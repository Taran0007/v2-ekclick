<?php
// Environment detection
$env = getenv('APP_ENV') ?: 'production';
$is_production = $env === 'production';

// Load environment-specific configuration
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'eclick');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Site configuration
define('SITE_NAME', 'Ek-Click');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/php-deliverease');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if ($is_production) {
    ini_set('session.cookie_secure', 1);
}
session_start();

// Error reporting
if ($is_production) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Security headers
if ($is_production) {
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    if (!$is_production) {
        die("Connection failed: " . $e->getMessage());
    } else {
        die("Database connection error. Please try again later.");
    }
}

// Helper Functions
function redirect($path) {
    header("Location: " . SITE_URL . "/" . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Create environment file template
if (!$is_production && !file_exists(__DIR__ . '/env.php')) {
    $env_template = <<<'EOT'
<?php
// Database Settings
putenv('DB_HOST=localhost');
putenv('DB_NAME=eclick');
putenv('DB_USER=root');
putenv('DB_PASS=');

// Site Settings
putenv('SITE_URL=http://localhost/php-deliverease');
putenv('APP_ENV=local');
EOT;
    file_put_contents(__DIR__ . '/env.php', $env_template);
}
