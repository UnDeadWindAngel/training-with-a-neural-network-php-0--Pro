<?php
/*
 * В связи с изменением архитектуры на Repository необходимость в моделях отпала.
 * Модели переделаны в DTO для обратной совместимости.
*/
namespace src\Models;

class Message
{
    private $id;
    private $name;
    private $message;
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

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getCreatedAt()
    {
        return new \DateTime($this->createdAt);
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