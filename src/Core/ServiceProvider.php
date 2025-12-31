<?php
namespace src\Core;

abstract class ServiceProvider
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function register(): void;

    abstract public function boot(): void;
}