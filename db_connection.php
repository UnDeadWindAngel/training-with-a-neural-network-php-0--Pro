<?php
class Database {
    private $host = '127.127.126.50:3306';
    private $dbname = 'guestbook_db';
    private $username = 'root'; // По умолчанию в OpenServer
    private $password = ''; // По умолчанию пустой пароль
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $e) {
            die("Ошибка подключения: " . $e->getMessage());
        }

        return $this->conn;
    }
}

// Использование
$database = new Database();
$db = $database->connect();