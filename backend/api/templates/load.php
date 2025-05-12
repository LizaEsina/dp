<?php
require_once __DIR__ . '/../cors.php';
cors();

require_once __DIR__ . '/../jwt_middleware.php';
header('Content-Type: application/json');

// Аутентификация
$tokenData = authenticate();
$userId = $tokenData['user_id'] ?? null;

// Разрешённые шаблоны
$allowedTemplates = ['greeting.php', 'comment_form.php', 'dom_xss_fix.html','dom_xss.html','getUserByName_call.php','transfer_form.php','csrf_advanced_fix.php'];

// Получение имени шаблона
$templateName = $_GET['name'] ?? '';
if (!in_array($templateName, $allowedTemplates)) {
    http_response_code(400);
    echo json_encode(['error' => 'Недопустимое имя шаблона']);
    exit;
}

// Параметры подключения к базе данных
$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';

// Подключение к базе данных
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Запрос шаблона из базы данных
    $query = "SELECT content FROM templates WHERE name = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$templateName]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($template) {
        // Если шаблон найден, возвращаем его содержимое
        echo json_encode([
            'template' => htmlspecialchars($template['content'], ENT_QUOTES, 'UTF-8')
        ]);
    } else {
        // Если шаблон не найден
        http_response_code(404);
        echo json_encode(['error' => 'Файл шаблона не найден']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
