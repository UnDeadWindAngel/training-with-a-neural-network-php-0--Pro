<?php

namespace src\Core\Cache;

interface CacheInterface
{
    /**
     * Получает значение по ключу
     *
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию, если ключ не найден
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Устанавливает значение по ключу
     *
     * @param string $key Ключ
     * @param mixed $value Значение
     * @param int|null $ttl Время жизни в секундах
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool;

    /**
     * Удаляет значение по ключу
     *
     * @param string $key Ключ
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Очищает весь кэш
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Получает несколько значений по ключам
     *
     * @param array $keys Массив ключей
     * @param mixed $default Значение по умолчанию для отсутствующих ключей
     * @return array
     */
    public function getMultiple(array $keys, $default = null): array;

    /**
     * Устанавливает несколько значений
     *
     * @param array $values Ассоциативный массив [ключ => значение]
     * @param int|null $ttl Время жизни в секундах
     * @return bool
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Проверяет существование ключа
     *
     * @param string $key Ключ
     * @return bool
     */
    public function has(string $key): bool;
}