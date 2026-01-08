<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Core\Database;

class DatabaseProvider extends ServiceProvider
{
    public function register(): void
    {
        // База данных (синглтон)
        $this->container->singleton(Database::class, function() {
            return Database::getInstance();
        });

        // Подключение к БД (синглтон)
        $this->container->singleton('db.connection', function($c) {
            return $c->get(Database::class)->getConnection();
        });
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}