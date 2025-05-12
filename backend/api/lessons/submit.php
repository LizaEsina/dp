<?php
require_once __DIR__ . '/../jwt_middleware.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';
$tokenData = authenticate();
$userId = $tokenData['user_id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Инициализация переменной $transactionStarted
    $transactionStarted = false;

    // Получаем assignment_id из URL
    $assignmentId = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$assignmentId) throw new Exception('Не указан ID задания', 400);

    // Получаем тело запроса
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['code'])) {
        throw new Exception('Неверные данные: требуется поле code', 400);
    }

    // Начинаем транзакцию
    $pdo->beginTransaction();
    $transactionStarted = true;

// Получаем данные задания и связанного урока
$stmt = $pdo->prepare("
    SELECT a.*, l.category, l.points 
    FROM assignments a
    JOIN lessons l ON a.lesson_id = l.id
    WHERE a.id = ?
");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();

if (!$assignment) throw new Exception('Задание не найдено', 404);

// Подключаем валидаторы
require_once __DIR__ . '/../validators/ValidatorInterface.php';
require_once __DIR__ . '/../validators/SqlValidator.php';
require_once __DIR__ . '/../validators/ValidatorFactory.php';
require_once __DIR__ . '/../validators/XssValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

$validator = ValidatorFactory::create($assignment['category']);

// Получаем данные config и validation_rules из базы данных
$configData = json_decode($assignment['config'], true);
$rulesData = json_decode($assignment['validation_rules'], true);

// Базовый конфиг + тип
$mergedConfig = array_merge($configData ?? [], ['type' => $assignment['type']]);

// Если есть правила, добавляем
if (is_array($rulesData)) {
    $mergedConfig = array_merge($mergedConfig, $rulesData);
}


// Проверяем решение с объединённым конфигом
$validationResult = $validator->validate(
    $input['code'],
    $mergedConfig  // передаем объединённый конфиг в валидатор
);

// Сохраняем попытку (соответствует структуре таблицы)
$stmt = $pdo->prepare("
    INSERT INTO user_attempts 
    (user_id, assignment_id, attempt_code, is_success, score, details, code) 
    VALUES 
    (:user_id, :assignment_id, :attempt_code, :is_success, :score, :details, :code)
");
$details = [
    'errors' => $validationResult['errors'] ?? [],
    'warnings' => $validationResult['warnings'] ?? [],
    'feedback' => $validationResult['feedback'] ?? ''
];
$stmt->execute([
    ':user_id' => $userId,
    ':assignment_id' => $assignmentId,
    ':attempt_code' => $input['code'], // для обратной совместимости
    ':is_success' => $validationResult['is_valid'] ? 1 : 0,
    ':score' => $validationResult['score'],
    ':details' => json_encode($details),
    ':code' => $input['code'] // новое поле
]);
    // Обновляем прогресс пользователя
    $isCompleted = $validationResult['score'] >= 80 ? 1 : 0;
    $stmt = $pdo->prepare("
        INSERT INTO user_assignments 
        (user_id, assignment_id, is_completed, attempts, best_score) 
        VALUES 
        (:user_id, :assignment_id, :completed, 1, :score)
        ON DUPLICATE KEY UPDATE
            attempts = attempts + 1,
            best_score = GREATEST(best_score, :score),
            is_completed = IF(is_completed = 1, 1, :completed)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':assignment_id' => $assignmentId,
        ':completed' => $isCompleted,
        ':score' => $validationResult['score']
    ]);

    // Начисляем опыт
    $experienceEarned = floor($assignment['points'] * ($validationResult['score'] / 100));
    
    $stmt = $pdo->prepare("
        UPDATE user 
        SET experience = experience + :exp 
        WHERE id = :user_id
    ");
    $stmt->execute([
        ':exp' => $experienceEarned,
        ':user_id' => $userId
    ]);

    // Завершаем транзакцию
    $pdo->commit();

    // Формируем ответ
    echo json_encode([
        'success' => true,
        'score' => $validationResult['score'] ?? 0,
        'experience_earned' => $experienceEarned,
        'is_correct' => isset($validationResult['is_valid']) ? (bool)$validationResult['is_valid'] : null,
        'is_completed' => (bool)$isCompleted,
        'feedback' => $validationResult['feedback'] ?? '',
        'errors' => $validationResult['errors'] ?? [],
        'warnings' => $validationResult['warnings'] ?? [],
        'code' => $validationResult['code'] ?? [],
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $transactionStarted) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString() // Только для разработки
    ]);
} catch (Exception $e) {
    if (isset($pdo) && $transactionStarted) {
        $pdo->rollBack();
    }
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
