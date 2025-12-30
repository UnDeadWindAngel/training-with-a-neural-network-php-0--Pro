<?php
// Регистрация маршрутов
use Bramus\Router\Router;

$router = new Router();

// Устанавливаем базовый путь (если проект в подпапке)
$router->setBasePath('/public');

// Middleware для CSRF (добавим позже)
// $router->before('POST|PUT|DELETE', '/.*', function() { ... });

// Главная страница
$router->get('/', function() {
    $title = $_ENV['APP_NAME'] ?? 'Гостевая книга';
    $content = '';
    include __DIR__ . '/Views/layout.php';
});

// Маршруты для сообщений
$router->get('/messages', 'src\Controllers\MessageController@indexView');
$router->post('/messages', 'src\Controllers\MessageController@create');
$router->delete('/messages/(\d+)/delete', 'src\Controllers\MessageController@delete');
$router->put('/messages/(\d+)/update', 'src\Controllers\MessageController@update');

// Маршруты для пользователей
$router->get('/login', 'src\Controllers\UserController@indexView');
$router->post('/register', 'src\Controllers\UserController@register');
$router->post('/login', 'src\Controllers\UserController@login');
$router->post('/logout', 'src\Controllers\UserController@logout');

// REST API (для будущего SPA)
$router->get('/api/messages', 'src\Controllers\MessageController@indexJson');
$router->get('/api/messages/(\d+)', 'src\Controllers\MessageController@show');

// 404 - страница не найдена
$router->set404(function() {
    http_response_code(404);
    $title = '404 - Страница не найдена';
    $content = '<h1>404 - Страница не найдена</h1>';
    include __DIR__ . '/Views/layout.php';
});

// Запуск маршрутизатора
$router->run();