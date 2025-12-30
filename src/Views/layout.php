<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .alert { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        input, textarea { display: block; margin: 10px 0; padding: 8px; width: 100%; }
    </style>
</head>
<body>
<div class="container">
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <main>
        <header>
            <h1>Добро пожаловать в гостевую книгу!</h1>
            <nav>
                <p><a href="/public">Главная</a></p>
                <p><a href="/public/messages">Перейти к сообщениям</a></p>
                <p><a href="/public/login">Войти или зарегистрироваться</a></p>
            </nav>
        </header>
        <?php echo $content; ?>
    </main>

    <footer>
        <p>Среда: <?php echo $_ENV['APP_ENV']; ?> |
            PHP: <?php echo PHP_VERSION; ?> |
            <small>© 2026</small></p>
    </footer>
</div>
</body>
</html>