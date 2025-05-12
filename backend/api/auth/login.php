<?php
require_once __DIR__ . '/../cors.php';
cors();
error_reporting(E_ALL);
ini_set('display_errors', 1);
$host = 'mysql_db';
$dbname = 'esina_diplom';
$username = 'esina_diplom';
$password = 'Xbxbkjdf5.';

// Секретный ключ для подписи JWT (храните в .env!)
define('JWT_SECRET', 'your_very_secret_key_here');
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['login']) || empty($data['password'])) {
        throw new Exception('Логин и пароль обязательны');
    }

    $stmt = $pdo->prepare("SELECT * FROM user WHERE login = :login");
    $stmt->execute(['login' => $data['login']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($data['password'], $user['password'])) {
        throw new Exception('Неверные учетные данные');
    }

    // Генерация JWT
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode([
        'user_id' => $user['id'],
        'login' => $user['login'],
        'role' => $user['role'],
        'exp' => time() + 3600 // 1 час
    ]));
    $signature = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    $token = "$header.$payload.$signature";

    // Устанавливаем cookie с токеном
    setcookie('token', $token, [
        'expires' => time() + 3600,
        'path' => '/',
        'domain' => 'localhost',
        'secure' => false,     // Для разработки на localhost
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'login' => $user['login'],
            'experience' => $user['experience'],
            'level' => $user['level']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}