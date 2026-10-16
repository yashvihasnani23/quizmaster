<?php
$pageTitle = 'Manage Users - Admin';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id === (int)$_SESSION['user_id']) {
        // Stops an admin from locking themselves out
        flash('danger', 'You cannot change or delete your own account here.');
    } elseif ($action === 'set_role') {
        $role = $_POST['role'] ?? '';
        if (in_array($role, ['user', 'admin'], true)) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
            flash('success', 'Role updated.');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('success', 'User deleted.');
    }
    header('Location: users.php');
    exit;
}

$users = [];
$loadError = '';
try {
    $users = $pdo->query(
        'SELECT u.id, u.name, u.email, u.role, u.created_at,
                (SELECT COUNT(*) FROM quizzes  WHERE user_id = u.id) AS quiz_count,
                (SELECT COUNT(*) FROM attempts WHERE user_id = u.id) AS attempt_count,
                (SELECT ROUND(AVG(score / total * 100)) FROM attempts WHERE user_id = u.id AND total > 0) AS avg_score
         FROM users u
         ORDER BY u.id DESC'
    )->fetchAll();
} catch (PDOException $ex) {
    // Development only: shows the real SQL problem
    $loadError = $ex->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<?php if ($loadError): ?>
    <div class="alert alert-danger"><strong>Query failed:</strong> <?= e($loadError) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped align-middle bg-white shadow-sm">
        <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Quizzes</th><th>Attempts</th><th>Avg score</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <?php $isMe = ((int)$u['id'] === (int)$_SESSION['user_id']); ?>
            <tr>
                <td><?= e($u['name']) ?> <?= $isMe ? '<span class="badge bg-info">you</span>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'secondary' ?>"><?= e($u['role']) ?></span></td>
                <td><?= (int)$u['quiz_count'] ?></td>
                <td><?= (int)$u['attempt_count'] ?></td>
                <td><?= $u['avg_score'] !== null ? (int)$u['avg_score'] . '%' : '-' ?></td>
                <td><?= e(date('d M Y', strtotime((string)$u['created_at']))) ?></td>
                <td class="text-nowrap">
                    <?php if (!$isMe): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="action" value="set_role">
                            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                            <button class="btn btn-sm btn-outline-secondary">
                                <?= $u['role'] === 'admin' ? 'Make user' : 'Make admin' ?>
                            </button>
                        </form>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user and everything they created?');">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted small">-</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users && !$loadError): ?>
            <tr><td colspan="8" class="text-muted">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>