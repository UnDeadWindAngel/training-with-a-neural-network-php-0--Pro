<?php
namespace src\Middleware;

interface MiddlewareInterface
{
    /**
     * Обработка запроса
     * @param array $request Данные запроса
     * @param callable $next Следующий middleware/обработчик
     * @return mixed
     */
    public function handle(array $request, callable $next);
}