<?php
// Включаем автозагрузку Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Настройка сессии
session_start([
    'cookie_lifetime' => $_ENV['SESSION_LIFETIME'] ?? 3600,
    'cookie_secure' => $_ENV['APP_ENV'] === 'production',
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Инициализируем контейнер зависимостей
$container = require_once __DIR__ . '/../config/container.php';

// Сохраняем контейнер для глобального доступа (временно)
// В реальном проекте лучше использовать Service Locator или передавать явно
$GLOBALS['container'] = $container;

// Запускаем миграции при необходимости
if ($_ENV['APP_ENV'] === 'development') {
    $db = \src\Core\Database::getInstance()->getConnection();
    $migration = new \src\Core\Migration($db);
    $migration->runMigrations();
}

// Подключаем маршруты
require_once __DIR__ . '/../src/routes.php';