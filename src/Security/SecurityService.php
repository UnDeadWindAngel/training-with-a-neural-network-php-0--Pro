<?php
namespace src\Security;

class SecurityService
{
    // Защита от SQL-инъекций (уже есть в PDO, но добавим валидацию)
    public function validateSqlInput(string $input): bool
    {
        // Черный список опасных SQL конструкций
        $blacklist = [
            '/\bUNION\b.*\bSELECT\b/i',
            '/\bINSERT\b.*\bINTO\b/i',
            '/\bDELETE\b.*\bFROM\b/i',
            '/\bDROP\b/i',
            '/\bALTER\b/i',
            '/\bEXEC(UTE)?\b/i',
            '/\bXP_/i',
            '/;.*--/',
            '/\/\*.*\*\//',
        ];

        foreach ($blacklist as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }

        return true;
    }

    // Защита от XSS
    public function sanitizeHtml(string $html, array $allowedTags = []): string
    {
        $config = \HTMLPurifier_Config::createDefault();

        if (!empty($allowedTags)) {
            $config->set('HTML.Allowed', implode(',', $allowedTags));
        }

        $config->set('HTML.AllowedAttributes', 'a.href,a.title,img.src,img.alt');
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.RemoveEmpty', true);

        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($html);
    }

    // Защита от CSRF (расширенная)
    public function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = [
            'token' => $token,
            'created_at' => time(),
            'expires_at' => time() + 3600, // 1 час
        ];

        // Ограничиваем количество токенов в сессии
        if (count($_SESSION['csrf_tokens']) > 10) {
            array_shift($_SESSION['csrf_tokens']);
        }

        return $token;
    }

    public function validateCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            return false;
        }

        $tokenData = $_SESSION['csrf_tokens'][$token];

        // Проверяем срок действия
        if (time() > $tokenData['expires_at']) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }

        // Одноразовый токен
        unset($_SESSION['csrf_tokens'][$token]);
        return true;
    }

    // Защита от подбора паролей (rate limiting)
    public function checkRateLimit(string $ip, string $action, int $maxAttempts = 5, int $period = 300): bool
    {
        $key = "ratelimit:$action:$ip";

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time(),
                'blocked_until' => 0
            ];
            return true;
        }

        $data = $_SESSION[$key];

        // Проверяем блокировку
        if (time() < $data['blocked_until']) {
            return false;
        }

        // Сбрасываем счетчик если период истек
        if (time() - $data['first_attempt'] > $period) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time(),
                'blocked_until' => 0
            ];
            return true;
        }

        // Увеличиваем счетчик
        $data['attempts']++;

        // Блокируем если превышено
        if ($data['attempts'] > $maxAttempts) {
            $data['blocked_until'] = time() + 900; // 15 минут блокировки
            $_SESSION[$key] = $data;
            return false;
        }

        $_SESSION[$key] = $data;
        return true;
    }

    // Валидация пароля по политике безопасности
    public function validatePasswordPolicy(string $password): array
    {
        $errors = [];

        // Минимальная длина
        if (strlen($password) < 12) {
            $errors[] = 'Пароль должен содержать минимум 12 символов';
        }

        // Проверка сложности
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну заглавную букву';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну строчную букву';
        }

        if (!preg_match('/\d/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы одну цифру';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Пароль должен содержать хотя бы один специальный символ';
        }

        // Проверка на утечки (можно интегрировать с HaveIBeenPwned API)
        if ($this->isPasswordLeaked($password)) {
            $errors[] = 'Этот пароль был скомпрометирован в утечках данных';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => $this->calculatePasswordScore($password)
        ];
    }

    private function isPasswordLeaked(string $password): bool
    {
        // Используем k-анонимность через HaveIBeenPwned API
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        $response = @file_get_contents(
            "https://api.pwnedpasswords.com/range/{$prefix}",
            false,
            stream_context_create(['http' => ['timeout' => 2]])
        );

        if ($response) {
            $hashes = explode("\n", $response);
            foreach ($hashes as $line) {
                list($hashSuffix, $count) = explode(':', trim($line));
                if (strtoupper($hashSuffix) === $suffix) {
                    return true;
                }
            }
        }

        return false;
    }

    private function calculatePasswordScore(string $password): int
    {
        $score = 0;

        // Длина
        $score += min(strlen($password) * 4, 25);

        // Разнообразие символов
        $charTypes = 0;
        if (preg_match('/[a-z]/', $password)) $charTypes++;
        if (preg_match('/[A-Z]/', $password)) $charTypes++;
        if (preg_match('/\d/', $password)) $charTypes++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $charTypes++;

        $score += ($charTypes - 1) * 10;

        // Штраф за повторяющиеся символы
        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 15;
        }

        // Штраф за последовательности
        if (preg_match('/123|234|345|456|567|678|789|890|abc|bcd|cde|def/', strtolower($password))) {
            $score -= 20;
        }

        return max(0, min(100, $score));
    }
}