<?php
require_once 'jwt_middleware.php';

// Настройки CORS
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Логирование для отладки
error_log('Request received: ' . date('Y-m-d H:i:s'));
error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Headers: ' . print_r(getallheaders(), true));

try {
    // Подключение к БД
    $pdo = new PDO(
        "mysql:host=mysql_db;dbname=esina_diplom",
        "esina_diplom",
        "Xbxbkjdf5.",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Валидация и обработка параметров
    $allowed = [
        'categories' => ['sql', 'xss', 'csrf', 'idol', 'broken_auth'],
        'difficulties' => ['low', 'medium', 'hard']
    ];

    $filters = [];
    foreach ($_GET as $key => $value) {
        switch ($key) {
            case 'category':
                if (in_array($value, $allowed['categories'])) {
                    $filters[$key] = $value;
                }
                break;
                
            case 'difficulty':
                if (in_array($value, $allowed['difficulties'])) {
                    $filters[$key] = $value;
                }
                break;
        }
    }

    // Формирование SQL-запроса
    $query = "SELECT * FROM lessons";
    $where = [];
    $params = [];

    if (!empty($filters)) {
        foreach ($filters as $field => $value) {
            $where[] = "$field = :$field";
            $params[":$field"] = $value;
        }
        $query .= " WHERE " . implode(" AND ", $where);
    }

    // Выполнение запроса
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $result = $stmt->fetchAll();
    
    // Форматирование ответа
    echo json_encode([
        'success' => true,
        'data' => array_map(function($lesson) {
            return [
                'id' => $lesson['id'],
                'title' => $lesson['title'],
                'category' => $lesson['category'],
                'difficulty' => $lesson['difficulty'],
                'is_premium' => (bool)$lesson['is_premium'],
                'points' => $lesson['points'],
                'created_at' => $lesson['created_at']
            ];
        }, $result)
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}