<?php
$pageTitle = 'My Attempts - QuizMaster';
require_once 'includes/auth.php';
require_login();

// All attempts by the logged-in user, newest first
$stmt = $pdo->prepare('SELECT a.id, a.score, a.total, a.time_taken_sec, a.created_at, q.title
                       FROM attempts a
                       JOIN quizzes q ON q.id = a.quiz_id
                       WHERE a.user_id = ?
                       ORDER BY a.created_at DESC, a.id DESC');
$stmt->execute([$_SESSION['user_id']]);
$attempts = $stmt->fetchAll();

// Simple summary numbers
$count = count($attempts);
$avg = 0;
if ($count > 0) {
    $sum = 0;
    foreach ($attempts as $a) {
        $sum += $a['total'] > 0 ? ($a['score'] / $a['total'] * 100) : 0;
    }
    $avg = round($sum / $count);
}

require_once 'includes/header.php';
?>

<h2 class="mb-3">My Attempts</h2>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center"><div class="card-body">
            <div class="fs-3 fw-bold"><?= $count ?></div>
            <div class="text-muted small">Quizzes taken</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm text-center"><div class="card-body">
            <div class="fs-3 fw-bold"><?= $avg ?>%</div>
            <div class="text-muted small">Average score</div>
        </div></div>
    </div>
</div>

<?php if (!$attempts): ?>
    <div class="alert alert-info">You haven't taken any quizzes yet. <a href="quizzes.php">Browse quizzes</a></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle bg-white shadow-sm">
            <thead>
                <tr>
                    <th>Quiz</th>
                    <th>Score</th>
                    <th>%</th>
                    <th>Time</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($attempts as $a): ?>
                <?php $pct = $a['total'] > 0 ? round($a['score'] / $a['total'] * 100) : 0; ?>
                <tr>
                    <td><?= e($a['title']) ?></td>
                    <td><?= (int)$a['score'] ?> / <?= (int)$a['total'] ?></td>
                    <td>
                        <span class="badge bg-<?= $pct >= 70 ? 'success' : ($pct >= 40 ? 'warning' : 'danger') ?>">
                            <?= $pct ?>%
                        </span>
                    </td>
                    <td><?= intdiv((int)$a['time_taken_sec'], 60) ?>m <?= (int)$a['time_taken_sec'] % 60 ?>s</td>
                    <td><?= e(date('d M Y, H:i', strtotime($a['created_at']))) ?></td>
                    <td><a href="result.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary">Review</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>