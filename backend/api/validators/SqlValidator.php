<?php

class SqlValidator implements ValidatorInterface
{
    public function validate(string $userCode, array $config): array
    {
        $errors = [];
        $feedback = [];
        $warnings = [];
        $score = 100;
        $hints = $config['hints'] ?? [];

        // 1. Основная проверка шаблона
        if (isset($config['template'])) {
            $this->validateExactTemplate($userCode, $config['template'], $errors, $feedback, $score);
        }
        
        // 2. Проверка типа SQL-оператора
        $this->validateQueryType($userCode, $config, $errors, $feedback, $score);

        // 3. Проверка безопасности кода
        $this->validateCode($userCode, $config, $errors, $feedback, $score);

        // 4. Проверка типа защиты
        $protectionType = $config['protection_type'] ?? null;
        switch ($protectionType) {
            case 'prepared_statements':
                $this->validatePreparedStatements($userCode, $errors, $feedback, $score);
                break;
            case 'whitelist':
                $this->validateWhitelist($userCode, $config, $errors, $feedback, $score);
                break;
        }

        // 5. Проверка дополнительных правил из конфига
        $this->validateConfigRules($userCode, $config, $errors, $feedback, $score);

        // 6. Общая проверка безопасности
        $this->validateSecurity($userCode, $config, $errors, $feedback, $score);

        // Итоговая обработка
        $isValid = empty($errors);
        $isCorrect = $isValid;
        $finalScore = max(0, $score);

        return [
            'is_valid' => $isValid, 
            'is_correct' => $isCorrect,
            'is_completed' => $isValid,
            'score' => $finalScore,
            'experience_earned' => $this->calculateExperience($finalScore),
            'errors' => array_values(array_unique($errors)),
            'feedback' => array_values(array_unique($feedback)),
            'warnings' => array_values(array_unique($warnings)),
            'hints' => array_unique(array_merge($hints, $this->generateHints($errors, $config))),
        ];
    }

    private function calculateExperience(int $score): int
    {
        return (int) round($score); 
    }

    private function validateExactTemplate(string $code, string $template, array &$errors, array &$feedback, int &$score)
    {
        $normalizedCode = strtoupper(trim(preg_replace('/\s+/', ' ', $code)));
        $normalizedTemplate = strtoupper(trim(preg_replace('/\s+/', ' ', $template)));
    
        // Пропускаем экранированный символ \\? в коде пользователя
        if (preg_match('/\\\\\?/', $normalizedCode)) {
            return; // Пропускаем, если найден экранированный вопросительный знак
        }
    
        // Проверяем наличие ? в коде пользователя
        if (strpos($normalizedCode, '?') !== false) {
            return; // Если ? присутствует в коде, считаем, что он соответствует шаблону
        }
    
        // Если ? нет, то добавляем ошибку
        $errors[] = "Ваш SQL-запрос отличается от ожидаемого шаблона.";
        $score -= 30; // уменьшаем баллы за несоответствие шаблону
    }
    
    private function validateQueryType(string $code, array $config, array &$errors, array &$feedback, int &$score)
    {
        if (!isset($config['template'])) return;
    
        // Извлекаем тип запроса из шаблона
        $requiredType = preg_match('/\b(CALL|SELECT|INSERT)\b/i', $config['template'], $matches)
            ? strtoupper($matches[1])
            : null;
    
        // Если тип запроса был найден и в коде не присутствует требуемый тип запроса
        if ($requiredType && !preg_match("/\b{$requiredType}\b/i", $code)) {
            $errors[] = "Требуется SQL-оператор типа: {$requiredType}";
            $score = 0;  // Обнуляем очки, если тип не совпадает
        }
    }
    

    private function validateCode(string $code, array $config, array &$errors, array &$feedback, int &$score): void
    {
        $code = trim($code);

        // 1. Проверка запрещённых паттернов
        foreach ($config['forbidden_patterns'] ?? [] as $pattern) {
            $pattern = $this->normalizeRegexPattern($pattern);
            if (preg_match($pattern, $code)) {
                $errors[] = "Запрещённый паттерн найден: `" . $this->cleanPattern($pattern) . "`";
                $score -= 30;
            }
        }

        foreach ($config['required_patterns'] ?? [] as $pattern) {
            $pattern = $this->normalizeRegexPattern($pattern);
            if (!preg_match($pattern, $code)) {
                $feedback[] = "Отсутствует обязательный элемент: `" . $this->cleanPattern($pattern) . "`";
                $score -= 20;
            }
        }

        // 3. Проверка наличия конструкции (для mustContain)
        foreach ($config['mustContain'] ?? [] as $substr) {
            if (stripos($code, $substr) === false) {
                $feedback[] = "Ожидается наличие конструкции: `$substr`";
                $score -= 10;
            }
        }
    }

