<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Controllers\MessageController;
use src\Controllers\UserController;

class ControllerProvider extends ServiceProvider
{
    public function register(): void
    {
        // Контроллеры (factory - каждый запрос новый экземпляр)
        $this->container->factory(MessageController::class, function($c) {
            return new MessageController(
                $c->get('message.service'),
                $c->get('security')
            );
        });

        $this->container->factory(UserController::class, function($c) {
            return new UserController(
                $c->get('user.service'),
                $c->get('security')
            );
        });

        // Алиасы для удобства
        $this->container->set('message.controller', function($c) {
            return $c->get(MessageController::class);
        });

        $this->container->set('user.controller', function($c) {
            return $c->get(UserController::class);
        });
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}