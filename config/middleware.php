<?php
return [
    'global' => [
        \src\Middleware\LoggingMiddleware::class
    ],

    'web' => [
        'auth' => \src\Middleware\AuthMiddleware::class,
        'csrf' => \src\Middleware\CSRFMiddleware::class
    ],

    'api' => [
        // В будущем: JWT middleware для API
    ],

    'routes' => [
        '/' => [],
        '/login' => [],
        '/register' => ['csrf'],
        '/messages' => ['auth', 'csrf'],
        '/api/messages' => [],
        '/api/messages/(\d+)' => []
    ]
];