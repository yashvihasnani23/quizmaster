<?php
$pageTitle = 'Take Quiz - QuizMaster';
require_once 'includes/auth.php';
require_login();

$quizId = (int)($_GET['id'] ?? 0);

// Load the quiz (must be published)
$stmt = $pdo->prepare('SELECT id, title, description, time_limit_minutes FROM quizzes WHERE id = ? AND is_published = 1');
$stmt->execute([$quizId]);
$quiz = $stmt->fetch();
if (!$quiz) {
    http_response_code(404);
    die('Quiz not found.');
}

// Load questions. We do NOT select correct_option, so answers never reach the browser.
$stmt = $pdo->prepare('SELECT id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE quiz_id = ? ORDER BY id');
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll();
if (!$questions) {
    die('This quiz has no questions yet.');
}

// Remember when this user started, so the server can measure time taken
$_SESSION['quiz_start'][$quizId] = time();

$totalSeconds = (int)$quiz['time_limit_minutes'] * 60;

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?= e($quiz['title']) ?></h2>
    <span class="badge bg-danger fs-5" id="timer">--:--</span>
</div>
<p class="text-muted"><?= e($quiz['description']) ?></p>

<form method="POST" action="submit_quiz.php" id="quizForm">
    <input type="hidden" name="quiz_id" value="<?= (int)$quiz['id'] ?>">

    <?php foreach ($questions as $i => $q): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="mb-3">Q<?= $i + 1 ?>. <?= e($q['question_text']) ?></h6>
                <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="radio"
                               name="answers[<?= (int)$q['id'] ?>]"
                               id="q<?= (int)$q['id'] . $opt ?>" value="<?= $opt ?>">
                        <label class="form-check-label" for="q<?= (int)$q['id'] . $opt ?>">
                            <?= e($q['option_' . $opt]) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-success btn-lg">Submit Quiz</button>
</form>

<script>
// Countdown timer: auto-submits the form when time runs out
let remaining = <?= $totalSeconds ?>;
const timerEl = document.getElementById('timer');
const form = document.getElementById('quizForm');

function render() {
    const m = String(Math.floor(remaining / 60)).padStart(2, '0');
    const s = String(remaining % 60).padStart(2, '0');
    timerEl.textContent = m + ':' + s;
}
render();

const interval = setInterval(() => {
    remaining--;
    render();
    if (remaining <= 0) {
        clearInterval(interval);
        form.submit();
    }
}, 1000);
</script>

<?php require_once 'includes/footer.php'; ?>