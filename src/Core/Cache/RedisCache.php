<?php
namespace src\Core\Cache;
use Predis\Connection\ConnectionException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Predis\Client;

class RedisCache implements TaggableCacheInterface
{
    private Client $client;
    private int $defaultTtl;
    private LoggerInterface $logger;
    private bool $isConnected = false;
    private static $instance = null;

    /**
     * @param LoggerInterface|null $logger Логгер для ошибок
     */

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->defaultTtl = $_ENV['REDIS_DEFAULT_TTL'] ?? 3600;
        $this->logger = $logger ?? new NullLogger();

        try {
            $this->client = new Client([
                'scheme' => $_ENV['REDIS_SCHEME'] ?? 'tcp',
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => $_ENV['REDIS_DB'] ?? 0,
                'timeout' => $_ENV['REDIS_TIMEOUT'] ?? 5.0,
                'read_write_timeout' => $_ENV['REDIS_READ_WRITE_TIMEOUT'] ?? 5.0,
            ]);

            // Проверяем соединение
            $this->client->ping();
            $this->isConnected = true;
        } catch (ConnectionException $e) {
            $this->logger->error('Redis connection failed: ' . $e->getMessage());
            $this->isConnected = false;
        }
    }

    public function get(string $key, $default = null)
    {
        if (!$this->isConnected) {
            return $default;
        }

        try {
            $value = $this->client->get($key);

            if ($value === null) {
                return $default;
            }

            return unserialize($value, ['allowed_classes' => true]);
        } catch (\Exception $e) {
            $this->logger->error("Redis get error for key {$key}: " . $e->getMessage());
            return $default;
        }
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            $serialized = serialize($value);
            $expire = $ttl ?? $this->defaultTtl;

            if ($expire > 0) {
                return $this->client->setex($key, $expire, $serialized) === 'OK';
            }

            return $this->client->set($key, $serialized) === 'OK';
        } catch (\Exception $e) {
            $this->logger->error("Redis set error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            return $this->client->del([$key]) > 0;
        } catch (\Exception $e) {
            $this->logger->error("Redis delete error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function clear(): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            return $this->client->flushdb() === 'OK';
        } catch (\Exception $e) {
            $this->logger->error('Redis clear error: ' . $e->getMessage());
            return false;
        }
    }

    public function getMultiple(array $keys, $default = null): array
    {
        if (!$this->isConnected) {
            return array_fill_keys($keys, $default);
        }

        try {
            $values = $this->client->mget($keys);
            $result = [];

            foreach ($values as $i => $value) {
                $result[$keys[$i]] = $value !== null
                    ? unserialize($value, ['allowed_classes' => true])
                    : $default;
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Redis getMultiple error: ' . $e->getMessage());
            return array_fill_keys($keys, $default);
        }
    }

    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            $pipeline = $this->client->pipeline();
            $expire = $ttl ?? $this->defaultTtl;

            foreach ($values as $key => $value) {
                $serialized = serialize($value);
                if ($expire > 0) {
                    $pipeline->setex($key, $expire, $serialized);
                } else {
                    $pipeline->set($key, $serialized);
                }
            }

            $pipeline->execute();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Redis setMultiple error: ' . $e->getMessage());
            return false;
        }
    }

    public function has(string $key): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            return $this->client->exists($key) > 0;
        } catch (\Exception $e) {
            $this->logger->error("Redis has error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function tag(string $key, array $tags): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            foreach ($tags as $tag) {
                $tagKey = $this->getTagKey($tag);
                $this->client->sadd($tagKey, [$key]);
                $this->client->expire($tagKey, $this->defaultTtl);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Redis tag error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    public function invalidateTag(string $tag): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            $tagKey = $this->getTagKey($tag);
            $keys = $this->client->smembers($tagKey);

            if (!empty($keys)) {
                $this->client->del($keys);
            }

            $this->client->del([$tagKey]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Redis invalidateTag error for tag {$tag}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Инвалидирует несколько тегов
     *
     * @param array $tags Массив тегов
     * @return bool
     */
    public function invalidateTags(array $tags): bool
    {
        foreach ($tags as $tag) {
            if (!$this->invalidateTag($tag)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Получает список ключей по тегу
     *
     * @param string $tag Тег
     * @return array
     */
    public function getKeysByTag(string $tag): array
    {
        if (!$this->isConnected) {
            return [];
        }

        try {
            return $this->client->smembers($this->getTagKey($tag));
        } catch (\Exception $e) {
            $this->logger->error("Redis getKeysByTag error for tag {$tag}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Проверяет соединение с Redis
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Генерирует ключ для тега
     *
     * @param string $tag Тег
     * @return string
     */
    private function getTagKey(string $tag): string
    {
        return 'tag:' . $tag;
    }

    /**
     * Получает клиент Redis (для расширенных операций)
     *
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        return $this->isConnected ? $this->client : null;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
    * Удаляет ключи по паттерну
    *
    * @param string $pattern Паттерн (например: 'messages:*')
    * @return bool
    */
    public function deleteByPattern(string $pattern): bool
    {
        if (!$this->isConnected) {
            return false;
        }

        try {
            $keys = $this->client->keys($pattern);
            if (!empty($keys)) {
                $this->client->del($keys);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Redis deleteByPattern error for pattern {$pattern}: " . $e->getMessage());
            return false;
        }
    }
}