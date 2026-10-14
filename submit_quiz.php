<?php
require_once 'includes/auth.php';
require_login();

// Only accept form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: quizzes.php');
    exit;
}

$quizId  = (int)($_POST['quiz_id'] ?? 0);
$answers = $_POST['answers'] ?? [];
if (!is_array($answers)) { $answers = []; }

// Make sure the quiz exists
$stmt = $pdo->prepare('SELECT id FROM quizzes WHERE id = ? AND is_published = 1');
$stmt->execute([$quizId]);
if (!$stmt->fetch()) {
    die('Quiz not found.');
}

// Load the real answers from the database
$stmt = $pdo->prepare('SELECT id, correct_option FROM questions WHERE quiz_id = ?');
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll();
$total = count($questions);

// Time taken, measured by the server
$start = $_SESSION['quiz_start'][$quizId] ?? time();
$timeTaken = max(0, time() - $start);
unset($_SESSION['quiz_start'][$quizId]);

// Score it, and save everything in one transaction
$pdo->beginTransaction();
try {
    $score = 0;
    $rows = [];
    foreach ($questions as $q) {
        $selected = $answers[$q['id']] ?? null;
        if (!in_array($selected, ['a', 'b', 'c', 'd'], true)) {
            $selected = null;   // unanswered or invalid value
        }
        $isCorrect = ($selected !== null && $selected === $q['correct_option']) ? 1 : 0;
        $score += $isCorrect;
        $rows[] = [$q['id'], $selected, $isCorrect];
    }

    $stmt = $pdo->prepare('INSERT INTO attempts (user_id, quiz_id, score, total, time_taken_sec) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'], $quizId, $score, $total, $timeTaken]);
    $attemptId = $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO attempt_answers (attempt_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)');
    foreach ($rows as $r) {
        $stmt->execute([$attemptId, $r[0], $r[1], $r[2]]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die('Could not save your attempt.');
}

header('Location: result.php?id=' . $attemptId);
exit;