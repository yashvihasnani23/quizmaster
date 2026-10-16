<?php
$pageTitle = 'Manage Quizzes - Admin';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['quiz_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE quizzes SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
        flash('success', 'Quiz status updated.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$id]);
        flash('success', 'Quiz deleted.');
    }
    header('Location: quizzes.php');
    exit;
}

$quizzes = $pdo->query(
    'SELECT q.id, q.title, q.is_published, q.created_at, c.name AS category, u.name AS author,
            (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count,
            (SELECT COUNT(*) FROM attempts  WHERE quiz_id = q.id) AS attempt_count
     FROM quizzes q
     JOIN categories c ON c.id = q.category_id
     JOIN users u ON u.id = q.user_id
     ORDER BY q.created_at DESC, q.id DESC'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<div class="table-responsive">
    <table class="table table-striped align-middle bg-white shadow-sm">
        <thead>
            <tr><th>Title</th><th>Author</th><th>Category</th><th>Questions</th><th>Attempts</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($quizzes as $q): ?>
            <tr>
                <td><?= e($q['title']) ?></td>
                <td><?= e($q['author']) ?></td>
                <td><?= e($q['category']) ?></td>
                <td><?= (int)$q['question_count'] ?></td>
                <td><?= (int)$q['attempt_count'] ?></td>
                <td>
                    <span class="badge bg-<?= $q['is_published'] ? 'success' : 'secondary' ?>">
                        <?= $q['is_published'] ? 'Published' : 'Draft' ?>
                    </span>
                </td>
                <td class="text-nowrap">
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
        <?php if (!$quizzes): ?><tr><td colspan="7" class="text-muted">No quizzes yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>