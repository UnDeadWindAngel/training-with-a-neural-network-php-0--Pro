<?php
namespace src\Core;

abstract class ServiceProvider
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    // Метод для регистрации сервисов в контейнере
    abstract public function register(): void;

    // Опционально: метод для запуска после регистрации всех сервисов
    abstract public function boot(): void;
}