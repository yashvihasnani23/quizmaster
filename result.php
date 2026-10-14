<?php
$pageTitle = 'Result - QuizMaster';
require_once 'includes/auth.php';
require_login();

$attemptId = (int)($_GET['id'] ?? 0);

// The attempt must belong to the logged-in user
$stmt = $pdo->prepare('SELECT a.*, q.title FROM attempts a JOIN quizzes q ON q.id = a.quiz_id WHERE a.id = ? AND a.user_id = ?');
$stmt->execute([$attemptId, $_SESSION['user_id']]);
$attempt = $stmt->fetch();
if (!$attempt) {
    http_response_code(404);
    die('Result not found.');
}

// Question-by-question review
$stmt = $pdo->prepare('SELECT qu.question_text, qu.option_a, qu.option_b, qu.option_c, qu.option_d,
                              qu.correct_option, aa.selected_option, aa.is_correct
                       FROM attempt_answers aa
                       JOIN questions qu ON qu.id = aa.question_id
                       WHERE aa.attempt_id = ?
                       ORDER BY qu.id');
$stmt->execute([$attemptId]);
$review = $stmt->fetchAll();

$percent = $attempt['total'] > 0 ? round($attempt['score'] / $attempt['total'] * 100) : 0;
$mins = intdiv((int)$attempt['time_taken_sec'], 60);
$secs = (int)$attempt['time_taken_sec'] % 60;

require_once 'includes/header.php';
?>

<div class="card shadow-sm text-center mb-4">
    <div class="card-body py-4">
        <h4><?= e($attempt['title']) ?></h4>
        <h1 class="display-4 fw-bold"><?= (int)$attempt['score'] ?> / <?= (int)$attempt['total'] ?></h1>
        <p class="lead mb-1"><?= $percent ?>%</p>
        <p class="text-muted">Time taken: <?= $mins ?>m <?= $secs ?>s</p>
        <a href="quizzes.php" class="btn btn-primary">More Quizzes</a>
        <a href="quiz.php?id=<?= (int)$attempt['quiz_id'] ?>" class="btn btn-outline-primary">Try Again</a>
    </div>
</div>

<h5 class="mb-3">Review your answers</h5>
<?php foreach ($review as $i => $r): ?>
    <div class="card mb-3 border-<?= $r['is_correct'] ? 'success' : 'danger' ?>">
        <div class="card-body">
            <h6>Q<?= $i + 1 ?>. <?= e($r['question_text']) ?></h6>
            <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                <?php
                $class = '';
                if ($opt === $r['correct_option']) { $class = 'text-success fw-bold'; }
                elseif ($opt === $r['selected_option']) { $class = 'text-danger fw-bold'; }
                ?>
                <div class="<?= $class ?>">
                    <?= strtoupper($opt) ?>. <?= e($r['option_' . $opt]) ?>
                    <?php if ($opt === $r['selected_option']): ?> (your answer)<?php endif; ?>
                    <?php if ($opt === $r['correct_option']): ?> ✔<?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($r['selected_option'] === null): ?>
                <div class="text-muted small mt-1">Not answered</div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>