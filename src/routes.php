<?php
// Регистрация маршрутов
use Bramus\Router\Router;
use src\Core\Container;
use src\Core\MiddlewarePipeline;

$router = new Router();

// Устанавливаем базовый путь (если проект в подпапке)
$router->setBasePath('/public');

// Получаем контейнер из глобальной переменной
$container = $GLOBALS['container'];

// Функция для создания pipeline
$createPipeline = function(array $middlewareClasses = []) use ($container) {
    $pipeline = new MiddlewarePipeline();

    // Глобальные middleware
    $pipeline->add($container->get(\src\Middleware\LoggingMiddleware::class));

    // Добавляем переданные middleware
    foreach ($middlewareClasses as $middlewareClass) {
        $pipeline->add($container->get($middlewareClass));
    }

    return $pipeline;
};

// Главная страница
$router->get('/', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST
    ];

    $pipeline = $createPipeline();

    $response = $pipeline->process($request, function($request) use ($container) {
        $title = $_ENV['APP_NAME'] ?? 'Гостевая книга';
        $content = '';
        require __DIR__ . '/Views/layout.php';
        return true;
    });
});

// API маршруты (логирование + CSRF для модифицирующих запросов)
$router->get('/api/messages', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST,
        'headers' => getallheaders()
    ];

    $pipeline = $createPipeline();

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\MessageController::class);
        $controller->indexJson();
    });
});

$router->post('/api/messages', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST,
        'headers' => getallheaders()
    ];

    $pipeline = $createPipeline([\src\Middleware\CSRFMiddleware::class]);

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\MessageController::class);
        $controller->create();
    });
});

// Веб-маршруты с полным набором middleware
$router->get('/messages', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST
    ];

    $pipeline = $createPipeline([\src\Middleware\AuthMiddleware::class]);

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\MessageController::class);
        $controller->indexView();
    });
});

$router->post('/messages', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST
    ];

    $pipeline = $createPipeline([
        \src\Middleware\AuthMiddleware::class,
        \src\Middleware\CSRFMiddleware::class
    ]);

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\MessageController::class);
        $controller->create();
    });
});

$router->post('/register', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST
    ];

    $pipeline = $createPipeline([
        \src\Middleware\AuthMiddleware::class,
        \src\Middleware\CSRFMiddleware::class
    ]);

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\UserController::class);
        $controller->register();
    });
});

$router->get('/login', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST
    ];

    $pipeline = $createPipeline();

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\UserController::class);
        $controller->indexView();
    });
});

$router->post('/login', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST
    ];

    $pipeline = $createPipeline();

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\UserController::class);
        $controller->login();
    });
});

$router->post('/logout', function() use ($createPipeline, $container) {
    $request = [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'get' => $_GET,
        'post' => $_POST
    ];

    $pipeline = $createPipeline();

    $pipeline->process($request, function($request) use ($container) {
        $controller = $container->get(\src\Controllers\UserController::class);
        $controller->logout();
    });
});

// Маршруты для сообщений
$router->delete('/messages/(\d+)/delete', 'src\Controllers\MessageController@delete');
$router->put('/messages/(\d+)/update', 'src\Controllers\MessageController@update');

// REST API (для будущего SPA)
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