<?php
namespace src\Core;

interface ContainerInterface
{
    /**
     * Регистрирует сервис в контейнере
     */
    public function set(string $id, $value): void;

    /**
     * Получает сервис из контейнера
     */
    public function get(string $id);

    /**
     * Проверяет наличие сервиса
     */
    public function has(string $id): bool;

    /**
     * Регистрирует фабрику для создания сервиса
     */
    public function factory(string $id, callable $factory): void;

    /**
     * Регистрирует синглтон (один экземпляр на все приложение)
     */
    public function singleton(string $id, $value): void;
}