<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../jwt_middleware.php'; 

$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';

$tokenData = authenticate(); 
$userId = $tokenData['user_id'];

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

    // Общая статистика
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_user, 
            SUM(experience) as total_experience,
            AVG(experience) as avg_experience
        FROM user
    ")->fetch();

    // Активность пользователей
    $activity = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as registrations,
            SUM(experience > 0) as active_user
        FROM user
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 7
    ")->fetchAll();

    // Статистика по урокам
    $lessonsStats = $pdo->query("
    SELECT 
        l.category,
        COUNT(DISTINCT CONCAT(ul.user_id, '_', ul.lesson_id)) as completed,
        AVG(ul.best_score) as avg_score
    FROM user_lessons ul
    JOIN lessons l ON ul.lesson_id = l.id
    WHERE ul.is_completed = 1
    GROUP BY l.category
")->fetchAll();


    // Прогресс по каждому пользователю с категориями
    $usersProgress = $pdo->query("
    SELECT 
        u.id,
        u.login,
        u.experience,
        IFNULL(ul_stats.completed_lessons, 0) AS completed_lessons,
        IFNULL(ul_stats.avg_score, 0) AS avg_score,
        COUNT(DISTINCT ua.achievement_id) AS achievements
    FROM user u
    LEFT JOIN (
        SELECT 
            user_id,
            COUNT(DISTINCT lesson_id) AS completed_lessons,
            ROUND(AVG(best_score), 2) AS avg_score
        FROM user_lessons
        WHERE is_completed = 1
        GROUP BY user_id
    ) AS ul_stats ON u.id = ul_stats.user_id
    LEFT JOIN user_achievements ua ON u.id = ua.user_id
    GROUP BY u.id
    ORDER BY u.experience DESC
")->fetchAll();

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'activity' => $activity,
        'lessons' => $lessonsStats,
        'users' => $usersProgress
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
