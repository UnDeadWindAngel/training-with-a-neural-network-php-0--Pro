<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Middleware\CSRFMiddleware;
use src\Middleware\AuthMiddleware;
use src\Middleware\LoggingMiddleware;
use src\Middleware\ProfilerMiddleware;

class MiddlewareProvider extends ServiceProvider
{
    public function register(): void
    {
        // Middleware (factory - каждый запрос новый экземпляр)
        $this->container->factory(CSRFMiddleware::class, function($c) {
            return new CSRFMiddleware(/*$c->get(CacheInterface::class)*/);
        });

        $this->container->factory(AuthMiddleware::class, function($c) {
            return new AuthMiddleware(/*$c->get(CacheInterface::class)*/);
        });

        $this->container->factory(LoggingMiddleware::class, function() {
            return new LoggingMiddleware();
        });
/*
        // Пример с ProfilerMiddleware (если он будет реализован)
        if (class_exists(ProfilerMiddleware::class)) {
            $this->container->factory(ProfilerMiddleware::class, function($c) {
                return new ProfilerMiddleware($c->get('profiler'));
            });
        }*/
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}