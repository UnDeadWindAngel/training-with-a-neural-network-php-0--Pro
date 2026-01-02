<?php
namespace src\Repositories;

interface RepositoryInterface
{
    /**
     * Найти все записи
     */
    public function all(array $columns = ['*']);

    /**
     * Найти запись по ID
     */
    public function find(int $id);

    /**
     * Создать новую запись
     */
    public function create(array $data);

    /**
     * Обновить запись
     */
    public function update(int $id, array $data);

    /**
     * Удалить запись
     */
    public function delete(int $id);

    /**
     * Найти записи по условию
     */
    public function findBy(array $criteria);
}