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

    // Получаем ID пользователя и проверяем его права
    $userId = $tokenData['user_id'];
    checkAdmin($pdo, $userId); 

    $method = $_SERVER['REQUEST_METHOD'];
    $lessonId = $_GET['id'] ?? null;

    switch ($method) {
        case 'DELETE':
            if (!$lessonId) {
                throw new Exception("ID урока обязателен", 400);
            }

            // Проверим текущее значение is_visible
            $stmt = $pdo->prepare("SELECT is_visible FROM lessons WHERE id = ?");
            $stmt->execute([$lessonId]);
            $lesson = $stmt->fetch();

            if (!$lesson) {
                throw new Exception("Урок не найден", 404);
            }

            // Если урок уже скрыт, то не делаем изменений
            if ($lesson['is_visible'] == 0) {
                echo json_encode(['success' => true, 'message' => 'Урок уже скрыт']);
                break;
            }

            // Обновляем поле is_visible на false
            $stmt = $pdo->prepare("UPDATE lessons SET is_visible = 0 WHERE id = ?");
            $stmt->execute([$lessonId]);

            // Проверяем, обновился ли урок
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Урок скрыт']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Не удалось скрыть урок']);
            }

            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Метод не поддерживается']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
