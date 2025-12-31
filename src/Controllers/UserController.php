<?php

namespace src\Controllers;

use src\Models\User;

class UserController
{
    private $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    // Для обычного HTML вывода
    public function indexView():void
    {
        $title = $_ENV['APP_NAME'] ?? 'Гостевая книга';
        $login = $_SESSION['user_login'] ?? '';
        $password = '';

        // Подключаем представление
        ob_start();
        require __DIR__ . '/../Views/users/index.php';
        $content = ob_get_clean();

        include __DIR__ . '/../Views/layout.php';
    }

    public function register()
    {
        if (!empty($_POST['usermail']) && !empty($_POST['username']) && !empty($_POST['userpassword'])
                    && !empty($_POST['userconfirmpassword'])) {

            $email=$_POST['usermail'];
            $login=$_POST['username'];
            $password=$_POST['userpassword'];
            $confirmPassword=$_POST['userconfirmpassword'];
            $ip=$_SERVER['REMOTE_ADDR'];

            // Валидация
            if ($password !== $confirmPassword) {
                return ['error' => 'Пароли не совпадают'];
            }

            if (!$this->userModel->validatePassword($password)) {
                return ['error' => 'Пароль должен быть минимум 8 символов, содержать буквы и цифры'];
            }

            // Хеширование пароля
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Создание пользователя
            $this->userModel->create($email, $login, $hashedPassword, $ip);
        }

        $_SESSION['flash_message'] = 'Пользователь создан';
        $_SESSION['flash_type'] = 'success';

        // Редирект на страницу сообщений
        header('Location: /public/messages');
        exit;
    }

    public function login()
    {
        $login=$_POST['searchlogin'];
        $password=$_POST['searchpassword'];

        $user = $this->userModel->findByLogin($login);

        if (!$user) {
            return ['error' => 'Пользователь не найден'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['error' => 'Неверный пароль'];
        }

        // Успешная авторизация
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        $_SESSION['user_email'] = $user['mail'];
        $_SESSION['flash_message'] = 'Пользователь авторизован';
        $_SESSION['flash_type'] = 'success';

        // Редирект на страницу сообщений
        header('Location: /public/login');
        exit;
    }

    public function logout()
    {
        session_destroy();

        // Редирект на страницу сообщений
        header('Location: /public/');
        exit;
    }

    public function isAuthenticated()
    {
        return !empty($_SESSION['user_id']);
    }

    public function getCurrentUser()
    {
        if ($this->isAuthenticated()) {
            return [
                'id' => $_SESSION['user_id'],
                'login' => $_SESSION['user_login'],
                'email' => $_SESSION['user_email']
            ];
        }

        return null;
    }
}