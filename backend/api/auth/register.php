<?php
require_once __DIR__ . '/../cors.php';
cors();
error_reporting(E_ALL);
header("Content-Type: application/json");

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

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['login']) || empty($data['password'])) {
        throw new Exception('Логин и пароль обязательны');
    }

    $login = htmlspecialchars(trim($data['login']), ENT_QUOTES, 'UTF-8');
    $password = $data['password'];


    if (!preg_match('/^[a-zA-Z0-9]{10,}$/', $login)) {
        throw new Exception('Логин должен содержать минимум 10 символов и состоять только из латинских букв и цифр');
    }

    if (
        strlen($password) < 6 ||
        !preg_match('/[a-zA-Z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {
        throw new Exception('Пароль должен быть не менее 6 символов и содержать хотя бы одну букву и одну цифру');
    }


    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE login = :login");
    $stmt->execute(['login' => $login]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Неверные данные. Попробуйте другой логин или пароль.');
    }

    $options = [
        'cost' => 12 
    ];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT, $options);

    $stmt = $pdo->prepare("INSERT INTO user (login, password, experience, level, created_at, updated_at) 
                           VALUES (:login, :password, :experience, :level, NOW(), NOW())");

    $stmt->execute([
        'login' => $login,
        'password' => $hashedPassword,
        'experience' => 0,
        'level' => 1
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Пользователь успешно зарегистрирован'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
