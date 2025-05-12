<?php
require_once __DIR__ . '/../cors.php';
cors();
require_once __DIR__ . '/../jwt_middleware.php';

header("Content-Type: application/json");

$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';

// Аутентификация и получение данных пользователя
$tokenData = authenticate();
$userId = $tokenData['user_id'];

try {
    // Подключаемся к базе данных
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Получаем вопросы по завершённым урокам пользователя
    $stmt = $pdo->prepare("
    SELECT q.id, q.lesson_id, q.question, q.correct_answer, q.options 
    FROM lesson_questions q
    JOIN user_lessons ul ON q.lesson_id = ul.lesson_id
    WHERE ul.user_id = ? AND ul.is_completed = 1
    GROUP BY q.id
    LIMIT 20
");

    $stmt->execute([$userId]);
    $questions = $stmt->fetchAll();

    if (!$questions) {
        http_response_code(404);
        echo json_encode(['error' => 'No available questions for completed lessons']);
        exit;
    }

    echo json_encode(['questions' => $questions]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
