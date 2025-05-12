<?php
require_once __DIR__ . '/../cors.php';
cors();
require_once __DIR__ . '/../jwt_middleware.php';
header("Content-Type: application/json");
$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';
$tokenData = authenticate();
$userId = $tokenData['user_id'];

// Проверка прав администратора
function checkAdmin($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT role FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        throw new Exception("Admin privileges required", 403);
    }
}

// Основной код
try {
    // Создаем подключение к БД
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $userId = $tokenData['user_id'];
    checkAdmin($pdo, $userId); 

    $lessonId = $_GET['id'] ?? null;

    if (!$lessonId) {
        throw new Exception("ID урока обязателен", 400);
    }

    // Восстановление урока
    $stmt = $pdo->prepare("UPDATE lessons SET is_visible = 1 WHERE id = ?");
    $stmt->execute([$lessonId]);
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
