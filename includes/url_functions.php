<?php
// URL and redirection functions
function getBaseUrl() {
    return rtrim(SITE_URL, '/');
}

function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

function redirect($path) {
    if (strpos($path, 'http') === 0) {
        $url = $path;
    } else {
        // Get base URL without any trailing slash
        $baseUrl = rtrim(getBaseUrl(), '/');
        // Clean the path by ensuring it starts with a slash but doesn't have double slashes
        $cleanPath = '/' . ltrim($path, '/');
        $url = $baseUrl . $cleanPath;
        error_log("Redirecting to: " . $url);
    }
    header("Location: " . $url);
    exit();
}

function getAssetUrl($path) {
    return getBaseUrl() . '/' . ltrim($path, '/');
}

function isCloudflare() {
    return isset($_SERVER["HTTP_CF_CONNECTING_IP"]) || isset($_SERVER["HTTP_CF_VISITOR"]);
}

function getRelativeUrl($path) {
    return str_replace(getBaseUrl(), '', $path);
}
