<?php
$pageTitle = 'Manage Categories - Admin';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);

    try {
        if ($action === 'add') {
            if ($name === '' || mb_strlen($name) > 100) {
                flash('danger', 'Enter a category name (max 100 characters).');
            } else {
                $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$name]);
                flash('success', 'Category added.');
            }
        } elseif ($action === 'rename') {
            if ($name === '' || mb_strlen($name) > 100) {
                flash('danger', 'Enter a category name (max 100 characters).');
            } else {
                $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?')->execute([$name, $id]);
                flash('success', 'Category renamed.');
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM quizzes WHERE category_id = ?');
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                flash('danger', 'Cannot delete a category that still has quizzes.');
            } else {
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                flash('success', 'Category deleted.');
            }
        }
    } catch (PDOException $e) {
        // 23000 = integrity violation, here a duplicate name (the column is UNIQUE)
        flash('danger', $e->getCode() === '23000' ? 'That category name already exists.' : 'Database error.');
    }
    header('Location: categories.php');
    exit;
}

$categories = $pdo->query(
    'SELECT c.id, c.name, COUNT(q.id) AS quiz_count
     FROM categories c LEFT JOIN quizzes q ON q.category_id = c.id
     GROUP BY c.id, c.name ORDER BY c.name'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<form method="POST" class="row g-2 mb-4">
    <input type="hidden" name="action" value="add">
    <div class="col-sm-6 col-md-4">
        <input type="text" name="name" class="form-control" placeholder="New category name" maxlength="100" required>
    </div>
    <div class="col-auto"><button class="btn btn-primary">Add category</button></div>
</form>

<div class="table-responsive">
    <table class="table table-striped align-middle bg-white shadow-sm">
        <thead><tr><th>Name</th><th>Quizzes</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="rename">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <input type="text" name="name" class="form-control form-control-sm" value="<?= e($c['name']) ?>" maxlength="100" required>
                        <button class="btn btn-sm btn-outline-primary">Rename</button>
                    </form>
                </td>
                <td><?= (int)$c['quiz_count'] ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Delete this category?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>