<?php
$pageTitle = 'My Quizzes - QuizMaster';
require_once 'includes/auth.php';
require_login();

// Delete or publish/unpublish. "AND user_id = ?" means you can only touch your own quizzes.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['quiz_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM quizzes WHERE id = ? AND user_id = ?')->execute([$id, $_SESSION['user_id']]);
    } elseif ($action === 'toggle') {
        $pdo->prepare('UPDATE quizzes SET is_published = 1 - is_published WHERE id = ? AND user_id = ?')->execute([$id, $_SESSION['user_id']]);
    }
    header('Location: my_quizzes.php');
    exit;
}

$stmt = $pdo->prepare('SELECT q.id, q.title, q.is_published, q.created_at, c.name AS category,
                              (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
                              (SELECT COUNT(*) FROM attempts WHERE quiz_id = q.id) AS attempt_count
                       FROM quizzes q
                       JOIN categories c ON c.id = q.category_id
                       WHERE q.user_id = ?
                       ORDER BY q.created_at DESC, q.id DESC');
$stmt->execute([$_SESSION['user_id']]);
$quizzes = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">My Quizzes</h2>
    <a href="create_quiz.php" class="btn btn-primary">+ Create Quiz</a>
</div>

<?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success">Quiz created and published!</div>
<?php endif; ?>

<?php if (!$quizzes): ?>
    <div class="alert alert-info">You haven't created any quizzes yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle bg-white shadow-sm">
            <thead>
                <tr><th>Title</th><th>Category</th><th>Questions</th><th>Attempts</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($quizzes as $q): ?>
                <tr>
                    <td><?= e($q['title']) ?></td>
                    <td><?= e($q['category']) ?></td>
                    <td><?= (int)$q['question_count'] ?></td>
                    <td><?= (int)$q['attempt_count'] ?></td>
                    <td>
                        <span class="badge bg-<?= $q['is_published'] ? 'success' : 'secondary' ?>">
                            <?= $q['is_published'] ? 'Published' : 'Draft' ?>
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="edit_quiz.php?id=<?= (int)$q['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="quiz_id" value="<?= (int)$q['id'] ?>">
                            <input type="hidden" name="action" value="toggle">
                            <button class="btn btn-sm btn-outline-secondary"><?= $q['is_published'] ? 'Unpublish' : 'Publish' ?></button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this quiz and all its attempts?');">
                            <input type="hidden" name="quiz_id" value="<?= (int)$q['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>