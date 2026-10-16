<?php
// Load the database connection and start the session (only runs once)
require_once __DIR__ . '/db.php';

// Base URL of the project, so links work from any folder (including admin/ later)
$base = '/quizmaster/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'QuizMaster') ?></title>
    <!-- Bootstrap 5: ready-made styling so you don't write much CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>index.php">QuizMaster</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Shown when logged in -->
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base ?>admin/index.php">Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>my_quizzes.php">My Quizzes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>attempts.php">My Attempts</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>leaderboard.php">Leaderboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>quizzes.php">Quizzes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>logout.php">Logout (<?= e($_SESSION['name']) ?>)</a></li>
                <?php else: ?>
                    <!-- Shown when logged out -->
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base ?>register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">