
<?php
class CsrfValidator implements ValidatorInterface {
    public function validate(string $userCode, array $config): array {
        $errors = [];
        $score = 100;

        // Проверка генерации CSRF-токена в сессии
        if (isset($config['csrf'])) {
            // Генерация токена
            if (!preg_match('/\$_SESSION\s*\[\s*[\'"]csrf_token[\'"]\s*]\s*=\s*bin2hex\s*\(\s*random_bytes\s*\(\s*\d+\s*\)\s*\)/i', $userCode)) {
                $errors[] = "Отсутствует генерация CSRF-токена в сессии";
                $score -= 20;
            }

            // Токен в форме
            if (!preg_match('/<input[^>]+name\s*=\s*[\'"]csrf_token[\'"]/i', $userCode)) {
                $errors[] = "Отсутствует CSRF-токен в форме";
                $score -= 15;
            }

            // hash_equals сравнение
            if (!preg_match('/hash_equals\s*\(\s*\$_SESSION\s*\[\s*[\'"]csrf_token[\'"]\s*]\s*,\s*\$_POST\s*\[\s*[\'"]csrf_token[\'"]\s*]\s*\)/i', $userCode)) {
                $errors[] = "Отсутствует безопасная проверка CSRF-токена (hash_equals)";
                $score -= 25;
            }
        }

        // Проверка на атрибут SameSite для cookie
        if (isset($config['check_samesite'])) {
            // Любой способ установки cookie с SameSite
            if (
                !preg_match('/header\s*\(\s*[\'"]Set-Cookie\s*:\s*[^;]+;\s*SameSite=(Strict|Lax|None)/i', $userCode) &&
                !preg_match('/setcookie\s*\([\s\S]*?(SameSite\s*=>\s*[\'"](Strict|Lax|None)[\'"])/i', $userCode)
            ) {
                $errors[] = "Отсутствует атрибут SameSite для кук";
                $score -= 20;
            }
        }

        // Проверка Referer с parse_url
        if (isset($config['check_referer'])) {
            if (
                !preg_match('/parse_url\s*\(\s*\$_SERVER\s*\[\s*[\'"]HTTP_REFERER[\'"]\s*]\s*,\s*PHP_URL_HOST\s*\)/i', $userCode)
            ) {
                $errors[] = "Отсутствует проверка HTTP Referer с использованием parse_url";
                $score -= 15;
            }
        }

        // Экранирование XSS
        if (isset($config['xss'])) {
            if (!preg_match('/htmlspecialchars\s*\(/i', $userCode)) {
                $errors[] = "Отсутствует экранирование XSS";
                $score -= 10;
            }
        }

        // Prepared statements для SQL
        if (isset($config['sql_injection'])) {
            if (!preg_match('/->\s*prepare\s*\(/i', $userCode) && !preg_match('/->\s*bind(Param|Value)\s*\(/i', $userCode)) {
                $errors[] = "Отсутствует защита от SQL инъекций (использование prepared statements)";
                $score -= 20;
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'score' => max($score, 0),
        ];
    }
}

