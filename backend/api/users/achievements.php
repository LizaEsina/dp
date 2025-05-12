<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Authorization");
require_once __DIR__ . '/../jwt_middleware.php'; 
$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';
$tokenData = authenticate(); // Функция сама отправит ошибку при неудаче
$userId = $tokenData['user_id'];
try {
    // Создаем подключение к БД
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);


    // Получаем достижения пользователя
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.name,
            a.description,
            a.icon,
            ua.earned_at
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
        ORDER BY ua.earned_at DESC
    ");
    
    // Замените $requestedUserId на $userId
    $stmt->execute([$userId]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем дату
    foreach ($achievements as &$ach) {
        $ach['earned_at'] = date('Y-m-d H:i:s', strtotime($ach['earned_at']));
    }

    echo json_encode([
        'success' => true,
        'data' => $achievements
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}