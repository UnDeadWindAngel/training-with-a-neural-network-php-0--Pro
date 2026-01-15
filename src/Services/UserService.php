<?php
namespace src\Services;

use src\Repositories\UserRepositoryInterface;
use src\Core\Cache\CacheInterface;
use src\Security\SecurityService;
use Exception;

class UserService
{
    private UserRepositoryInterface $userRepository;
    private CacheInterface $cache;
    private int $cacheTtl = 300;
    private SecurityService $security;

    public function __construct(
        UserRepositoryInterface $userRepository,
        CacheInterface $cache,
        SecurityService $security
    )
    {
        $this->userRepository = $userRepository;
        $this->cache = $cache;
        $this->security = $security;
    }

    /**
     * Регистрация нового пользователя
     * @throws Exception
     */
    public function register(string $email, string $login, string $password, string $ip)
    {
        // Проверка rate limit для регистрации
        if (!$this->security->checkRateLimit($ip, 'user_register', 3, 3600)) {
            throw new \RuntimeException('Превышен лимит регистраций. Попробуйте позже.');
        }

        // Валидация email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Некорректный email адрес');
        }

        // Санитизация логина
        $sanitizedLogin = $this->security->sanitizeHtml($login, []); // Не разрешаем HTML теги в логине

        // Проверка логина на SQL инъекции
        if (!$this->security->validateSqlInput($sanitizedLogin)) {
            throw new \InvalidArgumentException('Недопустимые символы в логине');
        }

        // Валидация пароля через SecurityService
        $passwordValidation = $this->security->validatePasswordPolicy($password);
        if (!$passwordValidation['valid']) {
            throw new \InvalidArgumentException(
                'Пароль не соответствует требованиям безопасности: ' .
                implode(', ', $passwordValidation['errors'])
            );
        }
        // Проверка, что пользователь с таким email или логином не существует
        $existingByEmail = $this->userRepository->findByEmail($email);
        if ($existingByEmail) {
            throw new \Exception('Пользователь с таким email уже существует');
        }

        $existingByLogin = $this->userRepository->findByLogin($sanitizedLogin);
        if ($existingByLogin) {
            throw new \Exception('Пользователь с таким логином уже существует');
        }

        // Создание пользователя
        $userId = $this->userRepository->create([
            'mail' => $email,
            'login' => $sanitizedLogin,
            'password' => $password,
            'ip_address' => $ip
        ]);

        // Инвалидируем кеш пользователей
        $this->invalidateUsersCache();

        return $userId;
    }

    /**
     * Аутентификация пользователя
     * @throws Exception
     */
    public function authenticate(string $login, string $password, string $ip)
    {
        // Проверка rate limit для аутентификации
        if (!$this->security->checkRateLimit($ip, 'user_auth', 5, 900)) {
            throw new Exception('Слишком много попыток входа. Попробуйте через 15 минут.');
        }

        // Санитизация логина
        $sanitizedLogin = $this->security->sanitizeHtml($login, []);

        // Проверка на SQL инъекции
        if (!$this->security->validateSqlInput($sanitizedLogin)) {
            throw new Exception('Недопустимые символы в логине');
        }

        $user = $this->userRepository->findByLogin($sanitizedLogin);

        if (!$user) {
            throw new \Exception('Пользователь не найден');
        }

        if (!password_verify($password, $user['password'])) {
            throw new \Exception('Неверный пароль');
        }

        // Сбрасываем счетчик попыток при успешной аутентификации
        $this->resetRateLimit($ip, 'user_auth');

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();

        return $user;
    }

    /**
     * Найти пользователя по ID
     */
    public function findUser(int $id)
    {
        $cacheKey = "user:{$id}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $user = $this->userRepository->find($id);

        if ($user) {
            // Убираем чувствительные данные перед кешированием
            unset($user['password'], $user['ip_address']);
            $this->cache->set($cacheKey, $user, $this->cacheTtl);

            // Добавляем теги для инвалидации
            $this->cache->tag($cacheKey, ['users', "user:{$id}"]);
        }

        return $user;
    }

    /**
     * Проверка сложности пароля
     */
    public function checkPasswordStrength(string $password): array
    {
        return $this->security->validatePasswordPolicy($password);
    }

    /**
     * Сброс rate limit для указанного действия
     */
    private function resetRateLimit(string $ip, string $action): void
    {
        $key = "ratelimit:{$action}:{$ip}";
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Инвалидация кеша пользователей
     */
    private function invalidateUsersCache(): void
    {
        // Удаляем все ключи, связанные с пользователями
        if ($this->cache instanceof \src\Core\Cache\TaggableCacheInterface) {
            $this->cache->invalidateTag('users');
        } else {
            // Удаляем по паттерну, если RedisCache
            if (method_exists($this->cache, 'deleteByPattern')) {
                $this->cache->deleteByPattern('user:*');
            }
        }
    }

    /**
     * Очистить кеш пользователей
     */
    public function clearUserCache(): bool
    {
        return $this->cache->clear();
    }
}