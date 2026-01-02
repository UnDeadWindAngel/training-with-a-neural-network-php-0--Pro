<?php
namespace src\Repositories;

interface MessageRepositoryInterface extends RepositoryInterface
{
    /**
     * Найти сообщения по поисковому запросу
     */
    public function search(string $query);

    /**
     * Получить сообщения с пагинацией
     */
    public function paginate(int $perPage = 10, int $page = 1);
}