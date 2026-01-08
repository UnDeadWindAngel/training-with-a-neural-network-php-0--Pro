<?php
namespace src\Core\Cache;

use src\Core\Cache\CacheInterface;

interface TaggableCacheInterface extends CacheInterface
{
    /**
     * Добавляет теги к ключу
     *
     * @param string $key Ключ
     * @param array $tags Массив тегов
     * @return bool
     */
    public function tag(string $key, array $tags): bool;

    /**
     * Инвалидирует все ключи с указанным тегом
     *
     * @param string $tag Тег
     * @return bool
     */
    public function invalidateTag(string $tag): bool;
}