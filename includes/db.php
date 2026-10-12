<?php
// Start a session so we can remember logged-in users on every page
session_start();

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