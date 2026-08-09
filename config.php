<?php
// FILE: config.php
// No session_start() here – sessions are started in the entry scripts.

$host = 'localhost';
$db   = 'studytrack360';
$user = 'root';      // change if needed
$pass = '';          // change if needed

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Optional helper, used only if the page already started a session.
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
