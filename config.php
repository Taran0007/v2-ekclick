<?php
// Start session
session_start();

// Auto-detect BASE URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Full dynamic BASE URL
define('BASE_URL', $protocol . "://" . $host . $base_path . '/');
define('SITE_URL', BASE_URL);

// Debug log (optional)
// error_log("BASE_URL: " . BASE_URL);
// error_log("SITE_URL: " . SITE_URL);

// Database settings
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'eclick');      // Change if needed
define('DB_USER', 'root');        // Default XAMPP
define('DB_PASS', '');            // Default XAMPP

// File upload path
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Timezone setting
date_default_timezone_set('UTC');

// Error reporting (always ON for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_PERSISTENT => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Website settings
define('SITE_NAME', 'Ek-Click');

// === HELPER FUNCTIONS ===

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateOrderNumber() {
    return 'ORD' . date('Ymd') . rand(1000, 9999);
}

function uploadFile($file, $type = 'products') {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
    $max_size = 2 * 1024 * 1024; // 2MB

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return false; // Invalid file type
    }

    if ($file['size'] > $max_size) {
        return false; // File too large
    }

    $target_dir = UPLOAD_PATH . $type . '/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $type . '/' . $new_filename;
    }

    return false;
}
?>