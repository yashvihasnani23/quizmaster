<?php
$pageTitle = 'Edit Quiz - QuizMaster';
require_once 'includes/auth.php';
require_once 'includes/quiz_functions.php';
require_login();

$quizId = (int)($_GET['id'] ?? 0);

// Only the owner can open this page
function load_quiz($pdo, $quizId) {
    $stmt = $pdo->prepare('SELECT * FROM quizzes WHERE id = ? AND user_id = ?');
    $stmt->execute([$quizId, $_SESSION['user_id']]);
    return $stmt->fetch();
}
$quiz = load_quiz($pdo, $quizId);
if (!$quiz) {
    http_response_code(404);
    die('Quiz not found.');
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$validCategoryIds = array_map('intval', array_column($categories, 'id'));
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_details') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $timeLimit = (int)($_POST['time_limit'] ?? 0);

        if ($title === '' || mb_strlen($title) > 200) {
            $error = 'Enter a quiz title (max 200 characters).';
        } elseif (!in_array($categoryId, $validCategoryIds, true)) {
            $error = 'Please choose a category.';
        } elseif ($timeLimit < 1 || $timeLimit > 180) {
            $error = 'Time limit must be between 1 and 180 minutes.';
        } else {
            $pdo->prepare('UPDATE quizzes SET title = ?, description = ?, category_id = ?, time_limit_minutes = ? WHERE id = ? AND user_id = ?')
                ->execute([$title, $description, $categoryId, $timeLimit, $quizId, $_SESSION['user_id']]);
            $msg = 'Quiz details updated.';
        }
    } elseif ($action === 'add_question') {
        $q = clean_question($_POST);
        if ($q === null) {
            $error = 'Fill in the question, all four options, and the correct answer.';
        } else {
            $pdo->prepare('INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$quizId, $q['text'], $q['a'], $q['b'], $q['c'], $q['d'], $q['correct']]);
            $msg = 'Question added.';
        }
    } elseif ($action === 'delete_question') {
        $qid = (int)($_POST['question_id'] ?? 0);
        $pdo->prepare('DELETE FROM questions WHERE id = ? AND quiz_id = ?')->execute([$qid, $quizId]);
        $msg = 'Question deleted.';
    }
    $quiz = load_quiz($pdo, $quizId);   // reload so the page shows fresh data
}

$stmt = $pdo->prepare('SELECT * FROM questions WHERE quiz_id = ? ORDER BY id');
$stmt->execute([$quizId]);
$questions = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Edit Quiz</h2>
    <a href="my_quizzes.php" class="btn btn-outline-secondary">&larr; My Quizzes</a>
</div>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<!-- Quiz details -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Details</h5>
        <form method="POST">
            <input type="hidden" name="action" value="update_details">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($quiz['title']) ?>" maxlength="200" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?= e($quiz['description']) ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (int)$quiz['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Time limit (minutes)</label>
                    <input type="number" name="time_limit" class="form-control" value="<?= (int)$quiz['time_limit_minutes'] ?>" min="1" max="180" required>
                </div>
            </div>
            <button class="btn btn-primary">Save details</button>
        </form>
    </div>
</div>

<!-- Existing questions -->
<h5 class="mb-3">Questions (<?= count($questions) ?>)</h5>
<?php foreach ($questions as $i => $q): ?>
    <div class="card shadow-sm mb-2">
        <div class="card-body d-flex justify-content-between align-items-start">
            <div>
                <strong>Q<?= $i + 1 ?>. <?= e($q['question_text']) ?></strong>
                <div class="small mt-1">
                    <?php foreach (['a', 'b', 'c', 'd'] as $opt): ?>
                        <div class="<?= $opt === $q['correct_option'] ? 'text-success fw-bold' : 'text-muted' ?>">
                            <?= strtoupper($opt) ?>. <?= e($q['option_' . $opt]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <form method="POST" onsubmit="return confirm('Delete this question?');">
                <input type="hidden" name="action" value="delete_question">
                <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$questions): ?>
    <div class="alert alert-warning">This quiz has no questions yet, so nobody can take it.</div>
<?php endif; ?>

<!-- Add a question -->
<div class="card shadow-sm mt-4">
    <div class="card-body">
        <h5 class="mb-3">Add a question</h5>
        <form method="POST">
            <input type="hidden" name="action" value="add_question">
            <textarea name="text" class="form-control mb-2" rows="2" placeholder="Question text" required></textarea>
            <div class="row g-2">
                <div class="col-md-6"><input type="text" name="a" class="form-control" placeholder="Option A" required></div>
                <div class="col-md-6"><input type="text" name="b" class="form-control" placeholder="Option B" required></div>
                <div class="col-md-6"><input type="text" name="c" class="form-control" placeholder="Option C" required></div>
                <div class="col-md-6"><input type="text" name="d" class="form-control" placeholder="Option D" required></div>
            </div>
            <div class="mt-2 mb-3">
                <label class="form-label small mb-1">Correct answer</label>
                <select name="correct" class="form-select w-auto" required>
                    <option value="">Choose...</option>
                    <option value="a">A</option><option value="b">B</option>
                    <option value="c">C</option><option value="d">D</option>
                </select>
            </div>
            <button class="btn btn-primary">Add question</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>