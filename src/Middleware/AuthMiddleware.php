<?php
namespace src\Middleware;

use src\Security\SecurityService;

class AuthMiddleware implements MiddlewareInterface
{
    private SecurityService $securityService;
    private array $publicRoutes;
    private string $redirectTo;
    private int $sessionTimeout;

    public function __construct(SecurityService $securityService, array $config = [])
    {
        $this->securityService = $securityService;
        $this->publicRoutes = $config['public_routes'] ?? [
            '/public/',
            '/public/login',
            '/public/register',
            '/api/messages'
        ];
        $this->redirectTo = $config['redirect_to'] ?? '/public/login';
        $this->sessionTimeout = $config['session_timeout'] ?? 1800;
    }

    public function handle(array $request, callable $next)
    {
        $uri = $request['uri'];

        // Проверяем, является ли маршрут публичным
        if ($this->isPublicRoute($uri)) {
            return $next($request);
        }

        // Проверяем аутентификацию пользователя
        if (!$this->isAuthenticated()) {
            return $this->redirectToLogin($request);
        }

        // Проверяем активность сессии
        if ($this->isSessionExpired()) {
            $this->handleSessionExpired();
        }

        // Обновляем время последней активности
        $_SESSION['last_activity'] = time();

        return $next($request);
    }

    private function isPublicRoute(string $uri): bool
    {
        foreach ($this->publicRoutes as $route) {
            if ($route === $uri) {
                return true;
            }

            // Поддержка wildcard маршрутов (например, '/api/messages/*')
            if (strpos($route, '*') !== false) {
                $pattern = str_replace('*', '.*', $route);
                if (preg_match('#^' . $pattern . '$#', $uri)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) &&
            isset($_SESSION['authenticated']) &&
            $_SESSION['authenticated'] === true;
    }

    private function isSessionExpired(): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return false;
        }

        return (time() - $_SESSION['last_activity']) > $this->sessionTimeout;
    }

    private function redirectToLogin(array $request)
    {
        // Для AJAX запросов
        if ($this->isAjaxRequest($request)) {
            http_response_code(401);
            header('Content-Type: application/json');
            return json_encode([
                'error' => 'Authentication required',
                'redirect' => $this->redirectTo
            ]);
        }

        // Для обычных запросов
        $_SESSION['redirect_after_login'] = $request['uri'];
        $_SESSION['flash_message'] = 'Пожалуйста, авторизуйтесь';
        $_SESSION['flash_type'] = 'error';

        header('Location: ' . $this->redirectTo);
        exit;
    }

    private function handleSessionExpired()
    {
        // Очищаем сессию
        session_unset();
        session_destroy();

        // Начинаем новую сессию для flash сообщения
        session_start();
        $_SESSION['flash_message'] = 'Сессия истекла. Пожалуйста, войдите снова.';
        $_SESSION['flash_type'] = 'warning';

        header('Location: ' . $this->redirectTo . '?expired=1');
        exit;
    }

    private function isAjaxRequest(array $request): bool
    {
        return isset($request['headers']['X-Requested-With']) &&
            strtolower($request['headers']['X-Requested-With']) === 'xmlhttprequest';
    }
}