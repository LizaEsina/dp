<?php
require_once __DIR__ . '/../jwt_middleware.php'; 
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Authorization");

$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';
$tokenData = authenticate(); // Функция сама отправит ошибку при неудаче
$userId = $tokenData['user_id'];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    // Получаем ID урока
    $lessonId = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$lessonId) throw new Exception('Не указан ID урока', 400);

    // Проверяем существование урока
    $stmt = $pdo->prepare("SELECT id FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    if (!$stmt->fetch()) throw new Exception('Урок не найден', 404);

    // Получаем следующую подсказку
    $stmt = $pdo->prepare("
        SELECT h.id, h.text 
        FROM lesson_hints h
        WHERE h.lesson_id = ? AND
              h.id NOT IN (
                  SELECT uh.hint_id 
                  FROM user_hints uh 
                  WHERE uh.user_id = ? AND uh.lesson_id = ?
              )
        ORDER BY h.order_index ASC
        LIMIT 1
    ");
    $stmt->execute([$lessonId, $userId, $lessonId]);
    $hint = $stmt->fetch();

    if (!$hint) {
        // Проверяем, есть ли вообще подсказки для этого урока
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_hints WHERE lesson_id = ?");
        $stmt->execute([$lessonId]);
        $totalHints = $stmt->fetchColumn();
        
        if ($totalHints == 0) {
            throw new Exception('Для этого урока нет подсказок', 404);
        } else {
            throw new Exception('Вы использовали все доступные подсказки для этого урока', 400);
        }
    }

    // Фиксируем использование подсказки
    $stmt = $pdo->prepare("
        INSERT INTO user_hints (user_id, lesson_id, hint_id) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, $lessonId, $hint['id']]);

    // Начисляем штраф (10% от стоимости урока)
    $stmt = $pdo->prepare("
        UPDATE user u
        JOIN lessons l ON l.id = ?
        SET u.experience = GREATEST(0, u.experience - FLOOR(l.points * 0.1))
        WHERE u.id = ?
    ");
    $stmt->execute([$lessonId, $userId]);

    // Получаем оставшееся количество подсказок
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as remaining 
        FROM lesson_hints h
        WHERE h.lesson_id = ? AND
              h.id NOT IN (
                  SELECT uh.hint_id 
                  FROM user_hints uh 
                  WHERE uh.user_id = ? AND uh.lesson_id = ?
              )
    ");
    $stmt->execute([$lessonId, $userId, $lessonId]);
    $remaining = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'hint' => $hint['text'],
        'penalty' => 'Списано 10% стоимости урока',
        'remaining_hints' => $remaining
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}