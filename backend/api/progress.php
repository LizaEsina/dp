<?php
require_once __DIR__ . '/jwt_middleware.php'; 
header("Content-Type: application/json");

$host = 'mysql_db';
$dbname = 'esina_diplom';
$username = 'esina_diplom';
$password = 'Xbxbkjdf5.';
$tokenData = authenticate();
$userId = $tokenData['user_id'];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Общая статистика (учитываем только одно прохождение каждого урока)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ul.lesson_id) as total_completed,
            SUM(DISTINCT l.points) as total_points
        FROM user_lessons ul
        JOIN lessons l ON ul.lesson_id = l.id
        WHERE ul.user_id = ? AND ul.is_completed = 1
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    // Статистика по категориям
    $stmt = $pdo->prepare("
        SELECT 
            l.category,
            COUNT(DISTINCT ul.lesson_id) as completed,
            SUM(DISTINCT l.points) as points
        FROM user_lessons ul
        JOIN lessons l ON ul.lesson_id = l.id
        WHERE ul.user_id = ? AND ul.is_completed = 1
        GROUP BY l.category
    ");
    $stmt->execute([$userId]);
    $byCategory = $stmt->fetchAll();

    // Статистика по сложности
    $stmt = $pdo->prepare("
        SELECT 
            l.difficulty,
            COUNT(DISTINCT ul.lesson_id) as completed
        FROM user_lessons ul
        JOIN lessons l ON ul.lesson_id = l.id
        WHERE ul.user_id = ? AND ul.is_completed = 1
        GROUP BY l.difficulty
    ");
    $stmt->execute([$userId]);
    $byDifficulty = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'total' => $stats,
            'by_category' => $byCategory,
            'by_difficulty' => $byDifficulty
        ]
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
