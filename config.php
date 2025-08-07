<?php
// First set up environment and URLs
$is_cloudflare = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) || isset($_SERVER["HTTP_CF_VISITOR"]);
$environment = getenv('ENVIRONMENT') ?: ($is_cloudflare ? 'production' : 'development');
// for is localhost on line 104 defined
$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

// Debug request information
error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP_HOST: " . $_SERVER['HTTP_HOST']);


// Define Base URL (New by TJ)

define('BASE_URL', 'http://localhost/php-deliverease/'); // Or your live domain

// Set up basic configurations
if (!defined('SITE_URL')) {
    if ($is_cloudflare) {
        $protocol = isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // For Cloudflare tunnel, we need to keep the base path
        $script_path = $_SERVER['SCRIPT_NAME'];
        $base_path = '/php-deliverease';  // Hardcode the base path for tunnel
        
        define('SITE_URL', $protocol . '://' . $host . $base_path);
        error_log("Cloudflare SITE_URL: " . SITE_URL);
    } else {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        $base_path = str_replace('/index.php', '', $script_name);
        if ($base_path === $script_name) { // If index.php is not in the path
            $base_path = rtrim(dirname($script_name), '/');
        }
        define('SITE_URL', $protocol . '://' . $host . $base_path);
        error_log("Local SITE_URL: " . SITE_URL);
    }
}

// Database settings
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'eclick');
define('DB_USER', 'root');
define('DB_PASS', '');

// Now include URL functions after SITE_URL is defined
require_once __DIR__ . '/includes/url_functions.php';

// Site configuration
define('SITE_NAME', 'Ek-Click');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Additional security for Cloudflare
if ($environment === 'production') {
    // Trust Cloudflare's IP
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
}

// Session configuration
session_start();

// Error reporting (disable(idk)in production)
if ($environment === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Timezone
date_default_timezone_set('UTC');

// Database connection with improved error handling
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5, // 5 seconds timeout
        PDO::ATTR_PERSISTENT => false // Disable persistent connections
    ];

    // Attempt database connection
    $retries = 3;
    $connected = false;
    $last_error = null;

    while ($retries > 0 && !$connected) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                $options
            );
            $connected = true;
        } catch (PDOException $e) {
            $last_error = $e;
            $retries--;
            if ($retries > 0) {
                sleep(1); // Wait 1 second before retrying
            }
        }
    }

    if (!$connected) {
        // Log the error
        error_log("Database connection failed: " . $last_error->getMessage());
        
        if ($is_localhost) {
            die("Database connection failed: " . $last_error->getMessage());
        } else {
            // Show generic error in production
            die("Database connection error. Please try again later.");
        }
    }
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    die("An unexpected error occurred. Please try again later.");
}

// Helper functions
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
