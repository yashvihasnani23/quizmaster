<?php
$pageTitle = 'Leaderboard - QuizMaster';
require_once 'includes/auth.php';
require_login();

$quizId = (int)($_GET['quiz'] ?? 0);

// Quizzes for the dropdown
$quizzes = $pdo->query('SELECT id, title FROM quizzes WHERE is_published = 1 ORDER BY title')->fetchAll();

if ($quizId > 0) {
    // Per-quiz board: each user's best attempt (highest score, then fastest)
    $stmt = $pdo->prepare(
        'SELECT u.name, a.score, a.total, a.time_taken_sec
         FROM attempts a
         JOIN users u ON u.id = a.user_id
         WHERE a.quiz_id = ?
           AND a.id = (SELECT a2.id FROM attempts a2
                       WHERE a2.user_id = a.user_id AND a2.quiz_id = a.quiz_id
                       ORDER BY a2.score DESC, a2.time_taken_sec ASC, a2.id ASC
                       LIMIT 1)
         ORDER BY a.score DESC, a.time_taken_sec ASC
         LIMIT 10'
    );
    $stmt->execute([$quizId]);
    $rows = $stmt->fetchAll();
} else {
    // Global board: total of each user's best score on every quiz they took
    $rows = $pdo->query(
        'SELECT u.name, SUM(b.best) AS points, COUNT(*) AS quizzes_taken
         FROM (SELECT user_id, quiz_id, MAX(score) AS best
               FROM attempts GROUP BY user_id, quiz_id) b
         JOIN users u ON u.id = b.user_id
         GROUP BY u.id, u.name
         ORDER BY points DESC, quizzes_taken DESC
         LIMIT 10'
    )->fetchAll();
}

require_once 'includes/header.php';
?>

<h2 class="mb-3">Leaderboard</h2>

<form method="GET" class="mb-4">
    <select name="quiz" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
        <option value="0">Global (all quizzes)</option>
        <?php foreach ($quizzes as $q): ?>
            <option value="<?= (int)$q['id'] ?>" <?= $quizId === (int)$q['id'] ? 'selected' : '' ?>>
                <?= e($q['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-primary btn-sm">Show</button></noscript>
</form>

<?php if (!$rows): ?>
    <div class="alert alert-info">No attempts yet. Be the first!</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle bg-white shadow-sm">
            <thead>
                <tr>
                    <th style="width:70px">Rank</th>
                    <th>Player</th>
                    <?php if ($quizId > 0): ?>
                        <th>Score</th><th>Time</th>
                    <?php else: ?>
                        <th>Points</th><th>Quizzes taken</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <?php $isMe = ($r['name'] === $_SESSION['name']); ?>
                <tr class="<?= $isMe ? 'table-primary' : '' ?>">
                    <td>
                        <?php if ($i === 0): ?>🥇<?php elseif ($i === 1): ?>🥈<?php elseif ($i === 2): ?>🥉<?php else: ?><?= $i + 1 ?><?php endif; ?>
                    </td>
                    <td><?= e($r['name']) ?></td>
                    <?php if ($quizId > 0): ?>
                        <td><?= (int)$r['score'] ?> / <?= (int)$r['total'] ?></td>
                        <td><?= intdiv((int)$r['time_taken_sec'], 60) ?>m <?= (int)$r['time_taken_sec'] % 60 ?>s</td>
                    <?php else: ?>
                        <td><?= (int)$r['points'] ?></td>
                        <td><?= (int)$r['quizzes_taken'] ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>