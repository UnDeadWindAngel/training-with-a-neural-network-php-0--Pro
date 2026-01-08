<?php
namespace src\Providers;

use src\Core\ServiceProvider as BaseServiceProvider;
use src\Services\MessageService;
use src\Services\UserService;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        // Сервисы (синглтон)
        $this->container->singleton(MessageService::class, function($c) {
            return new MessageService(
                $c->get('message.repository.interface'),
                $c->get('cache')
            );
        });

        $this->container->singleton(UserService::class, function($c) {
            return new UserService(
                $c->get('user.repository.interface'),
                $c->get('cache')
            );
        });

        // Алиасы для удобства
        $this->container->set('message.service', function($c) {
            return $c->get(MessageService::class);
        });

        $this->container->set('user.service', function($c) {
            return $c->get(UserService::class);
        });
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}