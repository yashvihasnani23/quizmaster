<?php
$pageTitle = 'Create Quiz - QuizMaster';
require_once 'includes/auth.php';
require_once 'includes/quiz_functions.php';
require_login();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$validCategoryIds = array_map('intval', array_column($categories, 'id'));

$error = '';
$title = '';
$description = '';
$categoryId = 0;
$timeLimit = 10;
$postedQuestions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $timeLimit   = (int)($_POST['time_limit'] ?? 0);
    $rawQuestions = is_array($_POST['questions'] ?? null) ? $_POST['questions'] : [];
    $postedQuestions = array_values($rawQuestions);   // so the form can be refilled on error

    // Validate every question
    $clean = [];
    foreach ($rawQuestions as $q) {
        $c = clean_question($q);
        if ($c === null) { $clean = null; break; }
        $clean[] = $c;
    }

    if ($title === '' || mb_strlen($title) > 200) {
        $error = 'Enter a quiz title (max 200 characters).';
    } elseif (!in_array($categoryId, $validCategoryIds, true)) {
        $error = 'Please choose a category.';
    } elseif ($timeLimit < 1 || $timeLimit > 180) {
        $error = 'Time limit must be between 1 and 180 minutes.';
    } elseif ($clean === null) {
        $error = 'Every question needs text, four options, and a correct answer.';
    } elseif (count($clean) < 1) {
        $error = 'Add at least one question.';
    } elseif (count($clean) > 50) {
        $error = 'A quiz can have at most 50 questions.';
    } else {
        // Save the quiz and all its questions together
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO quizzes (user_id, category_id, title, description, time_limit_minutes, is_published) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$_SESSION['user_id'], $categoryId, $title, $description, $timeLimit]);
            $quizId = $pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($clean as $q) {
                $stmt->execute([$quizId, $q['text'], $q['a'], $q['b'], $q['c'], $q['d'], $q['correct']]);
            }
            $pdo->commit();

            header('Location: my_quizzes.php?created=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Could not save the quiz. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<h2 class="mb-3">Create a Quiz</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($title) ?>" maxlength="200" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"><?= e($description) ?></textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Choose...</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Time limit (minutes)</label>
                    <input type="number" name="time_limit" class="form-control" value="<?= (int)$timeLimit ?>" min="1" max="180" required>
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3">Questions</h5>
    <div id="questions"></div>

    <button type="button" class="btn btn-outline-primary mb-4" id="addBtn">+ Add question</button>
    <div>
        <button type="submit" class="btn btn-success btn-lg">Publish Quiz</button>
        <a href="my_quizzes.php" class="btn btn-link">Cancel</a>
    </div>
</form>

<script>
// Questions to refill if the server sent the form back with an error
const initial = <?= json_encode($postedQuestions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

let idx = 0;
const wrap = document.getElementById('questions');

function renumber() {
    wrap.querySelectorAll('.q-card').forEach((card, n) => {
        card.querySelector('.q-num').textContent = 'Question ' + (n + 1);
    });
}

function addQuestion(d) {
    d = d || {};
    const i = idx++;
    const card = document.createElement('div');
    card.className = 'card shadow-sm mb-3 q-card';
    card.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <h6 class="q-num mb-0"></h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-q">Remove</button>
            </div>
            <textarea name="questions[${i}][text]" class="form-control mb-2" rows="2" placeholder="Question text" required></textarea>
            <div class="row g-2">
                <div class="col-md-6"><input type="text" name="questions[${i}][a]" class="form-control" placeholder="Option A" required></div>
                <div class="col-md-6"><input type="text" name="questions[${i}][b]" class="form-control" placeholder="Option B" required></div>
                <div class="col-md-6"><input type="text" name="questions[${i}][c]" class="form-control" placeholder="Option C" required></div>
                <div class="col-md-6"><input type="text" name="questions[${i}][d]" class="form-control" placeholder="Option D" required></div>
            </div>
            <div class="mt-2">
                <label class="form-label small mb-1">Correct answer</label>
                <select name="questions[${i}][correct]" class="form-select w-auto" required>
                    <option value="">Choose...</option>
                    <option value="a">A</option><option value="b">B</option>
                    <option value="c">C</option><option value="d">D</option>
                </select>
            </div>
        </div>`;
    // Set values through the DOM (not inside the HTML string), so quotes in user text are safe
    ['text', 'a', 'b', 'c', 'd', 'correct'].forEach(k => {
        card.querySelector('[name="questions[' + i + '][' + k + ']"]').value = d[k] || '';
    });
    card.querySelector('.remove-q').addEventListener('click', () => {
        if (wrap.querySelectorAll('.q-card').length > 1) {
            card.remove();
            renumber();
        }
    });
    wrap.appendChild(card);
    renumber();
}

document.getElementById('addBtn').addEventListener('click', () => addQuestion());

if (initial.length) { initial.forEach(addQuestion); } else { addQuestion(); }
</script>

<?php require_once 'includes/footer.php'; ?>