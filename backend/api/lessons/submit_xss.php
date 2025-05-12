<?php
require_once __DIR__ . '/../cors.php';
cors();
require_once __DIR__ . '/../jwt_middleware.php';
header("Content-Type: application/json");

$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';

// Аутентификация пользователя через JWT
$tokenData = authenticate();
$userId = $tokenData['user_id'];

// Получаем данные из запроса
$input = json_decode(file_get_contents('php://input'), true);
$assignmentId = $input['assignment_id'] ?? null;
$attackPayload = $input['payload'] ?? ''; // Полезная нагрузка с кодом для атаки

if (!$assignmentId) {
    http_response_code(400);
    echo json_encode(['error' => 'assignment_id required']);
    exit;
}

// Подключаемся к базе данных
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Получаем задание из базы данных
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? LIMIT 1");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    http_response_code(404);
    echo json_encode(['error' => 'Assignment not found']);
    exit;
}

$assignment['config'] = json_decode($assignment['config'], true);
$assignment['validation_rules'] = json_decode($assignment['validation_rules'], true);

$result = [
    'is_correct' => false,
    'feedback' => '',
    'errors' => [],
    'score' => 0,
    'experience_earned' => 0
];

// Проверка для DOM-based XSS
if ($assignment['type'] === 'attack_simulation') {
    if (strpos($attackPayload, '<script>alert("XSS")</script>') !== false) {
        $result['is_correct'] = true;
        $result['feedback'] = 'Успешная XSS-атака!';
        $result['score'] = 100;
        $result['experience_earned'] = 50;
    } else {
        $result['errors'][] = 'Атака не сработала. Попробуйте внедрить <script>alert("XSS")</script>';
    }
}

// Возвращаем результат
echo json_encode($result);
?>
