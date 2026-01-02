<?php
namespace src\Repositories;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Найти пользователя по email
     */
    public function findByEmail(string $email);

    /**
     * Найти пользователя по логину
     */
    public function findByLogin(string $login);
}