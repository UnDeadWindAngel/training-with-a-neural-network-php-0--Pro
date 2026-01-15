<?php
namespace src\Middleware;

use src\Security\SecurityService;

class CSRFMiddleware implements MiddlewareInterface
{
    private SecurityService $securityService;
    private array $excludedPaths;
    private array $allowedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    public function __construct(SecurityService $securityService, array $config = [])
    {
        $this->securityService = $securityService;
        $this->excludedPaths = $config['exclude'] ?? ['/api/webhook/'];
    }

    public function handle(array $request, callable $next)
    {
        $method = $request['method'] ?? 'GET';
        $uri = $request['uri'] ?? '/';

        // Проверяем, не исключен ли маршрут
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($uri, $excludedPath) === 0) {
                return $next($request);
            }
        }

        // GET запросы - добавляем токен в сессию если его нет
        if ($method === 'GET') {
            $this->ensureCsrfToken();
        }

        // Проверяем токен для модифицирующих методов
        elseif (in_array($method, $this->allowedMethods)) {
            if (!$this->validateCsrfToken($request)) {
                return $this->handleCsrfFailure($method);
            }
        }

        return $next($request);
    }

    private function ensureCsrfToken(): void
    {
        // Генерируем токен, если его нет в сессии
        if (!isset($_SESSION['csrf_tokens']) || empty($_SESSION['csrf_tokens'])) {
            $this->securityService->generateCsrfToken();
        }
    }

    private function validateCsrfToken(array $request): bool
    {
        $token = $this->getTokenFromRequest($request);

        if (empty($token)) {
            return false;
        }

        return $this->securityService->validateCsrfToken($token);
    }

    private function getTokenFromRequest(array $request): string
    {
        // Проверяем в разных местах
        return $request['post']['csrf_token'] ??
            $request['get']['csrf_token'] ??
            ($request['headers']['X-CSRF-Token'] ??
                ($request['headers']['X-XSRF-Token'] ?? ''));
    }

    private function handleCsrfFailure(string $method)
    {
        http_response_code(403);

        // Для AJAX запросов возвращаем JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            return json_encode([
                'error' => 'CSRF token validation failed',
                'code' => 'csrf_token_invalid',
                'message' => 'Токен безопасности устарел или отсутствует'
            ]);
        }

        // Для обычных запросов - страница с ошибкой
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>403 Forbidden</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .error { color: #d32f2f; }
                .message { margin: 20px 0; }
                .back-link { margin-top: 20px; }
            </style>
        </head>
        <body>
            <h1 class="error">403 Forbidden</h1>
            <div class="message">
                <p>CSRF token validation failed</p>
                <p>Ваш запрос не может быть обработан из-за отсутствия или недействительности токена безопасности.</p>
            </div>
            <div class="back-link">
                <a href="javascript:history.back()">Вернуться назад</a> | 
                <a href="/">На главную</a>
            </div>
        </body>
        </html>
        HTML;
        return $html;
    }
}