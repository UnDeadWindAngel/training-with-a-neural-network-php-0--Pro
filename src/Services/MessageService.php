<?php
namespace src\Services;

use src\Repositories\MessageRepositoryInterface;
use src\Core\Cache\CacheInterface;
use src\Security\SecurityService;

class MessageService
{
    private MessageRepositoryInterface $messageRepository;
    private CacheInterface $cache;
    private int $cacheTtl = 300;
    private SecurityService $security;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        CacheInterface $cache,
        SecurityService $security
    ) {
        $this->messageRepository = $messageRepository;
        $this->cache = $cache;
        $this->security = $security;
    }

    /**
     * Получить все сообщения с пагинацией и поиском
     */
    public function getMessages(int $page = 1, int $perPage = 10, ?string $search = null)
    {
        $cacheKey = "messages:{$page}:{$perPage}:" . md5($search ?? '');

        // Пытаемся получить из кеша
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        if ($search) {

            if (!$this->security->validateSqlInput($search)) {
                throw new \InvalidArgumentException('Недопустимые символы в поисковом запросе');
            }

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

        $sanitizedData = array_map(function($message) {
            $message['message'] = $this->security->sanitizeHtml($message['message'] ?? '', [
                'p', 'br', 'strong', 'em', 'a', 'img', 'ul', 'ol', 'li'
            ]);
            return $message;
        }, $data);

        $response = [
            'messages' => $sanitizedData,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];

        // Сохраняем в кеш с тегами
        $this->cache->set($cacheKey, $response, $this->cacheTtl);
        $this->cache->tag($cacheKey, ['messages', 'messages:pagination']);

        return $response;
    }

    /**
     * Создать новое сообщение
     */
    public function createMessage(string $name, string $text, string $ip)
    {
        // Проверка rate limit для создания сообщений
        if (!$this->security->checkRateLimit($ip, 'create_message', 10, 300)) {
            throw new \RuntimeException('Превышено количество сообщений. Попробуйте позже.');
        }

        // Защита от XSS - санитизация имени
        $sanitizedName = $this->security->sanitizeHtml($name, ['b', 'i', 'u']);

        // Защита от XSS - санитизация текста сообщения
        $sanitizedText = $this->security->sanitizeHtml($text, [
            'p', 'br', 'strong', 'em', 'a', 'img', 'ul', 'ol', 'li', 'code', 'pre'
        ]);

        // Проверка на SQL инъекции в данных
        if (!$this->security->validateSqlInput($sanitizedName) ||
            !$this->security->validateSqlInput($sanitizedText)) {
            throw new \InvalidArgumentException('Недопустимые символы в данных');
        }

        $id = $this->messageRepository->create([
            'name' => $sanitizedName,
            'message' => $sanitizedText,
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
        // Получаем текущее сообщение для проверки прав
        $message = $this->messageRepository->find($id);
        if (!$message) {
            throw new \RuntimeException('Сообщение не найдено');
        }

        // Проверка rate limit для обновления
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!$this->security->checkRateLimit($ip, 'update_message', 15, 300)) {
            throw new \RuntimeException('Слишком много обновлений. Попробуйте позже.');
        }

        // Санитизация текста
        $sanitizedText = $this->security->sanitizeHtml($newText, [
            'p', 'br', 'strong', 'em', 'a', 'img', 'ul', 'ol', 'li', 'code', 'pre'
        ]);

        // Проверка на SQL инъекции
        if (!$this->security->validateSqlInput($sanitizedText)) {
            throw new \InvalidArgumentException('Недопустимые символы в тексте');
        }

        $result = $this->messageRepository->update($id, ['message' => $sanitizedText]);

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
        // Проверка rate limit для удаления
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!$this->security->checkRateLimit($ip, 'delete_message', 3, 300)) {
            throw new \RuntimeException('Слишком много удалений. Попробуйте позже.');
        }

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
            // Санитизируем HTML перед кешированием
            $message['message'] = $this->security->sanitizeHtml($message['message'] ?? '', [
                'p', 'br', 'strong', 'em', 'a', 'img', 'ul', 'ol', 'li', 'code', 'pre'
            ]);

            $this->cache->set($cacheKey, $message, $this->cacheTtl);

            $this->cache->tag($cacheKey, ['messages', "message:{$id}"]);
        }

        return $message;
    }

    /**
     * Поиск сообщений
     */
    public function searchMessages(string $query)
    {
        // Защита от SQL инъекций в поисковом запросе
        if (!$this->security->validateSqlInput($query)) {
            throw new \InvalidArgumentException('Недопустимые символы в поисковом запросе');
        }

        $cacheKey = "messages:search:" . md5($query);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $messages = $this->messageRepository->search($query);

        // Санитизируем HTML в результатах поиска
        $sanitizedMessages = array_map(function($message) {
            $message['message'] = $this->security->sanitizeHtml($message['message'] ?? '', [
                'p', 'br', 'strong', 'em', 'a', 'img', 'ul', 'ol', 'li', 'code', 'pre'
            ]);
            return $message;
        }, $messages);

        $this->cache->set($cacheKey, $sanitizedMessages, $this->cacheTtl);

        $this->cache->tag($cacheKey, ['messages', 'messages:search']);

        return $sanitizedMessages;
    }

    /**
     * Инвалидация кеша сообщений
     */
    private function invalidateMessagesCache(): void
    {
        // Удаляем все ключи, связанные с сообщениями
        if ($this->cache instanceof \src\Core\Cache\TaggableCacheInterface) {
            $this->cache->invalidateTag('messages');
        } else {
            // Удаляем по паттерну, если RedisCache
            if (method_exists($this->cache, 'deleteByPattern')) {
                $this->cache->deleteByPattern('messages:*');
                $this->cache->deleteByPattern('message:*');
            }
        }
    }

    /**
     * Очистить весь кеш
     */
    public function clearMessageCache(): bool
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