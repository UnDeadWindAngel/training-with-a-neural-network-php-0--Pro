<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Models\Message;
use src\Models\User;

class ModelProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрируем сервисы
        $this->container->set(Message::class, function() {
            return new Message();
        });

        $this->container->set(User::class, function() {
            return new User();
        });
    }

    public function boot(): void
    {
        // Запускается после регистрации всех сервисов
        // Можно выполнить настройки, которые требуют все зарегистрированные сервисы
    }
}