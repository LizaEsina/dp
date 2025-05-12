<?php
// validators/ValidatorFactory.php
class ValidatorFactory {
    private static $map = [
        'sql' => SqlValidator::class,
        'xss' => XssValidator::class,
        'csrf' => CsrfValidator::class,
        'broken_auth' => BrokenAuthValidator::class,
        'idol' => IdolValidator::class
    ];

    public static function create(string $category): ValidatorInterface {
        $category = strtolower($category);
        
        if (!isset(self::$map[$category])) {
            throw new InvalidArgumentException("Неподдерживаемая категория: {$category}");
        }

        if (!class_exists(self::$map[$category])) {
            throw new RuntimeException("Класс валидатора {$category} не найден");
        }

        return new self::$map[$category]();
    }
}