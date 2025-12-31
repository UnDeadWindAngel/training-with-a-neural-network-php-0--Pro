<?php
namespace src\Core;

class MiddlewarePipeline
{
    private $middlewares = [];

    public function add($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this; // Для fluent interface
    }

    public function process(array $request, callable $finalHandler)
    {
        $handlerStack = $finalHandler;

        // Собираем цепочку в обратном порядке
        foreach (array_reverse($this->middlewares) as $middleware) {
            $handlerStack = function($request) use ($middleware, $handlerStack) {
                if (is_string($middleware)) {
                    $middleware = new $middleware();
                }

                return $middleware->handle($request, $handlerStack);
            };
        }

        return $handlerStack($request);
    }
}