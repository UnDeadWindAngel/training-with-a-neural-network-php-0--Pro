<?php
namespace src\Services;

use src\Repositories\MessageRepositoryInterface;

class MessageService
{
    private $messageRepository;

    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Получить все сообщения с пагинацией и поиском
     */
    public function getMessages(int $page = 1, int $perPage = 10, ?string $search = null)
    {
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

        return [
            'messages' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];
    }

    /**
     * Создать новое сообщение
     */
    public function createMessage(string $name, string $text, string $ip)
    {
        return $this->messageRepository->create([
            'name' => $name,
            'message' => $text,
            'ip_address' => $ip
        ]);
    }

    /**
     * Обновить сообщение
     */
    public function updateMessage(int $id, string $newText)
    {
        return $this->messageRepository->update($id, ['message' => $newText]);
    }

    /**
     * Удалить сообщение
     */
    public function deleteMessage(int $id)
    {
        return $this->messageRepository->delete($id);
    }

    /**
     * Найти сообщение по ID
     */
    public function findMessage(int $id)
    {
        return $this->messageRepository->find($id);
    }
}