<?php
require_once 'db_connection.php';
$db = (new Database())->connect();

// Функции для работы с БД
// Вспомогательная функция для безопасного получения GET-параметров
function getSafeParam($param, $default = '') {
    return isset($_GET[$param]) ? htmlspecialchars(trim($_GET[$param])) : $default;
}

// Обработка POST-запросов (добавление, удаление, обновление)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!empty($_POST['name']) && !empty($_POST['message'])) {
                    saveMessageSQL($db, $_POST['name'], $_POST['message']);
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
                break;

            case 'delete':
                if (!empty($_POST['id'])) {
                    deleteMessageSQL($db, (int)$_POST['id']);
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
                break;

            case 'update':
                if (!empty($_POST['id']) && !empty($_POST['newMessage'])) {
                    updateMessageSQL($db, (int)$_POST['id'], (string)$_POST['newMessage']);
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                }
                break;
        }
    }
}


// Обработка GET-параметров (поиск и пагинация)
$limit = 10;
$searchString = getSafeParam('searchString');
$page = (int)getSafeParam('page', 1);
$page = max(1, $page); // Страница не меньше 1
$offset = ($page - 1) * $limit;

// Формируем запросы в зависимости от режима поиска
$searchMode = !empty($searchString);

if ($searchMode) {
    // Запрос с поиском
    $searchParam = '%' . $searchString . '%';

    // Общее количество для пагинации
    $countStmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE message LIKE ? OR name LIKE ?");
    $countStmt->execute([$searchParam, $searchParam]);
    $totalRows = $countStmt->fetchColumn();

    // Данные для текущей страницы
    $dataStmt = $db->prepare("SELECT * FROM messages WHERE message LIKE ? OR name LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $dataStmt->bindValue(1, $searchParam, PDO::PARAM_STR);
    $dataStmt->bindValue(2, $searchParam, PDO::PARAM_STR);
    $dataStmt->bindValue(3, $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(4, $offset, PDO::PARAM_INT);
} else {
    // Запрос без поиска
    $countStmt = $db->prepare("SELECT COUNT(*) FROM messages");
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();

    // Данные для текущей страницы
    $dataStmt = $db->prepare("SELECT * FROM messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $dataStmt->bindValue(1, $limit, PDO::PARAM_INT);
    $dataStmt->bindValue(2, $offset, PDO::PARAM_INT);
}

function saveMessageSQL($db, $name, $message) {
    $sql = "INSERT INTO messages (name, message, ip_address) VALUES (:name, :message, :ip)";
    $stmt = $db->prepare($sql);

    $stmt->execute([
        ':name' => htmlspecialchars(trim($name)),
        ':message' => htmlspecialchars(trim($message)),
        ':ip' => $_SERVER['REMOTE_ADDR']
    ]);

    return $db->lastInsertId();
}

function deleteMessageSQL($db, $id) {
    // Проверяем, существует ли сообщение
    $check = $db->prepare("SELECT id FROM messages WHERE id = :id");
    $check->execute([':id' => $id]);

    if ($check->rowCount() > 0) {
        $stmt = $db->prepare("DELETE FROM messages WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    return false;
}

function updateMessageSQL($db, $id, $newMessage)
{
    // Проверяем, существует ли сообщение
    $check = $db->prepare("SELECT id FROM messages WHERE id = :id");
    $check->execute([':id' => $id]);

    if ($check->rowCount() > 0) {
        $stmt = $db->prepare("UPDATE messages SET message = :newmessage WHERE id = :id");
        return $stmt->execute([':newmessage' => htmlspecialchars(trim($newMessage)), ':id' => $id]);
    }

    return false;
}

$dataStmt->execute();
$messages = $dataStmt->fetchAll();

$totalPages = ceil($totalRows / $limit);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Гостевая книга (MySQL)</title>
    <style>
        .message { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .message small { color: #666; }
        .delete-form, .update-form { display: inline-block; margin-right: 10px; }
        .pagination { margin: 20px 0; }
        .pagination a { padding: 5px 10px; border: 1px solid #ccc; margin: 0 2px; text-decoration: none; }
        .pagination .active { background-color: #007bff; color: white; }
    </style>
</head>
<body>
<h1>Гостевая книга с MySQL</h1>

<!-- Форма добавления -->
<form method="POST">
    <input type="hidden" name="action" value="add">
    <input type="text" name="name" placeholder="Ваше имя" required><br><br>
    <textarea name="message" placeholder="Ваше сообщение" rows="4" required></textarea><br><br>
    <button type="submit">Добавить сообщение</button>
</form>

<!-- Форма поиска -->
<form method="GET">
    <input type="text" name="searchString" placeholder="Поиск по сообщениям и именам"
           value="<?php echo htmlspecialchars($searchString); ?>">
    <button type="submit">Найти</button>
    <?php if ($searchMode): ?>
        <a href="?">Сбросить поиск</a>
    <?php endif; ?>
</form>

<hr>

<!-- Статистика -->
<h2>
    <?php if ($searchMode): ?>
        Результаты поиска "<?php echo htmlspecialchars($searchString); ?>":
    <?php else: ?>
        Все сообщения:
    <?php endif; ?>
    (показано <?php echo count($messages); ?> из <?php echo $totalRows; ?>)
</h2>

<!-- Список сообщений -->
<h2>Сообщений: <?php echo count($messages); ?></h2>

<?php if (empty($messages)): ?>
    <p>Нет сообщений</p>
<?php else: ?>
    <?php foreach ($messages as $msg): ?>
        <div class="message" id="msg-<?php echo $msg['id']; ?>">
            <strong><?php echo $msg['name']; ?></strong>
            <small>
                (ID: <?php echo $msg['id']; ?> |
                <?php echo $msg['created_at']; ?> |
                IP: <?php echo $msg['ip_address']; ?>)
            </small>
            <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>

            <!-- Форма удаления -->
            <form method="POST" class="delete-form" onsubmit="return confirm('Удалить сообщение #<?php echo $msg['id']; ?>?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                <button type="submit" style="color: red;">Удалить</button>
            </form>

            <!-- Форма редактирования -->
            <form method="POST" class="update-form" onsubmit="return confirm('Редактировать сообщение #<?php echo $msg['id']; ?>?')">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                <textarea name="newMessage" rows="2" style="width: 300px;" placeholder="Новый текст сообщения"><?php echo htmlspecialchars($msg['message']); ?></textarea><br>
                <button type="submit">Обновить</button>
            </form>
        </div>
    <?php endforeach; ?>

    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo $searchMode ? '&searchString=' . urlencode($searchString) : ''; ?>">
                    ← Предыдущая
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $searchMode ? '&searchString=' . urlencode($searchString) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $searchMode ? '&searchString=' . urlencode($searchString) : ''; ?>">
                    Следующая →
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>