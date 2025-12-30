<?php
// Обработка данных формы
$userName = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" AND !empty($_POST["form1"]) AND $_POST["form1"] == "true") {

    $_POST["form1"]="false";

    // Данные пришли из формы
    $userName = htmlspecialchars($_POST["username"]); // Защита от XSS
    $userAge = (int)$_POST["age"]; // Приведение к числу

    echo "<h3>Привет, $userName!</h3>";
    echo "Тебе $userAge лет<br>";

    // Проверка возраста
    if ($userAge >= 18) {
        echo "Доступ разрешен";
    } else {
        echo "Доступ запрещен";
    }

    echo "<hr>";
}
?>

<!-- HTML форма -->
<h2>Введите данные</h2>
<form method="POST" action="">
    <input type="text" name="form1" value="true" hidden="hidden">

    <label>Имя:</label>
    <input type="text" name="username" value="<?php echo $userName; ?>">
    <br><br>

    <label>Возраст:</label>
    <input type="number" name="age" min="1" max="120">
    <br><br>

    <label>Город:</label>
    <select name="city">
        <option value="Волгоград">Волгоград</option>
        <option value="Москва">Москва</option>
        <option value="Санкт-Петербург">Санкт-Петербург</option>
    </select>
    <br><br>

    <input type="submit" value="Отправить">
</form>