<?php
$pageTitle = 'Browse Quizzes - QuizMaster';
require_once 'includes/auth.php';
require_login();

// Category filter from the URL, e.g. quizzes.php?category=2
$categoryId = (int)($_GET['category'] ?? 0);

// Load all categories for the filter buttons
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Load published quizzes, with category name, author name and question count
$sql = 'SELECT q.id, q.title, q.description, q.time_limit_minutes,
               c.name AS category, u.name AS author,
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count
        FROM quizzes q
        JOIN categories c ON c.id = q.category_id
        JOIN users u ON u.id = q.user_id
        WHERE q.is_published = 1';
$params = [];

if ($categoryId > 0) {
    $sql .= ' AND q.category_id = ?';
    $params[] = $categoryId;
}
$sql .= ' ORDER BY q.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<h2 class="mb-3">Browse Quizzes</h2>

<!-- Category filter -->
<div class="mb-4">
    <a href="quizzes.php" class="btn btn-sm <?= $categoryId === 0 ? 'btn-primary' : 'btn-outline-primary' ?> me-1">All</a>
    <?php foreach ($categories as $cat): ?>
        <a href="quizzes.php?category=<?= (int)$cat['id'] ?>"
           class="btn btn-sm <?= $categoryId === (int)$cat['id'] ? 'btn-primary' : 'btn-outline-primary' ?> me-1">
            <?= e($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!$quizzes): ?>
    <div class="alert alert-info">No quizzes found in this category yet.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($quizzes as $quiz): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-secondary align-self-start mb-2"><?= e($quiz['category']) ?></span>
                        <h5 class="card-title"><?= e($quiz['title']) ?></h5>
                        <p class="text-muted small flex-grow-1"><?= e($quiz['description']) ?></p>
                        <p class="small mb-3">
                            <?= (int)$quiz['question_count'] ?> questions &middot;
                            <?= (int)$quiz['time_limit_minutes'] ?> min<br>
                            By <?= e($quiz['author']) ?>
                        </p>
                        <?php if ($quiz['question_count'] > 0): ?>
                            <a href="quiz.php?id=<?= (int)$quiz['id'] ?>" class="btn btn-primary">Start Quiz</a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>No questions yet</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>