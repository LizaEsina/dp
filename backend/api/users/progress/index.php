
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once __DIR__ . '/../../../jwt_middleware.php';

try {
    $tokenData = authenticate();
    $userId = $tokenData['user_id'];

    $host = 'mysql_db';
    $dbname = 'esina_diplom';
    $dbuser = 'esina_diplom';
    $dbpass = 'Xbxbkjdf5.';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("
        SELECT lesson_id, is_completed, attempts, best_score 
        FROM user_lessons 
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $progressMap = [];
    foreach ($progress as $item) {
        $progressMap[$item['lesson_id'] = $item];
    }

    echo json_encode([
        'success' => true,
        'data' => $progressMap
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>