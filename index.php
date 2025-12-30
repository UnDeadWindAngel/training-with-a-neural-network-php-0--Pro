<?php
// Это комментарий - он не выполняется

/*
  Многострочный комментарий.
  Сейчас мы выведем приветствие
*/

echo "Hello, World!<br>"; // echo выводит текст
print "Я изучаю PHP!<br>";

// Переменные начинаются с $
$name = "Алексей"; // Строка
$age = 25;         // Число
$isStudent = true; // Булево значение

echo "Меня зовут " . $name . "<br>"; // Точка соединяет строки
echo "Мне $age лет<br>"; // Можно вставлять переменные прямо в строку

// Простая математика
$x = 10;
$y = 5;
$sum = $x + $y;
echo "Сумма: $x + $y = $sum<br>";

// Условный оператор
if ($age >= 18) {
    echo "Я совершеннолетний<br>";
} else {
    echo "Я несовершеннолетний<br>";
}

// Цикл
for ($i = 1; $i <= 3; $i++) {
    echo "Счетчик: $i<br>";
}

//echo "<br>---Переменные---<br>";
//require_once ('practice1.php');

//echo "<br><br>---Массивы---";
//require_once ('practice2.php');

//echo "<br>---Формы---<br>";
//require_once ('practice3.php');

//echo "<br>---Формы дз---<br>";
//require_once ('practice4.php');

//echo "<br>---Функции---<br>";
//require_once ('functions.php');

//echo "<br>---Гостевая книга JSON---<br>";
//require_once ('guestbook.php');

echo "<br>---Гостевая книга MySQL---<br>";
require_once ('guestbook_mysql.php');
