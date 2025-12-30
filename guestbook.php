<?php
// Функции
function loadMessages() {
    if (!file_exists('messages.json')) {
        return [];
    }
    $data = file_get_contents('messages.json');
    return json_decode($data, true) ?: [];
}

function saveMessage($name, $message) {
    $messages = loadMessages();

    $newMessage = [
        'id' => uniqid(),
        'name' => htmlspecialchars(trim($name)),
        'message' => htmlspecialchars(trim($message)),
        'date' => date("Y-m-d H:i:s"),
        'ip' => $_SERVER['REMOTE_ADDR']
    ];

    $messages[] = $newMessage;

    // Оставляем только 50 последних сообщений
    if (count($messages) > 50) {
        $messages = array_slice($messages, -50);
    }

    file_put_contents(
        'messages.json',
        json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function deleteMessage($id) {

    $messages = loadMessages();

    foreach ($messages as $key => $msg) {
        if ($msg['id'] == $id) {
            unset($messages[$key]);
        }
    }

    // Переиндексация массива
    $messages = array_values($messages);

    file_put_contents(
        'messages.json',
        json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

// Обработка формы добавления
if ($_SERVER["REQUEST_METHOD"] == "POST"){
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'jsonadd':
                if (!empty($_POST['name']) && !empty($_POST['message'])) {
                    saveMessage($_POST['name'], $_POST['message']);
                    header("Location: " . $_SERVER['PHP_SELF']); // Перенаправление для очистки POST
                    exit;
                }
                break;

            case 'deletejson':
                if (!empty($_POST['id'])) {
                    deleteMessage($_POST['id']);
                    header("Location: " . $_SERVER['PHP_SELF']); // Перенаправление для очистки POST
                    exit;
                }
                break;
        }
    }
}

// Загрузка сообщений
$allMessages = loadMessages();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Гостевая книга</title>
</head>
<body>
<h1>Гостевая книга</h1>

<!-- Форма добавления сообщения -->
<form method="POST">
    <input type="hidden" name="action" value="jsonadd">
    <input type="text" name="name" placeholder="Ваше имя" required><br><br>
    <textarea name="message" placeholder="Ваше сообщение" rows="4" required></textarea><br><br>
    <button type="submit">Отправить</button>
</form>

<hr>

<!-- Вывод сообщений -->
<h2>Сообщения (<?php echo count($allMessages); ?>):</h2>

<?php if (empty($allMessages)): ?>
    <p>Пока нет сообщений. Будьте первым!</p>
<?php else: ?>
    <?php foreach (array_reverse($allMessages) as $msg): ?>
        <div style="border: 1px solid #ccc; margin: 10px; padding: 10px;">
            <strong><?php echo $msg['name']; ?></strong>
            <small>(<?php echo $msg['date']; ?>)</small>
            <p><?php echo nl2br($msg['message']); ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<hr>

<!-- Форма удаления сообщения -->
<form method="POST">
    <input type="hidden" name="action" value="deletejson">
    <input type="text" name="id" placeholder="id сообщения" required><br><br>
    <button type="submit">Отправить</button>
</form>

</body>
</html>