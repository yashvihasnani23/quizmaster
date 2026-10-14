<?php
require_once __DIR__ . '/db.php';

// Call at the top of any page that needs a logged-in user
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /quizmaster/login.php');
        exit;
    }
}

// Call on admin pages (we'll use this on Day 5)
function require_admin() {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Access denied.');
    }
}