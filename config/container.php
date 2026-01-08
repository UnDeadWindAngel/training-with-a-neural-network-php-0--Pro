<?php

use src\Core\Container;
use src\Providers\DatabaseProvider;
use src\Providers\CacheProvider;
use src\Providers\RepositoryProvider;
use src\Providers\ServiceProvider as AppServiceProvider;
use src\Providers\ControllerProvider;
use src\Providers\MiddlewareProvider;

// Создаем контейнер
$container = new Container();

// Список провайдеров в порядке зависимости
$providers = [
    DatabaseProvider::class,
    CacheProvider::class,
    RepositoryProvider::class,
    AppServiceProvider::class,
    ControllerProvider::class,
    MiddlewareProvider::class,
];

//Регистрация всех сервисов
foreach ($providers as $providerClass) {
    $provider = new $providerClass($container);
    $provider->register();
}

//Запуск boot методов (если нужно)
foreach ($providers as $providerClass) {
    $provider = new $providerClass($container);
    $provider->boot();
}

return $container;