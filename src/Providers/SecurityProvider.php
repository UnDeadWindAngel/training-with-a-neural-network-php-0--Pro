<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Security\SecurityService;

class SecurityProvider extends ServiceProvider
{
    public function register() : void
    {
        $this->container->set(SecurityService::class, function () {
            return new SecurityService();
        });

        // Псевдоним для удобства
        $this->container->set('security', function ($c) {
            return $c->get(SecurityService::class);
        });
    }

    public function boot(): void
    {
        // Инициализация сессии только если она нужна
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }

        // Инициализация HTMLPurifier (ленивая загрузка)
        if (!class_exists('HTMLPurifier')) {
            require_once __DIR__ . '/../../vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
        }
    }
}