<?php
// 1. Создайте ассоциативный массив с вашими данными
$myProfile = [
    "name" => "Владимир",
    "birth_year" => 1993,
    "city" => "Волгоград",
    "hobbies" => ["программирование", "спорт", "музыка"] // Массив внутри массива!
];

// 2. Вычислите возраст (как в предыдущем задании)
$currentYear = date("Y");

// 3. Добавьте вычисленный возраст в массив
$myProfile["age"] = $currentYear - $myProfile["birth_year"];

// 4. Выведите информацию в читаемом виде
echo "<h2>Мой профиль</h2>";
echo "Имя: " . $myProfile["name"] . "<br>";
echo "Год рождения: " . $myProfile["birth_year"] . "<br>";
echo "Город: " . $myProfile["city"] . "<br>";
echo "Возраст: " . $myProfile["age"] . "<br>";

// 5. Выведите все хобби через цикл
echo "Хобби:<br>";
foreach ($myProfile["hobbies"] as $hobby) {
    echo "- $hobby<br>";
}

// 6. Проверьте, есть ли "программирование" в хобби
if (in_array("программирование", $myProfile["hobbies"])) {
    echo "<br>Я люблю программировать!<br>";
}
