<?php
// Включаем автозагрузку Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Стартуем сессию
session_start();

// CSRF токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Подключаем маршруты
require_once __DIR__ . '/../src/routes.php';

// Подключаем бд
require_once __DIR__ . '/../src/Core/Database.php';

$db = new (\src\Core\Database::class);

// Запуск миграций при необходимости
$migration = new \src\Core\Migration($db->getConnection());
$migration->runMigrations();