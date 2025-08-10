<?php
// URL and redirection functions
function getBaseUrl() {
    return defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
}

function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function redirect($path) {
    // Determine full redirect URL
    if (strpos($path, 'http') === 0) {
        $url = $path; // Full external URL
    } else {
        $baseUrl = getBaseUrl();
        $cleanPath = '/' . ltrim($path, '/');
        $url = $baseUrl . $cleanPath;
    }

    // Check 1: Avoid redirecting to the current page (infinite loop)
    $currentUrl = getCurrentUrl();
    if ($currentUrl === $url) {
        error_log("Redirect skipped: Target is current page");
        return; // Don't redirect
    }

    // Check 2: Ensure valid URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        error_log("Invalid redirect URL: " . $url);
        return; // Invalid URL, skip redirect
    }

    // Redirect and exit
    error_log("Redirecting to: " . $url);
    header("Location: " . $url);
    exit();
}

function getAssetUrl($path) {
    return (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/' . ltrim($path, '/');
}

function getRelativeUrl($path) {
    return str_replace(getBaseUrl(), '', $path);
}
