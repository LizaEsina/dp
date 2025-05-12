<?php
error_reporting(E_ALL);
require_once __DIR__ . '/../jwt_middleware.php';
ini_set('display_errors', 1);
$allowedOrigin = 'http://localhost:5173';
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}
$host = 'mysql_db';
$dbname = 'esina_diplom';
$username = 'esina_diplom';
$password = 'Xbxbkjdf5.';
$tokenData = authenticate(); // Функция сама отправит ошибку при неудаче
$userId = $tokenData['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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


    // Основная информация о пользователе
    $stmt = $pdo->prepare("SELECT id, login, experience, level, role FROM user WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found");
    }

    // Получение достижений пользователя
    $achievementsStmt = $pdo->prepare("
        SELECT a.name, a.description, a.icon 
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = :user_id
    ");
    $achievementsStmt->execute(['user_id' => $userId]);
    $achievements = $achievementsStmt->fetchAll();

    // Статистика по завершенным урокам
    $statsStmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT ul.lesson_id) as completed_lessons,
        COUNT(DISTINCT CASE WHEN l.category = 'sql' THEN ul.lesson_id END) as sql_completed,
        COUNT(DISTINCT CASE WHEN l.category = 'xss' THEN ul.lesson_id END) as xss_completed,
        COUNT(DISTINCT CASE WHEN l.category = 'csrf' THEN ul.lesson_id END) as csrf_completed,
        COUNT(DISTINCT CASE WHEN l.category = 'lfi' THEN ul.lesson_id END) as lfi_completed
    FROM user_lessons ul
    JOIN lessons l ON ul.lesson_id = l.id
    WHERE ul.user_id = :user_id AND ul.is_completed = TRUE
");

    $statsStmt->execute(['user_id' => $userId]);
    $stats = $statsStmt->fetch();

    // Формирование ответа
    $response = [
        'id' => $user['id'],
        'login' => $user['login'],
        'experience' => $user['experience'],
        'level' => $user['level'],
        'achievements' => $achievements,
        'role' => $user['role'],
        'stats' => [
            'completed_lessons' => $stats['completed_lessons'],
            'categories_completed' => [
                'sql' => $stats['sql_completed'],
                'xss' => $stats['xss_completed'],
                'csrf' => $stats['csrf_completed'],
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>