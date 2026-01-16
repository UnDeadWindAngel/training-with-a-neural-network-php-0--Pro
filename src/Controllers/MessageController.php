<?php

namespace src\Controllers;

use src\Security\SecurityService;
use src\Services\MessageService;

class MessageController
{
    private MessageService $messageService;
    private SecurityService $securityService;

    public function __construct(MessageService $messageService, SecurityService $securityService)
    {
        $this->messageService = $messageService;
        $this->securityService = $securityService;
    }

    // Для обычного HTML вывода
    public function indexView()
    {
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);

        // Получаем данные
        $data = $this->messageService->getMessages($page, 10, $search);

        // Передаем в представление сервис защиты для генерации токенов CSRF
        $securityService = $this->securityService;

        // Генерируем CSRF токен для формы добавления
        $csrfTokenForAddForm = $securityService->generateCsrfToken();

        // Передаем данные в представление явно
        $title = $_ENV['APP_NAME'] ?? 'Гостевая книга';
        $messages = $data['messages'];
        $total = $data['total'];
        $totalPages = $data['totalPages'];
        $currentPage = $data['currentPage'];

        // Подключаем представление
        ob_start();
        require __DIR__ . '/../Views/messages/index.php';
        $content = ob_get_clean();

        include __DIR__ . '/../Views/layout.php';
    }

    // Для API (возвращает JSON)
    public function indexJson()
    {
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = $_GET['limit'] ?? 10;

        $data = $this->index($search, $page, $limit);

        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // Показать одно сообщение (для API)
    public function show($id)
    {
        $message = $this->messageService->findMessage($id);

        if (!$message) {
            http_response_code(404);
            echo json_encode(['error' => 'Сообщение не найдено']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($message, JSON_UNESCAPED_UNICODE);
    }

    // Обработка POST запроса
    public function create()
    {
        $name = $_POST['name'] ?? '';
        $message = $_POST['message'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!empty($name) && !empty($message)) {
            $this->messageService->createMessage($name, $message, $ip);

            $_SESSION['flash_message'] = 'Сообщение добавлено';
            $_SESSION['flash_type'] = 'success';

            // Редирект на страницу сообщений
            header('Location: /public/messages');
            return;
        }

        $_SESSION['flash_message'] = 'Заполните все поля';
        $_SESSION['flash_type'] = 'error';
        header('Location: /public/messages');
        return;
    }

    // Удаление с параметром из URL
    public function delete($id)
    {
        $UID = $_SESSION['user_id'] ?? '';
        if (empty($UID)) {
            http_response_code(403);
            echo json_encode(['error' => 'Требуется авторизация']);
            return;
        }

        $this->messageService->deleteMessage($id);
        $_SESSION['flash_message'] = 'Сообщение удалено';
        $_SESSION['flash_type'] = 'success';

        header('Location: /public/messages');
        exit;
    }

    // Обновление с параметром из URL
    public function update($id)
    {
        $UID = $_SESSION['user_id'] ?? '';
        $NewMSG = $_POST['newMessage'] ?? '';
        if (empty($UID)) {
            http_response_code(403);
            echo json_encode(['error' => 'Требуется авторизация']);
            return;
        }

        if (!empty($NewMSG)) {
            $this->messageService->updateMessage($id, $NewMSG);
            $_SESSION['flash_message'] = 'Сообщение обновлено';
            $_SESSION['flash_type'] = 'success';
        }

        header('Location: /public/messages');
        exit;
    }

    public function index($search = '', $page = 1, $limit = 10)
    {
        $messages = $this->messageService->getMessages($page, $limit, $search);

        return [
            'messages' => $messages['data'],
            'total' => $messages['total'],
            'totalPages' => $messages['totalPages'],
            'currentPage' => $messages['currentPage'],
            'search' => $search,
            'limit' => $limit
        ];
    }
}