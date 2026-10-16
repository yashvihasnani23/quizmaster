<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,       // JavaScript cannot read the session cookie
        'samesite' => 'Lax',      // cookie is not sent on cross-site form posts
    ]);
    session_start();
}

// Try to connect to the database
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=quizmaster;charset=utf8mb4',  // server, database name
        'root',   // XAMPP default username
        '',       // XAMPP default password is empty
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // show errors instead of failing silently
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // rows come back as arrays like $row['name']
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed.');
}

// Helper to safely print user text on a page (prevents XSS attacks)
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// ---- CSRF protection ----
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Every POST request must carry the right token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        die('Invalid or expired form. Go back, refresh the page, and try again.');
    }
}