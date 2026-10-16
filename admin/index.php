<?php
$pageTitle = 'Admin Stats - QuizMaster';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$stats = [
    'Users'             => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'Quizzes'           => $pdo->query('SELECT COUNT(*) FROM quizzes')->fetchColumn(),
    'Published quizzes' => $pdo->query('SELECT COUNT(*) FROM quizzes WHERE is_published = 1')->fetchColumn(),
    'Questions'         => $pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
    'Attempts'          => $pdo->query('SELECT COUNT(*) FROM attempts')->fetchColumn(),
];
$avg = $pdo->query('SELECT ROUND(AVG(score / total * 100)) FROM attempts WHERE total > 0')->fetchColumn();

// Most attempted quizzes
$popular = $pdo->query(
    'SELECT q.title, COUNT(a.id) AS attempts
     FROM quizzes q LEFT JOIN attempts a ON a.quiz_id = q.id
     GROUP BY q.id, q.title
     ORDER BY attempts DESC, q.title
     LIMIT 5'
)->fetchAll();

// Attempts per category
$byCategory = $pdo->query(
    'SELECT c.name, COUNT(a.id) AS attempts
     FROM categories c
     LEFT JOIN quizzes q ON q.category_id = c.id
     LEFT JOIN attempts a ON a.quiz_id = q.id
     GROUP BY c.id, c.name
     ORDER BY attempts DESC'
)->fetchAll();
$maxCat = max(1, ...array_map('intval', array_column($byCategory, 'attempts') ?: [1]));

// Latest activity
$recent = $pdo->query(
    'SELECT u.name, q.title, a.score, a.total, a.created_at
     FROM attempts a
     JOIN users u ON u.id = a.user_id
     JOIN quizzes q ON q.id = a.quiz_id
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT 8'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/_nav.php';
?>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $label => $value): ?>
        <div class="col-6 col-md-4 col-lg">
            <div class="card shadow-sm text-center"><div class="card-body">
                <div class="fs-2 fw-bold"><?= (int)$value ?></div>
                <div class="text-muted small"><?= e($label) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
    <div class="col-6 col-md-4 col-lg">
        <div class="card shadow-sm text-center"><div class="card-body">
            <div class="fs-2 fw-bold"><?= (int)$avg ?>%</div>
            <div class="text-muted small">Average score</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h5 class="mb-3">Attempts by category</h5>
            <?php foreach ($byCategory as $c): ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small">
                        <span><?= e($c['name']) ?></span><span><?= (int)$c['attempts'] ?></span>
                    </div>
                    <div class="progress" style="height:10px">
                        <div class="progress-bar" style="width: <?= round($c['attempts'] / $maxCat * 100) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h5 class="mb-3">Most attempted quizzes</h5>
            <?php if (!$popular): ?><p class="text-muted">No quizzes yet.</p><?php endif; ?>
            <ol class="mb-0">
                <?php foreach ($popular as $p): ?>
                    <li><?= e($p['title']) ?> <span class="text-muted">(<?= (int)$p['attempts'] ?> attempts)</span></li>
                <?php endforeach; ?>
            </ol>
        </div></div>
    </div>
</div>

<h5 class="mt-4 mb-3">Recent attempts</h5>
<div class="table-responsive">
    <table class="table table-striped align-middle bg-white shadow-sm">
        <thead><tr><th>User</th><th>Quiz</th><th>Score</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['title']) ?></td>
                <td><?= (int)$r['score'] ?> / <?= (int)$r['total'] ?></td>
                <td><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="4" class="text-muted">No attempts yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>