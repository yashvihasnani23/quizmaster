<?php
$pageTitle = 'Dashboard - QuizMaster';
require_once 'includes/auth.php';
require_login();                       // kicks out anyone not logged in
require_once 'includes/header.php';
?>

<h2 class="mb-4">Welcome, <?= e($_SESSION['name']) ?>!</h2>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5>Browse Quizzes</h5>
                <p class="text-muted">Find a quiz and test yourself.</p>
                <a href="quizzes.php" class="btn btn-primary">Browse</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5>Create Quiz</h5>
                <p class="text-muted">Build your own quiz for others.</p>
                <a href="create_quiz.php" class="btn btn-primary">Create</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5>My Attempts</h5>
                <p class="text-muted">Review your past scores.</p>
                <a href="attempts.php" class="btn btn-primary">View</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>