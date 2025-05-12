<?php
require_once __DIR__ . '/../cors.php';
cors();
require_once __DIR__ . '/../jwt_middleware.php';

header("Content-Type: application/json");

$host = 'mysql_db';
$dbname = 'esina_diplom';
$user = 'esina_diplom';
$pass = 'Xbxbkjdf5.';

// Аутентификация
$tokenData = authenticate();
$userId = $tokenData['user_id'];

// Получаем ответы пользователя
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['answers']) || !is_array($data['answers'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid answers format']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $correctAnswersCount = 0;
    $questionIds = array_keys($data['answers']);  // ID вопросов, переданных с фронта
    $inClause = implode(',', array_fill(0, count($questionIds), '?'));

    // Получаем все нужные данные по вопросам
    $stmt = $pdo->prepare("
        SELECT q.id, q.correct_answer, q.question, q.lesson_id, l.title AS lesson_title
        FROM lesson_questions q
        JOIN lessons l ON q.lesson_id = l.id
        JOIN user_lessons ul ON ul.lesson_id = q.lesson_id
        WHERE ul.user_id = ? AND ul.is_completed = 1 AND q.id IN ($inClause)
    ");
    $stmt->execute(array_merge([$userId], $questionIds));
    $questions = $stmt->fetchAll();

    // Проверим, что вопросы получены
    if (empty($questions)) {
        http_response_code(404);
        echo json_encode(['error' => 'No questions found for the user']);
        exit;
    }

    $totalQuestions = count($questions);  // Общее количество полученных вопросов
    $correctAnswers = [];
    $errors = [];
    $lessonsToReview = [];

    foreach ($questions as $question) {
        $id = $question['id'];
        $correctAnswers[$id] = $question['correct_answer'];
        $userAnswer = $data['answers'][$id];

        if ($correctAnswers[$id] == $userAnswer) {
            $correctAnswersCount++;
        } else {
            $errors[] = [
                'question_id' => $id,
                'question' => $question['question'],
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswers[$id],
                'lesson_id' => $question['lesson_id'],
                'lesson_title' => $question['lesson_title'],
            ];
            $lessonsToReview[$question['lesson_id']] = $question['lesson_title'];
        }
    }

    $score = $totalQuestions > 0 ? round(($correctAnswersCount / $totalQuestions) * 100, 1) : 0;

    // Если тест решен менее чем на 80%, уменьшаем опыт
    if ($score < 80) {
        // Снимаем 10 опыта (или любое другое значение)
        $stmt = $pdo->prepare("UPDATE user SET experience = experience - 100 WHERE id = ?");
        $stmt->execute([$userId]);
    }

    echo json_encode([
        'message' => "Ваш результат: $score%",
        'score' => $score,
        'correct_answers' => $correctAnswersCount,
        'total_questions' => $totalQuestions,
        'errors' => $errors,
        'lessons_to_review' => array_values($lessonsToReview) // Список уроков с ошибками
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
