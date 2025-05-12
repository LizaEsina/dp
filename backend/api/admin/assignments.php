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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    checkAdmin($pdo, $userId);

    $method = $_SERVER['REQUEST_METHOD'];
    $assignmentId = $_GET['id'] ?? null;

    switch ($method) {
        case 'GET':
            if ($assignmentId) {
                $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
                $stmt->execute([$assignmentId]);
                echo json_encode($stmt->fetch());
            } else {
                // Получаем все задания
                $stmt = $pdo->query("SELECT * FROM assignments");
                $assignments = $stmt->fetchAll();
        
                // Получаем список всех уроков (id и заголовки)
                $lessonsStmt = $pdo->query("SELECT id, title FROM lessons ORDER BY title");
                $lessons = $lessonsStmt->fetchAll();
        
                echo json_encode([
                    'assignments' => $assignments,
                    'lessons' => $lessons
                ]);
            }
            break;
        

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            $required = ['lesson_id', 'type', 'difficulty_level', 'config'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Поле $field обязательно", 400);
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO assignments 
                (lesson_id, type, difficulty_level, config, solution_code, validation_rules)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['lesson_id'],
                $input['type'],
                $input['difficulty_level'],
                json_encode($input['config']),
                $input['solution_code'] ?? null,
                isset($input['validation_rules']) ? json_encode($input['validation_rules']) : null
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

            case 'PUT':
                if (!$assignmentId) {
                    throw new Exception("ID задания обязателен", 400);
                }
    
                $input = json_decode(file_get_contents('php://input'), true);
                if (!is_array($input)) {
                    throw new Exception("Невалидный JSON в теле запроса", 400);
                }
    
                $required = ['lesson_id', 'type', 'difficulty_level', 'config'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        throw new Exception("Поле $field обязательно", 400);
                    }
                }
    
                $stmt = $pdo->prepare("
                    UPDATE assignments SET 
                        lesson_id = ?, 
                        type = ?, 
                        difficulty_level = ?, 
                        config = ?, 
                        solution_code = ?, 
                        validation_rules = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $input['lesson_id'],
                    $input['type'],
                    $input['difficulty_level'],
                    json_encode($input['config']),
                    $input['solution_code'] ?? null,
                    isset($input['validation_rules']) ? json_encode($input['validation_rules']) : null,
                    $assignmentId
                ]);
                echo json_encode(['success' => true]);
                break;
    
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
