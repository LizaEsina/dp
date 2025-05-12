<?php
require_once __DIR__ . '/cors.php';
cors();
error_reporting(E_ALL);
require_once __DIR__ . '/jwt_middleware.php';
ini_set('display_errors', 1);
$host = 'mysql_db';
$dbname = 'esina_diplom';
$username = 'esina_diplom';
$password = 'Xbxbkjdf5.';
$tokenData = authenticate(); 
$userId = $tokenData['user_id'];

try {
    // Создаем подключение к БД
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    // Получаем все достижения с отметкой о получении
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.name,
            a.description,
            a.icon,
            CASE WHEN ua.user_id IS NOT NULL THEN 1 ELSE 0 END as earned,
            ua.earned_at
        FROM achievements a
        LEFT JOIN user_achievements ua 
            ON a.id = ua.achievement_id 
            AND ua.user_id = :user_id
        ORDER BY a.id ASC
    ");
    
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем дату
    foreach ($achievements as &$ach) {
        if ($ach['earned_at']) {
            $ach['earned_at'] = date('Y-m-d H:i:s', strtotime($ach['earned_at']));
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $achievements
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}