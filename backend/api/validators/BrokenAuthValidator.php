<?php
class BrokenAuthValidator implements ValidatorInterface {
    public function validate(string $userCode, array $config): array {
        $errors = [];
        $score = 100;

        // Проверка хеширования паролей
        if (!preg_match('/password_hash\(/', $userCode)) {
            $errors[] = "Пароли не хешируются";
            $score -= 30;
        }

        // Проверка сложности пароля
        if (isset($config['password_policy'])) {
            $policy = $config['password_policy'];
            $pattern = $this->buildPasswordRegex($policy);
            if (!preg_match($pattern, $userCode)) {
                $errors[] = "Не соответствует политике паролей";
                $score -= 20;
            }
        }

        // Проверка блокировки аккаунта
        if (isset($config['max_attempts'])) {
            $attemptCheck = preg_match('/failed_attempts\s*>=/', $userCode);
            $lockCheck = preg_match('/account_locked\s*=\s*true/', $userCode);
            
            if (!$attemptCheck || !$lockCheck) {
                $errors[] = "Нет защиты от брутфорса";
                $score -= 25;
            }
        }

        // Проверка сессий
        if (!preg_match('/session_regenerate_id\(true\)/', $userCode)) {
            $errors[] = "Нет регенерации session ID";
            $score -= 15;
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'score' => max($score, 0)
        ];
    }

    private function buildPasswordRegex(array $policy): string {
        $parts = [];
        if ($policy['min_length'] ?? 8) {
            $parts[] = '.{'.$policy['min_length'].',}';
        }
        if ($policy['require_upper']) $parts[] = '(?=.*[A-Z])';
        if ($policy['require_digit']) $parts[] = '(?=.*\d)';
        if ($policy['require_special']) $parts[] = '(?=.*[\W_])';
        
        return '/^'.implode('', $parts).'$/';
    }
}