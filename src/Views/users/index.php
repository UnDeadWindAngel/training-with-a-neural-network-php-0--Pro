<?php if(empty($_SESSION['user_id'])): ?>
<!-- Форма добавления пользователя -->
<form action="/public/register" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfTokenRegister; ?>">
    <input type="text" name="usermail" placeholder="Электронная почта" required><br>
    <input type="text" name="username" placeholder="Имя пользователя" required><br>
    <input type="password" name="userpassword" placeholder="Пароль" required><br>
    <input type="password" name="userconfirmpassword" placeholder="Повторите пароль" required><br>
    <button type="submit">Создать</button><br><br>
</form>

<!-- Форма авторизации -->
<form action="/public/login" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfTokenLogin; ?>">
    <input type="text" name="searchlogin" value="<?php echo htmlspecialchars($login); ?>" placeholder="Логин"><br>
    <input type="password" name="searchpassword" value="<?php echo htmlspecialchars($password); ?>" placeholder="Пароль"><br>
    <button type="submit">Авторизоваться</button><br><br>
</form>
<?php endif; ?>
<?php if (!empty($_SESSION['user_id'])): ?>
<!-- Форма выхода -->
<form action="/public/logout" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfTokenLogout; ?>">
    <button type="submit">Выйти</button>
</form>
<?php endif; ?>