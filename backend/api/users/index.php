<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Подключение к базе данных
$host = 'mysql_db';
$dbname = 'esina_diplom';
$username = 'esina_diplom';
$password = 'Xbxbkjdf5.';

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

    // Явное указание столбцов для выборки
    $stmt = $pdo->query("
        SELECT 
            id, 
            login as username, 
            experience, 
            level, 
            created_at, 
            updated_at 
        FROM user
    ");
    
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $users,
        'count' => count($users)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}