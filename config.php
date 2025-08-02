<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'eclick');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'E-CLICK');
define('SITE_URL', 'http://localhost/php-deliverease');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Session configuration
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateOrderNumber() {
    return 'ORD' . date('Ymd') . rand(1000, 9999);
}

function uploadFile($file, $type = 'products') {
    $target_dir = UPLOAD_PATH . $type . '/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $type . '/' . $new_filename;
    }
    return false;
}
?>
