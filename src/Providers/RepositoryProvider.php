<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Repositories\MessageRepository;
use src\Repositories\MessageRepositoryInterface;
use src\Repositories\UserRepository;
use src\Repositories\UserRepositoryInterface;

class RepositoryProvider extends ServiceProvider
{
    public function register(): void
    {
        // Репозитории (синглтон, так как обычно не имеют состояния)
        $this->container->singleton(MessageRepository::class, function($c) {
            return new MessageRepository($c->get('db.connection'));
        });

        $this->container->singleton(MessageRepositoryInterface::class, function($c) {
            return $c->get(MessageRepository::class);
        });

        $this->container->singleton(UserRepository::class, function($c) {
            return new UserRepository($c->get('db.connection'));
        });

        $this->container->singleton(UserRepositoryInterface::class, function($c) {
            return $c->get(UserRepository::class);
        });

        // Алиасы для удобства
        $this->container->set('message.repository', function($c) {
            return $c->get(MessageRepository::class);
        });

        $this->container->set('message.repository.interface', function($c) {
            return $c->get(MessageRepositoryInterface::class);
        });

        $this->container->set('user.repository', function($c) {
            return $c->get(UserRepository::class);
        });

        $this->container->set('user.repository.interface', function($c) {
            return $c->get(UserRepositoryInterface::class);
        });
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}