<?php

namespace src\Models;

use PDO;

class User
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    //проверка существования пользователя
    public function exists($email, $login)
    {
        $sql = "SELECT id FROM users WHERE mail = ? OR login = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, trim($email), PDO::PARAM_STR);
        $stmt->bindValue(2, trim($login), PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function create($mail, $login, $password, $ip)
    {
        if ($this->exists($mail, $login)) {
            return ['error' => 'Пользователь уже существует'];
        }

        $sql = "INSERT INTO users (mail, login, password, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, filter_var(trim($mail), FILTER_VALIDATE_EMAIL), PDO::PARAM_STR);
        $stmt->bindValue(2, trim($login), PDO::PARAM_STR);
        $stmt->bindValue(3, $password, PDO::PARAM_STR);
        $stmt->bindValue(4, $ip, PDO::PARAM_STR);
        $success = $stmt->execute();

        return $success ? ['id' => $this->db->lastInsertId()] : ['error' => 'Ошибка создания'];
    }

    public function findByLogin($login)
    {
        $sql = "SELECT * FROM users WHERE login = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, trim($login), PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function delete($userId ,$id)
    {
        if ($userId == $id) {
            return false;
        }

        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function validatePassword($password)
    {
        // Минимальная длина 8 символов, хотя бы одна цифра и буква
        return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/\d/', $password);
    }

    /* заложено для изменения параметров пользователя если появится необходимость

    public function update($id, $newMessage) {
         $sql = "UPDATE messages SET message = ? WHERE id = ?";
         $stmt = $this->db->prepare($sql);
         $stmt->bindValue(1,  htmlspecialchars(trim($newMessage)), PDO::PARAM_STR);
         $stmt->bindValue(2, $id, PDO::PARAM_INT);
         return $stmt->execute();
     }*/
}