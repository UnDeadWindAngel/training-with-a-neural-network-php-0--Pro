<?php
// Обработка данных формы
$likedFilms = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" AND !empty($_POST["form2"]) AND $_POST["form2"] == "true" ) {

    $_POST["form2"] = "false";

    // Данные пришли из формы
    $likedFilms = explode(",", htmlspecialchars($_POST["likedfilms"])); // Защита от XSS

    echo "Мои любимые фильмы: <br>";
    echo "<ol>";
    foreach ($likedFilms as $likedFilm) {
        echo "<li>-$likedFilm</li>" . "<br>";
    }
    echo "</ol>";
    echo "<hr>";
}
?>

<!-- HTML форма -->
<h2>Введите данные</h2>
<form method="POST" action="">
    <input type="text" name="form2" value="true" hidden="hidden">

    <label>Любимые фильмы:</label>
    <input type="text" name="likedfilms" value="" alt="Введите список любимых фильмов через запятую">
    <br><br>

    <input type="submit" value="Отправить">
</form>