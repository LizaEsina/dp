<?php
class XssValidator implements ValidatorInterface
{
    public function validate(string $userCode, array $config): array
    {
        $errors = [];
        $score = 100;
        $hints = [];
        $userCode = urldecode($userCode);  
        $userCode = html_entity_decode($userCode);
    
        $isAttack = isset($config['type']) && $config['type'] === 'attack_simulation';
        $xssType = $config['xss_type'] ?? null;
    
        $weights = $config['rule_weights'] ?? [
            'expected_substring_in_url' => 40,
            'expected_stored_value' => 40,
            'should_trigger_alert' => 30,
            'html_escaping' => 40,
            'safe_dom_manipulation' => 30,
        ];
    
        $rules = $config['validation_rules'] ?? [];

        // Запретить слишком очевидный payload
        if (preg_match('/<script[^>]*>.*?alert\s*\(.*?\).*?<\/script>/is', $userCode)) {
            return [
                'is_valid' => false,
                'errors' => ["Слишком очевидный XSS payload: <script>alert(...)</script> не допускается."],
                'score' => 0,
                'generated_hint' => "Попробуйте использовать другие XSS-техники: `<img src=x onerror=alert(1)>`, `onmouseover`, `svg`, `iframe`, `javascript:` и т.д."
            ];
        }

        // Общая проверка на вредоносный payload в заданиях типа атаки
        if ($isAttack) {
            $xssPayloadPatterns = [
                '/on\w+\s*=\s*["\']?alert\s*\(/i',
                '/<\s*(img|svg|iframe|math|body|video|input)[^>]*>/i',
                '/javascript:/i',
                '/<\s*svg[^>]*onload\s*=\s*["\']?alert/i',
                '/src\s*=\s*["\']?data:text\/html/i',
            ];

            $hasPayload = false;
            foreach ($xssPayloadPatterns as $pattern) {
                if (preg_match($pattern, $userCode)) {
                    $hasPayload = true;
                    break;
                }
            }

            if (!$hasPayload) {
                return [
                    'is_valid' => false,
                    'errors' => ["XSS-инъекция не обнаружена. Обычный текст вроде '123' не подходит."],
                    'score' => 0,
                    'generated_hint' => $config['hint'] ?? 'Введите вредоносный код, например: `<img src=x onerror=alert(1)>`'
                ];
            }
        }
    
        if ($isAttack) {
            switch ($xssType) {
                case 'Reflected XSS':
                    if (!empty($rules['expected_substring_in_url']) &&
                        stripos($userCode, $rules['expected_substring_in_url']) === false) {
                        $errors[] = "Ожидаемый вредоносный код не найден в URL.";
                        $score -= $weights['expected_substring_in_url'];
                    } else {
                        $hints[] = "Добавьте вредоносный код в URL: " . ($rules['expected_substring_in_url'] ?? '');
                    }
                    break;

                case 'Stored XSS':
                    if (!empty($rules['expected_stored_value']) &&
                        stripos($userCode, $rules['expected_stored_value']) === false) {
                        $errors[] = "Ожидаемый код не найден в сохранённых данных.";
                        $score -= $weights['expected_stored_value'];
                    } else {
                        $hints[] = "Внедрите вредоносный код в сохраняемые данные: " . ($rules['expected_stored_value'] ?? '');
                    }
                    if (!empty($rules['must_persist'])) {
                        $hints[] = "Убедитесь, что код сохраняется и срабатывает при загрузке.";
                    }
                    break;

                case 'DOM-based XSS':
                    if (preg_match('/^\s*alert\s*\(/i', trim($userCode))) {
                        $errors[] = "Прямой вызов alert() не считается DOM XSS атакой.";
                        $score = 0;
                        break;
                    }

                    if (preg_match('/<\s*(img|svg|iframe)[^>]*on\w+\s*=\s*["\']?alert\s*\(/i', $userCode)) {
                        $hints[] = "Вы использовали DOM XSS payload с обработчиком событий. Отлично!";
                    } else {
                        $errors[] = "Не обнаружено DOM-XSS payload";
                        $score -= 40;
                    }

                    if (!empty($config['vulnerable_js']) && !preg_match('/innerHTML|outerHTML/', $config['vulnerable_js'])) {
                        $errors[] = "В шаблоне страницы не используется innerHTML / outerHTML.";
                        $score -= 10;
                    }
                    break;
            }

            if (!empty($rules['should_trigger_alert']) &&
                !preg_match('/alert\s*\(\s*[\'"]?.*?[\'"]?\s*\)/', $userCode)) {
                $errors[] = "Ожидается вызов alert(\"...\").";
                $score -= $weights['should_trigger_alert'];
                $hints[] = "Цель — вызвать `alert(...)` любым способом, кроме `<script>alert()</script>`.";
            }

        } else {
            foreach ($config['required_functions'] ?? [] as $function) {
                if (!preg_match("/\b{$function}\s*\(/", $userCode)) {
                    $errors[] = "Не используется обязательная функция: {$function}";
                    $score -= 30;
                }
            }

            $dangerousPatterns = [
                '/\.innerHTML\s*=/',
                '/document\.write\s*\(/',
                '/<\?=\s*\$_([A-Z]+)\[.*?\]\s*\?>/',
                '/echo\s+.*?\$_([A-Z]+)\[.*?\]/'
            ];

            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $userCode)) {
                    $errors[] = "Обнаружен опасный паттерн вывода: " . $this->getPatternName($pattern);
                    $score -= 20;
                }
            }

            if (isset($config['context_checks'])) {
                foreach ($config['context_checks'] as $context) {
                    switch ($context) {
                        case 'html_escaping':
                            if (!$this->checkHtmlEscaping($userCode)) {
                                $errors[] = "Отсутствует HTML-экранирование";
                                $score -= $weights['html_escaping'];
                            }
                            break;
                        case 'safe_dom_manipulation':
                            if (!$this->checkSafeDomManipulation($userCode)) {
                                $errors[] = "Небезопасная манипуляция с DOM";
                                $score -= $weights['safe_dom_manipulation'];
                            }
                            break;
                    }
                }
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'score' => max(0, $score),
            'generated_hint' => $config['hint'] ?? implode(" ", $hints)
        ];
    }

    private function checkHtmlEscaping(string $code): bool
    {
        return preg_match('/htmlspecialchars\(|htmlentities\(/', $code);
    }

    private function checkSafeDomManipulation(string $code): bool
    {
        return !preg_match('/innerHTML|outerHTML|document\.write/', $code)
            && preg_match('/textContent|setAttribute\(/', $code);
    }

    private function getPatternName(string $pattern): string
    {
        $map = [
            '/\.innerHTML\s*=/' => 'innerHTML',
            '/document\.write\s*\(/' => 'document.write()',
            '/<\?=\s*\$_([A-Z]+)\[.*?\]\s*\?>/' => 'Прямой вывод суперглобальных переменных',
            '/echo\s+.*?\$_([A-Z]+)\[.*?\]/' => 'Вывод необработанных данных'
        ];
        return $map[$pattern] ?? 'Неизвестный паттерн';
    }
}
