<?php
namespace src\Providers;

use src\Core\ServiceProvider;
use src\Core\Cache\CacheInterface;
use src\Core\Cache\TaggableCacheInterface;
use src\Core\Cache\RedisCache;

class CacheProvider extends ServiceProvider
{
    public function register(): void
    {
        // Redis кеш (синглтон)
        $this->container->singleton(RedisCache::class, function() {
            return RedisCache::getInstance();
        });

        // Интерфейсы кеша
        $this->container->singleton(CacheInterface::class, function($c) {
            return $c->get(RedisCache::class);
        });

        $this->container->singleton(TaggableCacheInterface::class, function($c) {
            return $c->get(RedisCache::class);
        });

        // Алиасы для удобства
        $this->container->set('cache', function($c) {
            return $c->get(CacheInterface::class);
        });

        $this->container->set('redis.cache', function($c) {
            return $c->get(RedisCache::class);
        });
    }

    public function boot(): void
    {
        // TODO: Implement boot() method.
    }
}