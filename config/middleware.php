<?php
return [
    'global' => [
        \src\Middleware\LoggingMiddleware::class,
        \src\Middleware\CacheMiddleware::class,
        \src\Middleware\CSRFMiddleware::class,
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
    ],

    // Конфигурация middleware
    'config' => [
        'cache_middleware' => [
            'methods' => ['GET', 'HEAD'],
            'exclude' => [
                '/public/admin/',
                '/public/api/auth/',
                '/public/login',
                '/public/register',
                '/public/logout',
                '/public/messages/create',
                '/public/messages/update',
                '/public/messages/delete',
            ],
            'ttl' => [
                'default' => 300,
                'api' => 60,
                'static' => 3600,
                'messages' => 30,
                'users' => 180,
            ],
            'vary_by_user_agent' => false,
            'cache_control' => true,
        ],
        'csrf' => [
            'exclude' => ['/api/webhook/', '/api/webhook'],
            'token_length' => 32,
            'lifetime' => 3600,
            'max_tokens' => 10,
        ],
        'auth' => [
            'public_routes' => [
                '/public/',
                '/public/login',
                '/public/register',
                '/public/api/messages',
                '/public/api/messages/*',
                '/public/health',
                '/public/ping',
            ],
            'session_timeout' => 1800,
            'redirect_to' => '/public/login',
        ],
        'logging' => [
            'enabled' => true,
            'ignore_paths' => ['/health', '/ping', '/favicon.ico'],
            'log_ips' => true,
            'log_user_agents' => false,
            'log_performance' => true,
        ],
    ]
];