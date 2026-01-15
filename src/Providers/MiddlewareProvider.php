<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Middleware\CSRFMiddleware;
use src\Middleware\AuthMiddleware;
use src\Middleware\CacheMiddleware;
use src\Middleware\LoggingMiddleware;
use src\Middleware\ProfilerMiddleware;

class MiddlewareProvider extends ServiceProvider
{
    public function register(): void
    {
        // Middleware (factory - каждый запрос новый экземпляр)
        $this->container->factory(CacheMiddleware::class, function($c) {
            $config = [];
            if ($c->has('config')) {
                $config = $c->get('config');
            }

            $cacheConfig = $config['cache_middleware'] ?? [
                'methods' => ['GET', 'HEAD'],
                'exclude' => ['/public/admin/', '/public/api/auth/', '/public/login', '/public/register'],
                'ttl' => [
                    'default' => 300,
                    'api' => 60,
                    'static' => 3600,
                ],
                'vary_by_user_agent' => false,
                'cache_control' => true,
            ];

            return new CacheMiddleware(
                $c->get('cache'),
                $cacheConfig
            );
        });

        $this->container->factory(CSRFMiddleware::class, function($c) {
            $config = [];
            if ($c->has('config')) {
                $config = $c->get('config');
            }

            $csrfConfig = $config['csrf'] ?? [
                'exclude' => ['/api/webhook/'],
                'token_length' => 32,
                'lifetime' => 3600,
            ];

            return new CSRFMiddleware(
                $c->get('security'),
                $csrfConfig
            );
        });

        $this->container->factory(AuthMiddleware::class, function($c) {
            $config = [];
            if ($c->has('config')) {
                $config = $c->get('config');
            }

            $authConfig = $config['auth'] ?? [
                'public_routes' => ['/', '/public/login', '/public/register', '/public/api/messages'],
                'session_timeout' => 1800,
                'redirect_to' => '/public/login',
            ];

            return new AuthMiddleware(
                $c->get('security'),
                $authConfig
            );
        });

        $this->container->factory(LoggingMiddleware::class, function($c) {
            $config = [];
            if ($c->has('config')) {
                $config = $c->get('config');
            }

            $logConfig = $config['logging'] ?? [
                'enabled' => true,
                'ignore_paths' => ['/health', '/ping'],
                'log_ips' => true,
                'log_user_agents' => false,
            ];

            return new LoggingMiddleware($logConfig);
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
        // Инициализация сессии для middleware
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
    }
}