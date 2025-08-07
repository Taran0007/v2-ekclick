<?php
// Cloudflare API configuration
define('API_URL', 'https://your-worker.your-subdomain.workers.dev/api');

// Site configuration
define('SITE_NAME', 'Ek-Click');
define('SITE_URL', 'http://localhost/php-deliverease/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Session configuration
session_start();

// API Helper function
function callApi($endpoint, $method = 'GET', $data = null) {
    $ch = curl_init(API_URL . '/' . $endpoint);
    
    $headers = ['Content-Type: application/json'];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($statusCode >= 400) {
        error_log("API Error ($statusCode): $response");
        return null;
    }
    
    return json_decode($response, true);
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

// File upload function remains the same
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
