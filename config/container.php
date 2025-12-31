<?php
use src\Core\Container;
use src\Core\Database;
use src\Models\Message;
use src\Models\User;
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

// Модели
$container->set(Message::class, function(Container $c) {
    return new Message($c->get('db.connection'));
});

$container->set(User::class, function(Container $c) {
    return new User($c->get('db.connection'));
});

// Контроллеры
$container->set(MessageController::class, function(Container $c) {
    return new MessageController($c->get(Message::class));
});

$container->set(UserController::class, function(Container $c) {
    return new UserController($c->get(User::class));
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
$container->set('message.model', function(Container $c) {
    return $c->get(Message::class);
});

$container->set('user.model', function(Container $c) {
    return $c->get(User::class);
});

$container->set('message.controller', function(Container $c) {
    return $c->get(MessageController::class);
});

$container->set('user.controller', function(Container $c) {
    return $c->get(UserController::class);
});

return $container;