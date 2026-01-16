
<!-- Форма добавления -->
<form action="/public/messages" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfTokenForAddForm; ?>">
    <input type="text" name="name" placeholder="Имя" required><br>
    <textarea name="message" placeholder="Сообщение" required></textarea><br>
    <button type="submit">Отправить</button>
</form>

<!-- Форма поиска -->
<form action="/public/messages" method="GET">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Поиск">
    <button type="submit">Найти</button>
</form>

<!-- Список сообщений -->
<h2>Сообщений: <?php echo $data['total']; ?></h2>

<?php foreach ($data['messages'] as $msg): ?>
    <div class="message">
        <strong><?php echo htmlspecialchars($msg['name']); ?></strong>
        <small><?php echo $msg['created_at']; ?></small>
        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>

        <?php if(!empty($_SESSION['user_id'])):?>
            <form action="/public/messages/<?php echo $msg['id']; ?>/delete" method="POST" style="display:inline;">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="csrf_token" value="<?php echo $securityService->generateCsrfToken(); ?>">
                <button type="submit">Удалить</button>
            </form>

            <form action="/public/messages/<?php echo $msg['id']; ?>/update" method="POST" style="display:inline;">
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="csrf_token" value="<?php echo $securityService->generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                <input type="text" name="newMessage" placeholder="Новый текст">
                <button type="submit">Обновить</button>
            </form>
        <?php endif;?>
    </div>
<?php endforeach; ?>

<!-- Пагинация -->
<?php if ($data['totalPages'] > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $data['totalPages']; $i++): ?>
            <a href="/public/messages/?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=messages"
               class="<?php echo $i == $data['currentPage'] ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>