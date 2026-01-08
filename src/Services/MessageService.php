<?php
namespace src\Services;

use src\Repositories\MessageRepositoryInterface;
use src\Core\Cache\CacheInterface;

class MessageService
{
    private $messageRepository;
    private $cache;
    private int $cacheTtl = 300;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        CacheInterface $cache
    ) {
        $this->messageRepository = $messageRepository;
        $this->cache = $cache;
    }

    /**
     * Получить все сообщения с пагинацией и поиском
     */
    public function getMessages(int $page = 1, int $perPage = 10, ?string $search = null)
    {
        $cacheKey = "messages:{$page}:{$perPage}:" . ($search ?? '');

        // Пытаемся получить из кеша
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        if ($search) {
            $messages = $this->messageRepository->search($search);
            $total = count($messages);
            $totalPages = ceil($total / $perPage);
            $data = array_slice($messages, ($page - 1) * $perPage, $perPage);
        } else {
            $result = $this->messageRepository->paginate($perPage, $page);
            $data = $result['data'];
            $total = $result['total'];
            $totalPages = $result['total_pages'];
        }

        $response = [
            'messages' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];

        // Сохраняем в кеш
        $this->cache->set($cacheKey, $response, $this->cacheTtl);

        return $response;
    }

    /**
     * Создать новое сообщение
     */
    public function createMessage(string $name, string $text, string $ip)
    {
        $id = $this->messageRepository->create([
            'name' => $name,
            'message' => $text,
            'ip_address' => $ip
        ]);

        // Инвалидируем кеш сообщений
        $this->invalidateMessagesCache();

        return $id;
    }

    /**
     * Обновить сообщение
     */
    public function updateMessage(int $id, string $newText)
    {
        $result = $this->messageRepository->update($id, ['message' => $newText]);

        if ($result) {
            // Удаляем кеш конкретного сообщения
            $this->cache->delete("message:{$id}");
            // Инвалидируем общий кеш
            $this->invalidateMessagesCache();
        }

        return $result;
    }

    /**
     * Удалить сообщение
     */
    public function deleteMessage(int $id)
    {
        $result = $this->messageRepository->delete($id);

        if ($result) {
            $this->cache->delete("message:{$id}");
            $this->invalidateMessagesCache();
        }

        return $result;
    }

    /**
     * Найти сообщение по ID
     */
    public function findMessage(int $id)
    {
        $cacheKey = "message:{$id}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $message = $this->messageRepository->find($id);

        if ($message) {
            $this->cache->set($cacheKey, $message, $this->cacheTtl);
        }

        return $message;
    }

    /**
     * Поиск сообщений
     */
    public function searchMessages(string $query)
    {
        $cacheKey = "messages:search:" . md5($query);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $messages = $this->messageRepository->search($query);
        $this->cache->set($cacheKey, $messages, $this->cacheTtl);

        return $messages;
    }

    /**
     * Инвалидация кеша сообщений
     */
    private function invalidateMessagesCache(): void
    {
        // Удаляем все ключи, связанные с сообщениями
        // В реальном проекте можно использовать теги, но для простоты удаляем по паттерну
        // Если используете RedisCache с поддержкой тегов:
        if ($this->cache instanceof \src\Core\Cache\TaggableCacheInterface) {
            $this->cache->invalidateTag('messages');
        }
    }

    /**
     * Очистить весь кеш
     */
    public function clearCache(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Установить время жизни кеша
     */
    public function setCacheTtl(int $seconds): void
    {
        $this->cacheTtl = $seconds;
    }
}