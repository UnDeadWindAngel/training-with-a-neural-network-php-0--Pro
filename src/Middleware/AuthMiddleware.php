<?php
namespace src\Middleware;

class AuthMiddleware implements MiddlewareInterface
{
    private $publicRoutes = [
        '/',
        '/login',
        '/register',
        '/api/messages'
    ];

    public function handle(array $request, callable $next)
    {
        // Публичные маршруты пропускаем без проверки
        if (in_array($request['uri'], $this->publicRoutes)) {
            return $next($request);
        }

        // API маршруты могут использовать JWT (в будущем)
        if ($this->isApiRequest($request)) {
            return $this->handleApiAuth($request, $next);
        }

        // Веб-маршруты проверяем сессию
        if (empty($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Пожалуйста, авторизуйтесь';
            $_SESSION['flash_type'] = 'error';
            $_SESSION['redirect_after_login'] = $request['uri'];

            header('Location: /public/login');
            exit;
        }

        return $next($request);
    }

    private function handleApiAuth(array $request, callable $next)
    {
        // Для API можно использовать JWT или API-ключи
        // Пока разрешаем доступ ко всем API без аутентификации
        // (в реальном проекте это нужно изменить)
        return $next($request);
    }

    private function isApiRequest(array $request): bool
    {
        return strpos($request['uri'], '/api/') === 0;
    }
}