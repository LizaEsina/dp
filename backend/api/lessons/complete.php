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

    $lessonId = isset($_GET['id']) ? intval($_GET['id']) : null;
    if (!$lessonId) {
        throw new Exception('Не указан ID урока', 400);
    }

    $pdo->beginTransaction();

    // Проверка существования урока
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch();

    if (!$lesson) {
        throw new Exception('Урок не найден', 404);
    }

    // Проверяем, проходил ли пользователь этот урок ранее
    $stmt = $pdo->prepare("
        SELECT * FROM user_lessons 
        WHERE user_id = ? AND lesson_id = ?
    ");
    $stmt->execute([$userId, $lessonId]);
    $existing = $stmt->fetch();

    $pointsToAdd = 0;
    if ($existing) {
        // Обновляем запись, если урок уже был пройден
        $stmt = $pdo->prepare("
            UPDATE user_lessons
            SET 
                attempts = attempts + 1,
                best_score = GREATEST(best_score, :score),
                completed_at = NOW()
            WHERE user_id = :user_id AND lesson_id = :lesson_id
        ");
        $stmt->execute([
            ':score' => $lesson['points'],
            ':user_id' => $userId,
            ':lesson_id' => $lessonId
        ]);
    } else {
        // Если урок не был пройден ранее, добавляем новую запись
        $stmt = $pdo->prepare("
            INSERT INTO user_lessons 
                (user_id, lesson_id, is_completed, attempts, best_score, completed_at) 
            VALUES 
                (:user_id, :lesson_id, 1, 1, :score, NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':lesson_id' => $lessonId,
            ':score' => $lesson['points']
        ]);
        $pointsToAdd = $lesson['points'];
    }

    // Обновляем опыт пользователя только если новый опыт больше
    if ($pointsToAdd > 0) {
        $stmt = $pdo->prepare("SELECT experience FROM user WHERE id = ?");
        $stmt->execute([$userId]);
        $currentExperience = $stmt->fetchColumn();

        if ($currentExperience + $pointsToAdd > $currentExperience) {
            $stmt = $pdo->prepare("
                UPDATE user 
                SET 
                    experience = experience + :points,
                    level = FLOOR(1 + SQRT(experience + :points) / 10)
                WHERE id = :user_id
            ");
            $stmt->execute([
                ':points' => $pointsToAdd,
                ':user_id' => $userId
            ]);
        }
    }

    // Проверка достижений
    $newAchievements = checkAchievements($pdo, $userId, $lessonId);

    $pdo->commit();

    // Получение обновлённых данных
    $stmt = $pdo->prepare("
        SELECT u.experience, u.level, ul.* 
        FROM user u
        LEFT JOIN user_lessons ul 
            ON ul.user_id = u.id AND ul.lesson_id = :lesson_id
        WHERE u.id = :user_id
    ");
    $stmt->execute([
        ':lesson_id' => $lessonId,
        ':user_id' => $userId
    ]);
    $progress = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'progress' => $progress,
        'achievements' => $newAchievements,
        'message' => 'Урок успешно завершен!',
        'points_awarded' => $pointsToAdd
    ]);

} catch (PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Функция для проверки достижений пользователя
function checkAchievements($pdo, $userId, $lessonId) {
    $newAchievements = [];

    // Кол-во завершённых уроков
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_lessons WHERE user_id = ? AND is_completed = 1");
    $stmt->execute([$userId]);
    $completedLessons = $stmt->fetchColumn();

    // 1. Первый шаг
    if ($completedLessons >= 1) {
        awardAchievement($pdo, $userId, 3, 'Первый шаг', $newAchievements);
    }

    // 2. Упорство — 3 любых урока
    if ($completedLessons >= 3) {
        awardAchievement($pdo, $userId, 6, 'Упорство', $newAchievements);
    }

    // 3. SQL Мастер — 5 SQL-уроков
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_lessons ul
        JOIN lessons l ON ul.lesson_id = l.id
        WHERE ul.user_id = ? AND ul.is_completed = 1 AND l.category = 'SQL'
    ");
    $stmt->execute([$userId]);
    $sqlCompleted = $stmt->fetchColumn();

    if ($sqlCompleted >= 5) {
        awardAchievement($pdo, $userId, 5, 'SQL Мастер', $newAchievements);
    }

    // 4. Перфекционист — идеальный результат
    $stmt = $pdo->prepare("SELECT points FROM lessons WHERE id = ?");
    $stmt->execute([$lessonId]);
    $maxPoints = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT best_score FROM user_lessons 
        WHERE user_id = ? AND lesson_id = ?
    ");
    $stmt->execute([$userId, $lessonId]);
    $bestScore = $stmt->fetchColumn();

    if ($bestScore == $maxPoints) {
        awardAchievement($pdo, $userId, 4, 'Перфекционист', $newAchievements);
    }
// 5. XSS-Охотник — 3 XSS-урока
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM user_lessons ul
    JOIN lessons l ON ul.lesson_id = l.id
    WHERE ul.user_id = ? AND ul.is_completed = 1 AND l.category = 'XSS'
");
$stmt->execute([$userId]);
$xssCompleted = $stmt->fetchColumn();

if ($xssCompleted >= 3) {
    awardAchievement($pdo, $userId, 7, 'XSS-Охотник', $newAchievements);
}

// 6. CSRF-Щит — 2 CSRF-урока
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM user_lessons ul
    JOIN lessons l ON ul.lesson_id = l.id
    WHERE ul.user_id = ? AND ul.is_completed = 1 AND l.category = 'CSRF'
");
$stmt->execute([$userId]);
$csrfCompleted = $stmt->fetchColumn();

if ($csrfCompleted >= 2) {
    awardAchievement($pdo, $userId, 8, 'CSRF-Щит', $newAchievements);
}

    return $newAchievements;
}

// Функция для присуждения достижения
function awardAchievement($pdo, $userId, $achievementId, $name, &$list) {
    $stmt = $pdo->prepare("SELECT * FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
    $stmt->execute([$userId, $achievementId]);
    $existingAchievement = $stmt->fetch();

    if (!$existingAchievement) {
        $stmt = $pdo->prepare("
            INSERT INTO user_achievements 
            (user_id, achievement_id, earned_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$userId, $achievementId]);

        if ($stmt->rowCount() > 0) {
            $list[] = $name;
        }
    }
}
?>
