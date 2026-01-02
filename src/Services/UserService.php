<?php
namespace src\Services;

use src\Repositories\UserRepositoryInterface;

class UserService
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Регистрация нового пользователя
     */
    public function register(string $email, string $login, string $password, string $ip)
    {
        // Проверка, что пользователь с таким email или логином не существует
        $existingByEmail = $this->userRepository->findByEmail($email);
        if ($existingByEmail) {
            throw new \Exception('Пользователь с таким email уже существует');
        }

        $existingByLogin = $this->userRepository->findByLogin($login);
        if ($existingByLogin) {
            throw new \Exception('Пользователь с таким логином уже существует');
        }

        // Создание пользователя
        return $this->userRepository->create([
            'mail' => $email,
            'login' => $login,
            'password' => $password,
            'ip_address' => $ip
        ]);
    }

    /**
     * Аутентификация пользователя
     */
    public function authenticate(string $login, string $password)
    {
        $user = $this->userRepository->findByLogin($login);

        if (!$user) {
            throw new \Exception('Пользователь не найден');
        }

        if (!password_verify($password, $user['password'])) {
            throw new \Exception('Неверный пароль');
        }

        return $user;
    }

    /**
     * Найти пользователя по ID
     */
    public function findUser(int $id)
    {
        return $this->userRepository->find($id);
    }
}