<?php
class IdolValidator implements ValidatorInterface {
    public function validate(string $userCode, array $config): array {
        $errors = [];
        $score = 100;

        // Проверка прямых обращений по ID
        $directIdPatterns = [
            '/\$_GET\[\s*[\'"]id[\'"]\s*\]/',
            '/\$_POST\[\s*[\'"]object_id[\'"]\s*\]/'
        ];
        
        foreach ($directIdPatterns as $pattern) {
            if (preg_match($pattern, $userCode)) {
                $errors[] = "Прямое использование ID из запроса";
                $score -= 30;
            }
        }

        // Проверка авторизации доступа
        if (!preg_match('/check_object_permission\(/', $userCode)) {
            $errors[] = "Отсутствует проверка прав доступа";
            $score -= 40;
        }

        // Проверка UUID вместо последовательных ID
        if (isset($config['require_uuid']) && 
            !preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}/i', $userCode)) {
            $errors[] = "Не используются UUID";
            $score -= 20;
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'score' => max($score, 0)
        ];
    }
}