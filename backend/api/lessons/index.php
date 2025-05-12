<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$host = 'mysql_db';
$dbname = 'esina_diplom';
$dbuser = 'esina_diplom'; 
$dbpass = 'Xbxbkjdf5.';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Получение списка уроков только с is_visible = TRUE
    $stmt = $pdo->prepare("SELECT id, title, slug, description, difficulty, category, points, is_premium,is_visible FROM lessons WHERE is_visible = TRUE");
    $stmt->execute();
    $lessons = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'lessons' => $lessons
    ]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
