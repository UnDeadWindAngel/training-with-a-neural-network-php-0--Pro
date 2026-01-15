<?php
namespace src\Middleware;

use src\Core\Cache\CacheInterface;

class CacheMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private array $config;
    private array $cacheableMethods = ['GET', 'HEAD'];
    private array $excludedPaths = ['/admin/', '/api/auth/', '/login', '/register'];
    public function __construct(CacheInterface $cache, array $config = [])
    {
        $this->cache = $cache;
        $this->config = array_merge([
            'methods' => ['GET', 'HEAD'],
            'exclude' => ['/admin/', '/api/auth/', '/login', '/register'],
            'ttl' => [
                'default' => 300,
                'api' => 60,
                'static' => 3600,
            ],
            'vary_by_user_agent' => false,
            'cache_control' => true,
        ], $config);

        $this->cacheableMethods = $this->config['methods'];
        $this->excludedPaths = $this->config['exclude'];
    }

    public function handle($request, callable $next)
    {
        $method = $request['method'] ?? 'GET';
        $uri = $request['uri'] ?? '/';

        // Проверяем, можно ли кешировать этот запрос
        if (!$this->isCacheable($method, $uri)) {
            return $next($request);
        }

        $cacheKey = $this->generateCacheKey($request);

        // Пытаемся получить из кеша
        $cachedResponse = $this->cache->get($cacheKey);
        if ($cachedResponse !== null && $this->isCacheValid($cachedResponse)) {
            return $this->serveFromCache($cachedResponse);
        }

        // Перехватываем вывод для кеширования
        ob_start();
        $response = $next($request);
        $content = ob_get_clean();

        // Если нет контента от next, используем return значение
        if (empty($content) && !empty($response) && (is_string($response) || is_array($response))) {
            $content = is_string($response) ? $response : json_encode($response);
        }

        // Кешируем ответ
        $this->cacheResponse($cacheKey, $content, $uri);

        return $content;
    }

    private function isCacheable(string $method, string $uri): bool
    {
        // Проверяем метод
        if (!in_array($method, $this->cacheableMethods)) {
            return false;
        }

        // Проверяем исключенные пути
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($uri, $excludedPath) === 0) {
                return false;
            }
        }

        // Не кешируем авторизованные запросы (кроме API)
        if (isset($_SESSION['user_id']) && strpos($uri, '/api/') !== 0) {
            return false;
        }

        return true;
    }

    private function generateCacheKey(array $request): string
    {
        $uri = $request['uri'] ?? '/';
        $method = $request['method'] ?? 'GET';

        $keyData = [
            'method' => $method,
            'uri' => $uri,
            'query' => $_SERVER['QUERY_STRING'] ?? '',
        ];

        // Добавляем user agent если нужно
        if ($this->config['vary_by_user_agent'] && isset($request['user_agent'])) {
            $keyData['ua'] = substr(md5($request['user_agent']), 0, 8);
        }

        // Добавляем версию приложения для инвалидации кеша при обновлении
        $keyData['app_version'] = $_ENV['APP_VERSION'] ?? '1.0';

        return 'http_cache:' . md5(serialize($keyData));
    }

    private function isCacheValid(array $cachedData): bool
    {
        if (!isset($cachedData['expires_at'])) {
            return false;
        }

        return time() <= $cachedData['expires_at'];
    }

    private function serveFromCache(array $cachedData)
    {
        // Восстанавливаем заголовки из кеша
        if (!empty($cachedData['headers'])) {
            foreach ($cachedData['headers'] as $header) {
                if (!headers_sent() && !empty($header)) {
                    header($header);
                }
            }
        }

        // Добавляем заголовки о кешировании
        header('X-Cache: HIT');
        if (isset($cachedData['created_at'])) {
            header('X-Cache-Date: ' . date('Y-m-d H:i:s', $cachedData['created_at']));
        }

        return $cachedData['content'];
    }

    private function cacheResponse(string $key, string $content, string $uri): void
    {
        // Определяем TTL в зависимости от пути
        $ttl = $this->config['ttl']['default'];
        if (strpos($uri, '/api/') === 0) {
            $ttl = $this->config['ttl']['api'] ?? $ttl;
        } elseif (preg_match('#\.(css|js|png|jpg|jpeg|gif|ico|svg)$#', $uri)) {
            $ttl = $this->config['ttl']['static'] ?? $ttl;
        }

        $cacheData = [
            'content' => $content,
            'headers' => headers_list(),
            'created_at' => time(),
            'expires_at' => time() + $ttl,
            'uri' => $uri,
        ];

        // Сохраняем в кеш с тегами
        $this->cache->set($key, $cacheData, $ttl);

        // Добавляем теги для групповой инвалидации
        if ($this->cache instanceof \src\Core\Cache\TaggableCacheInterface) {
            $tags = ['http_cache', 'uri:' . md5($uri)];
            if (strpos($uri, '/api/') === 0) {
                $tags[] = 'api_cache';
            }
            $this->cache->tag($key, $tags);
        }

        header('X-Cache: MISS');
        if ($this->config['cache_control']) {
            header('Cache-Control: public, max-age=' . $ttl);
        }
    }

    /**
     * Инвалидация кеша по паттерну
     */
    public function invalidateCache(string $pattern): bool
    {
        if (method_exists($this->cache, 'deleteByPattern')) {
            return $this->cache->deleteByPattern($pattern);
        }
        return false;
    }

    /**
     * Очистка всего HTTP кеша
     */
    public function clearHttpCache(): bool
    {
        return $this->invalidateCache('http_cache:*');
    }
}