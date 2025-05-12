<?php
require_once __DIR__ . '/../cors.php';
cors();
error_reporting(E_ALL);
header("Content-Type: application/json");

$host = 'mysql_db'; // Укажите ваш хост
$dbname = 'esina_diplom'; // Укажите ваше имя базы данных
$username = 'esina_diplom'; // Укажите имя пользователя для БД
$password = 'Xbxbkjdf5.'; // Укажите пароль для БД

try {
    // Подключение к базе данных
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

    // Проверка обязательных полей
    if (empty($data['login']) || empty($data['password'])) {
        throw new Exception('Логин и пароль обязательны');
    }

    // Хеширование пароля
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Подготовка запроса для вставки нового пользователя
    $stmt = $pdo->prepare("INSERT INTO user (login, password, experience, level, created_at, updated_at) 
                            VALUES (:login, :password, :experience, :level, NOW(), NOW())");

    // Задаем уровень и опыт по умолчанию
    $experience = 0; // Начальный опыт
    $level = 1; // Начальный уровень

    // Выполнение запроса с передачей параметров
    $stmt->execute([
        'login' => $data['login'],
        'password' => $hashedPassword,
        'experience' => $experience,
        'level' => $level
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Пользователь успешно зарегистрирован'
    ]);
} catch (Exception $e) {
    http_response_code(400); // Код ошибки 400 для запроса с неправильными данными
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}