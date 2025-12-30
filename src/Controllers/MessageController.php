<?php

namespace src\Controllers;

use src\Models\Message;

class MessageController
{
    private $messageModel;

    public function __construct($db = null)
    {
        // Если БД не передана, создаем подключение
        if (!$db) {
            $db = \src\Core\Database::getInstance()->getConnection();
        }
        $this->messageModel = new Message($db);
    }

    // Для обычного HTML вывода
    public function indexView()
    {
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);

        // Получаем данные
        $data = $this->index($search, $page);

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
        // Реализуйте метод getById в модели Message
        $message = $this->messageModel->getById($id);

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
        // Проверка CSRF (должна быть в Middleware, но пока здесь)
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF token validation failed']);
            return;
        }

        if (!empty($_POST['name']) && !empty($_POST['message'])) {
            $this->messageModel->create(
                $_POST['name'],
                $_POST['message'],
                $_SERVER['REMOTE_ADDR']
            );

            $_SESSION['flash_message'] = 'Сообщение добавлено';
            $_SESSION['flash_type'] = 'success';

            // Редирект на страницу сообщений
            header('Location: /public/messages');
            exit;
        }

        $_SESSION['flash_message'] = 'Заполните все поля';
        $_SESSION['flash_type'] = 'error';
        header('Location: /public/messages');
        exit;
    }

    // Удаление с параметром из URL
    public function delete($id)
    {
        var_dump($_SERVER);
        if (empty($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Требуется авторизация']);
            return;
        }

        $this->messageModel->delete($id);
        $_SESSION['flash_message'] = 'Сообщение удалено';
        $_SESSION['flash_type'] = 'success';

        header('Location: /public/messages');
        exit;
    }

    // Обновление с параметром из URL
    public function update($id)
    {
        if (empty($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Требуется авторизация']);
            return;
        }

        if (!empty($_POST['newMessage'])) {
            $this->messageModel->update($id, $_POST['newMessage']);
            $_SESSION['flash_message'] = 'Сообщение обновлено';
            $_SESSION['flash_type'] = 'success';
        }

        header('Location: /public/messages');
        exit;
    }

    public function index($search = '', $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;

        $messages = $this->messageModel->getAll($search, $limit, $offset);
        $total = $this->messageModel->getCount($search);
        $totalPages = ceil($total / $limit);

        return [
            'messages' => $messages,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'limit' => $limit
        ];
    }
}