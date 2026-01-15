<?php

use src\Core\Container;
use src\Providers\DatabaseProvider;
use src\Providers\CacheProvider;
use src\Providers\SecurityProvider;
use src\Providers\RepositoryProvider;
use src\Providers\ServiceProvider as AppServiceProvider;
use src\Providers\ControllerProvider;
use src\Providers\MiddlewareProvider;

// Создаем контейнер
$container = new Container();

// Загружаем конфигурацию middleware
$middlewareConfig = require __DIR__ . '/middleware.php';
$container->set('middleware_config', $middlewareConfig);

// Устанавливаем общую конфигурацию
$container->set('config', function() use ($middlewareConfig) {
    return [
        'middleware' => $middlewareConfig,
        'cache' => [
            'default_ttl' => $_ENV['REDIS_DEFAULT_TTL'] ?? 3600,
        ],
        'security' => [
            'csrf_lifetime' => 3600,
            'rate_limit' => [
                'login' => ['max_attempts' => 5, 'period' => 900],
                'register' => ['max_attempts' => 3, 'period' => 3600],
            ],
        ],
    ];
});

// Список провайдеров в порядке зависимости
$providers = [
    DatabaseProvider::class,
    CacheProvider::class,
    SecurityProvider::class,
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