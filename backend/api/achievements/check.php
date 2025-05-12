<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once __DIR__ . '/../jwt_middleware.php'; 
    $host = 'mysql_db';
    $dbname = 'esina_diplom';
    $user = 'esina_diplom';
    $pass = 'Xbxbkjdf5.';
    $tokenData = authenticate(); // Функция сама отправит ошибку при неудаче
$userId = $tokenData['user_id'];
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

    
    // Проверяем все возможные достижения
    $newAchievements = [];
    
    // 1. Проверка достижения "3 урока"
    $stmt = $pdo->prepare("
        INSERT INTO user_achievements (user_id, achievement_id, earned_at)
        SELECT ?, a.id, NOW()
        FROM achievements a
        WHERE a.condition = 'complete_3_lessons'
        AND NOT EXISTS (
            SELECT 1 FROM user_achievements 
            WHERE user_id = ? AND achievement_id = a.id
        )
        AND (
            SELECT COUNT(*) FROM user_lessons 
            WHERE user_id = ? AND is_completed = 1
        ) >= 3
    ");
    $stmt->execute([$userId, $userId, $userId]);
    if ($stmt->rowCount() > 0) {
        $newAchievements[] = "3 урока";
    }

    // 2. Проверка других достижений...

    echo json_encode([
        'success' => true,
        'new_achievements' => $newAchievements
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}