    private function validatePreparedStatements(string $code, array &$errors, array &$feedback, int &$score)
    {
        $preparedCheck = preg_match('/->prepare\s*\(/i', $code)
            && preg_match('/\?/i', $code)
            && preg_match('/->execute\s*\(/i', $code);

        if (!$preparedCheck) {
            $errors[] = "Требуется полный цикл подготовленных выражений (prepare -> bind -> execute)";
            $feedback[] = "Используйте связывание параметров через prepare() и execute()";
            $score = min($score, 50);
        }
    }

    private function validateWhitelist(string $code, array $config, array &$errors, array &$feedback, int &$score)
    {
        $whitelistCheck = preg_match('/in_array\s*\(/i', $code)
            && preg_match('/\$_GET\s*\[/i', $code)
            && preg_match('/\b(allowed|valid)\w+/i', $code);

        if (!$whitelistCheck) {
            $errors[] = "Требуется реализация белого списка для входных данных";
            $feedback[] = "Используйте in_array() с предопределенным списком допустимых значений";
            $score = min($score, 50);
        }
    }

    private function validateSecurity(string $code, array $config, array &$errors, array &$feedback, int &$score)
    {
        $dangerousPatterns = [
            '/\b(OR|AND)\s+1\s*=\s*1\b/i',
            '/;\s*(DROP|DELETE|INSERT|UPDATE|ALTER)\s+/i',
            '/UNION\s+SELECT/i',
            '/--\s*$/'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                $errors[] = "Обнаружена критическая уязвимость SQLi";
                $feedback[] = "Немедленно устраните опасный паттерн: $pattern";
                $score = 0;
                return; // Прекращаем проверку после первой найденной уязвимости
            }
        }
    }

    private function generateHints(array $errors, array $config): array
    {
        $hints = [];
        foreach ($errors as $error) {
            if (str_contains($error, 'SQL-запрос')) {
                $hints[] = "ШАБЛОН: " . str_replace('{{input}}', '?', $config['template']);
            } elseif (str_contains($error, 'белый список')) {
                $hints[] = "Пример белого списка: \$allowed = ['val1','val2']; if (in_array(\$input, \$allowed))";
            } elseif (str_contains($error, 'процедуры')) {
                $hints[] = "Пример вызова: \$stmt = \$pdo->prepare('CALL proc(?)'); \$stmt->execute([\$input]);";
            }
        }
        return $hints;
    }

    private function normalizeRegexPattern(string $pattern): string
    {
        // Если паттерн уже имеет делимитаторы, возвращаем как есть
        if (preg_match('#^/(.*)/[a-z]*$#', $pattern)) {
            return $pattern;
        }
    
        // Экранируем только специальные символы, кроме ?
        $pattern = preg_quote($pattern, '/');
    
        // Обрабатываем особые символы, чтобы игнорировать экранированный ?
        // Убираем экранирование для \\? чтобы не было ошибок
        $pattern = str_replace('\\\?', '?', $pattern);
    
        return '/' . $pattern . '/i';
    }

    private function cleanPattern(string $pattern): string
    {
        return preg_replace('#^/(.*)/[a-z]*$#', '$1', $pattern);
    }

    private function validateConfigRules(string $code, array $config, array &$errors, array &$feedback, int &$score)
    {
        foreach ($config['required_patterns'] ?? [] as $pattern) {
            // Добавляем делимитаторы, если их нет
            $pattern = $this->normalizeRegexPattern($pattern);
            if (!preg_match($pattern, $code)) {
                $errors[] = "Обязательный элемент отсутствует: " . $this->cleanPattern($pattern);
                $score = 0;
            }
        }
    
        foreach ($config['forbidden_patterns'] ?? [] as $pattern) {
            // Добавляем делимитаторы, если их нет
            $pattern = $this->normalizeRegexPattern($pattern);
            if (preg_match($pattern, $code)) {
                $errors[] = "Обнаружен запрещенный элемент: " . $this->cleanPattern($pattern);
                $feedback[] = "Удалите из кода: " . $this->cleanPattern($pattern);
                $score = min($score, 50);
            }
        }
    }
}
