<?php
// Класс - чертеж для создания объектов
class Message {
    // Свойства (атрибуты)
    private $id;
    private $name;
    private $text;
    private $createdAt;

    // Конструктор - вызывается при создании объекта
    public function __construct($name, $text, $id = null) {
        $this->name = htmlspecialchars(trim($name));
        $this->text = htmlspecialchars(trim($text));
        $this->createdAt = date('Y-m-d H:i:s');
        $this->id = $id;
    }

    // Методы (функции класса)
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getText() {
        return $this->text;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setText($newText) {
        $this->text = htmlspecialchars(trim($newText));
    }

    // Статический метод - вызывается без создания объекта
    public static function validate($name, $text) {
        return !empty(trim($name)) && !empty(trim($text));
    }
}

// Наследование
class ExtendedMessage extends Message {
    private $ip;

    public function __construct($name, $text, $ip) {
        parent::__construct($name, $text); // Вызов конструктора родителя
        $this->ip = $ip;
    }

    public function getIp() {
        return $this->ip;
    }
}

// Использование классов
echo "<h2>Демонстрация ООП</h2>";

// Создание объектов
$msg1 = new Message("Владимир", "Привет, мир!");
$msg2 = new ExtendedMessage("Анна", "Второе сообщение", "192.168.1.1");

echo "Автор: " . $msg1->getName() . "<br>";
echo "Текст: " . $msg1->getText() . "<br>";

// Изменение текста
$msg1->setText("Новый текст сообщения");
echo "Новый текст: " . $msg1->getText() . "<br>";

// Статический метод
if (Message::validate("Иван", "Текст")) {
    echo "Сообщение валидно!<br>";
}

// Проверка наследования
if ($msg2 instanceof Message) {
    echo "msg2 является экземпляром класса Message<br>";
}