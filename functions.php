<?php
// 1. Функция для безопасного получения данных из формы
function getSafeValue($fieldName, $default = '') {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST[$fieldName])) {
        return htmlspecialchars(trim($_POST[$fieldName]));
    }
    return $default;
}

// 2. Функция проверки возраста
function checkAccess($age) {
    if ($age >= 18) {
        return "Доступ разрешен";
    } else {
        return "Доступ запрещен";
    }
}

// 3. Функция форматирования списка фильмов
function formatFilmsList($filmsString) {
    $filmsArray = explode(",", $filmsString);
    $result = "<ol>";
    foreach ($filmsArray as $film) {
        $cleanFilm = htmlspecialchars(trim($film));
        if (!empty($cleanFilm)) {
            $result .= "<li>$cleanFilm</li>";
        }
    }
    $result .= "</ol>";
    return $result;
}

// Теперь используем эти функции в обработке форм:
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Для формы 1
    if (isset($_POST['form1'])) {
        $userName = getSafeValue('username');
        $userAge = (int)getSafeValue('age', 0);

        echo "<h3>Привет, $userName!</h3>";
        echo "Тебе $userAge лет<br>";
        echo checkAccess($userAge);
    }

    // Для формы 2
    if (isset($_POST['form2'])) {
        $filmsString = getSafeValue('likedfilms');
        echo "Мои любимые фильмы: <br>";
        echo formatFilmsList($filmsString);
    }
}