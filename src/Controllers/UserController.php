<?php

namespace src\Controllers;

use src\Security\SecurityService;
use src\Services\UserService;

class UserController
{
    private UserService $userService;
    private SecurityService $securityService;

    public function __construct(UserService $userService, SecurityService $securityService)
    {
        $this->userService = $userService;
        $this->securityService = $securityService;
    }

    // Для обычного HTML вывода
    public function indexView():void
    {
        $title = $_ENV['APP_NAME'] ?? 'Гостевая книга';
        $login = $_SESSION['user_login'] ?? '';
        $password = '';

        // Генерация CSRF токенов для форм
        $csrfTokenRegister = $this->securityService->generateCsrfToken();
        $csrfTokenLogin = $this->securityService->generateCsrfToken();
        $csrfTokenLogout = $this->securityService->generateCsrfToken();

        // Подключаем представление
        ob_start();
        require __DIR__ . '/../Views/users/index.php';
        $content = ob_get_clean();

        include __DIR__ . '/../Views/layout.php';
    }

    public function register()
    {
        try {
            $email = $_POST['usermail'] ?? '';
            $login = $_POST['username'] ?? '';
            $password = $_POST['userpassword'] ?? '';
            $confirmPassword = $_POST['userconfirmpassword'] ?? '';

            // Валидация
            if ($password !== $confirmPassword) {
                throw new \Exception('Пароли не совпадают');
            }

            $this->userService->register($email, $login, $password, $_SERVER['REMOTE_ADDR']);

            $_SESSION['flash_message'] = 'Регистрация успешна! Теперь войдите.';
            $_SESSION['flash_type'] = 'success';
            header('Location: /public/login');
            exit;
        }
        catch (\Exception $e){

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            header('Location: /public/login');
            exit;
        }
    }

    public function login()
    {
        try {
            $login=$_POST['searchlogin'];
            $password=$_POST['searchpassword'];

            $user = $this->userService->authenticate($login, $password, $_SERVER['REMOTE_ADDR']);

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
        catch (\Exception $e){

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            header('Location: /public/login');
            exit;
        }
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