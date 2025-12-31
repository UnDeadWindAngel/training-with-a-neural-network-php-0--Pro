<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Core\Database;
use src\Models\Message;
use src\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрируем сервисы
        $this->container->singleton(Database::class, function() {
            return Database::getInstance();
        });

        $this->container->set(Message::class, function($c) {
            return new Message($c->get(Database::class)->getConnection());
        });

        $this->container->set(User::class, function($c) {
            return new User($c->get(Database::class)->getConnection());
        });
    }

    public function boot(): void
    {
        // Запускается после регистрации всех сервисов
        // Можно выполнить настройки, которые требуют все зарегистрированные сервисы
    }
}