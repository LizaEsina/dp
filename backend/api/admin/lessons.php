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
    $method = $_SERVER['REQUEST_METHOD'];
    $lessonId = $_GET['id'] ?? null;

    switch ($method) {
        case 'GET':
            if ($lessonId) {
                // Запрос для получения всех данных по конкретному уроку
                $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
                $stmt->execute([$lessonId]);
                $lesson = $stmt->fetch();
                echo json_encode($lesson);  // Возвращаем все данные урока
            } else {
                // Запрос для получения всех уроков с основными полями
                $stmt = $pdo->query("SELECT id, title, slug, description, category, difficulty, theory_content, points, vulnerable_code, secure_code,is_visible FROM lessons");
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            $required = ['title', 'slug', 'description', 'category', 'difficulty'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Поле $field обязательно", 400);
                }
            }

            $stmt = $pdo->prepare("
        INSERT INTO lessons 
        (title, slug, description, category, difficulty, theory_content, points, vulnerable_code, secure_code)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $input['title'],
        $input['slug'],
        $input['description'],
        $input['category'],
        $input['difficulty'],
        $input['theory_content'] ?? '',
        $input['points'] ?? 100,
        $input['vulnerable_code'] ?? '', 
        $input['secure_code'] ?? ''      
    ]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    break;

    case 'PUT':
        if (!$lessonId) {
            throw new Exception("ID урока обязателен", 400);
        }
    
        $input = json_decode(file_get_contents('php://input'), true);
    
        // Если поле is_visible передано в запросе, добавляем его в обновление
        $isVisible = isset($input['is_visible']) ? $input['is_visible'] : null;
    
        $stmt = $pdo->prepare("
            UPDATE lessons SET
                title = ?,
                slug = ?,
                description = ?,
                category = ?,
                difficulty = ?,
                theory_content = ?,
                points = ?,
                vulnerable_code = ?,       
                secure_code = ?,           
                is_visible = COALESCE(?, is_visible)
            WHERE id = ?
        ");
        $stmt->execute([
            $input['title'],
            $input['slug'],
            $input['description'],
            $input['category'],
            $input['difficulty'],
            $input['theory_content'] ?? '',
            $input['points'] ?? 100,
            $input['vulnerable_code'] ?? '',  
            $input['secure_code'] ?? '',      
            $isVisible,
            $lessonId
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
