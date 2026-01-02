<?php
use src\Core\Container;
use src\Core\Database;
use src\Repositories\MessageRepository;
use src\Repositories\UserRepository;
use src\Services\MessageService;
use src\Services\UserService;
use src\Controllers\MessageController;
use src\Controllers\UserController;
use src\Middleware\CSRFMiddleware;
use src\Middleware\AuthMiddleware;
use src\Middleware\LoggingMiddleware;

// Создаем контейнер
$container = new Container();

// База данных (синглтон)
$container->singleton(Database::class, function() {
    return Database::getInstance();
});

// Подключение к БД (синглтон)
$container->singleton('db.connection', function(Container $c) {
    return $c->get(Database::class)->getConnection();
});

// Репозитории
$container->singleton(MessageRepository::class, function(Container $c) {
    return new MessageRepository($c->get('db.connection'));
});

$container->singleton(UserRepository::class, function(Container $c) {
    return new UserRepository($c->get('db.connection'));
});

// Сервисы
$container->singleton(MessageService::class, function(Container $c) {
    return new MessageService($c->get(MessageRepository::class));
});

$container->singleton(UserService::class, function(Container $c) {
    return new UserService($c->get(UserRepository::class));
});

// Контроллеры
$container->set(MessageController::class, function(Container $c) {
    return new MessageController($c->get(MessageService::class));
});

$container->set(UserController::class, function(Container $c) {
    return new UserController($c->get(UserService::class));
});

// Middleware (фабрики - каждый раз новый экземпляр)
$container->factory(CSRFMiddleware::class, function(Container $c) {
    return new CSRFMiddleware();
});

$container->factory(AuthMiddleware::class, function(Container $c) {
    return new AuthMiddleware();
});

$container->factory(LoggingMiddleware::class, function(Container $c) {
    return new LoggingMiddleware();
});

// Алиасы для удобства
$container->set('message.controller', function(Container $c) {
    return $c->get(MessageController::class);
});

$container->set('user.controller', function(Container $c) {
    return $c->get(UserController::class);
});

return $container;