<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(getBaseUrl() . '/login.php');
    exit;
}

// Get role
$role = getUserRole();

// Get base URL once for consistency
$baseUrl = getBaseUrl();

// Role-based redirect
switch ($role) {
    case 'admin':
        redirect($baseUrl . '/admin/index.php');
        break;
    case 'vendor':
        redirect($baseUrl . '/vendor/index.php');
        break;
    case 'delivery':
        redirect($baseUrl . '/delivery/index.php');
        break;
    case 'user':
        redirect($baseUrl . '/customer/index.php'); // Made this consistent with others
        break;
    default:
        redirect($baseUrl . '/login.php'); // Ensures safe fallback
        break;
}

exit;
?>