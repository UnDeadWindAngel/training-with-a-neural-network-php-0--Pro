<?php
namespace src\Middleware;

class CSRFMiddleware implements MiddlewareInterface
{
    public function handle(array $request, callable $next)
    {
        // Проверяем только POST, PUT, DELETE, PATCH запросы
        $methods = ['POST', 'PUT', 'DELETE', 'PATCH'];

        if (in_array($request['method'], $methods)) {
            $token = $request['post']['csrf_token'] ??
                ($request['headers']['X-CSRF-Token'] ?? null);

            if (!$token || $token !== ($_SESSION['csrf_token'] ?? null)) {
                http_response_code(403);

                if ($this->isApiRequest($request)) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'CSRF token validation failed']);
                    exit;
                }

                die('CSRF token validation failed');
            }
        }

        return $next($request);
    }

    private function isApiRequest(array $request): bool
    {
        return strpos($request['uri'], '/api/') === 0;
    }
}