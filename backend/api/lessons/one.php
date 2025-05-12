<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Authorization");

// Настройки JWT
define('JWT_SECRET', 'your_very_secret_key_here');

// Функция для проверки токена
function authenticate() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? null;

    if (!$authHeader) {
        throw new Exception("Authorization header is missing", 401);
    }

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        throw new Exception("Invalid token format", 401);
    }

    $token = $matches[1];
    $tokenParts = explode('.', $token);
    
    if (count($tokenParts) !== 3) {
        throw new Exception("Invalid token structure", 401);
    }

    list($header, $payload, $signature) = $tokenParts;
    
    // Проверяем подпись
    $validSignature = base64_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );
    
    if ($signature !== $validSignature) {
        throw new Exception("Invalid token signature", 401);
    }
    
    $payloadData = json_decode(base64_decode($payload), true);
    if (!$payloadData) {
        throw new Exception("Invalid payload data", 401);
    }
    
    // Проверяем срок действия
    if (isset($payloadData['exp']) && time() > $payloadData['exp']) {
        throw new Exception("Token expired", 401);
    }
    
    if (!isset($payloadData['user_id'])) {
        throw new Exception("User ID missing in token", 401);
    }
    
    return $payloadData;
}

// Подключение к БД
$host = 'mysql_db';
$dbname = 'esina_diplom';
$dbuser = 'esina_diplom';
$dbpass = 'Xbxbkjdf5.';

try {
    $isAuthenticated = false;
    $userId = null;
    $userProgress = null;
    // Проверка авторизации
    try {
        $tokenData = authenticate();
        $userId = $tokenData['user_id'];
        $isAuthenticated = true;
    } catch (Exception $authError) {
        $isAuthenticated = false;
        // Для неавторизованных пользователей просто продолжаем без userId
    }

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Получаем ID урока
    $lessonId = $_GET['id'] ?? null;
    if (!$lessonId || !is_numeric($lessonId)) {
        throw new Exception("Invalid lesson ID", 400);
    }

    // Получаем урок
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = :id");
    $stmt->execute(['id' => $lessonId]);
    $lesson = $stmt->fetch();

    if (!$lesson) {
        throw new Exception("Lesson not found", 404);
    }

    // Получаем прогресс пользователя (если авторизован)
    $userProgress = [
        'is_completed' => false,
        'attempts' => 0,
        'best_score' => 0
    ];

    if ($isAuthenticated) {
        $userProgress = [
            'is_completed' => false,
            'attempts' => 0,
            'best_score' => 0
        ];
        
        $progressStmt = $pdo->prepare("
            SELECT is_completed, attempts, best_score 
            FROM user_lessons 
            WHERE user_id = :user_id AND lesson_id = :lesson_id
        ");
        $progressStmt->execute([
            'user_id' => $userId,
            'lesson_id' => $lessonId
        ]);
        $progress = $progressStmt->fetch();
        
        if ($progress) {
            $userProgress = $progress;
        }
    }

    // Формируем ответ
    $response = [
        'success' => true,
        'data' => [
            'lesson' => $lesson,
            'user_progress' => $isAuthenticated ? $userProgress : null
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    $code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>