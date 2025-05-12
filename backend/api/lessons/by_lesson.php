<?
require_once __DIR__ . '/../cors.php';
cors();
require_once __DIR__ . '/../jwt_middleware.php';
header("Content-Type: application/json");
$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';
$tokenData = authenticate();
$userId = $tokenData['user_id'];
$tokenData = authenticate();
$userId = $tokenData['user_id'];

$lessonId = $_GET['lesson_id'] ?? null;

if (!$lessonId) {
    http_response_code(400);
    echo json_encode(['error' => 'lesson_id required']);
    exit;
}

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$stmt = $pdo->prepare("SELECT * FROM assignments WHERE lesson_id = ? LIMIT 1");
$stmt->execute([$lessonId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    http_response_code(404);
    echo json_encode(['error' => 'Assignment not found']);
    exit;
}

$assignment['config'] = json_decode($assignment['config'], true);
$assignment['validation_rules'] = json_decode($assignment['validation_rules'], true);

echo json_encode(['assignment' => $assignment]);
