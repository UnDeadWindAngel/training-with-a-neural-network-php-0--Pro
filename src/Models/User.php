<?php
/*
 * В связи с изменением архитектуры на Repository необходимость в моделях отпала.
 * Модели переделаны в DTO для обратной совместимости.
*/

namespace src\Models;

use PDO;

class User
{
    private $id;
    private $email;
    private $login;
    private $password;
    private $createdAt;
    private $ipAddress;

    // Геттеры и сеттеры
    public function getId()
    {
        return $this->id;
    }

    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function setLogin($login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress($ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function __construct()
    {
    }
}