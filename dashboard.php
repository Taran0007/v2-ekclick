<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(getBaseUrl() . '/login.php');
}

// Redirect based on user role
$role = getUserRole();

switch ($role) {
    case 'admin':
        redirect(getBaseUrl() . '/admin/index.php');
        break;
    case 'vendor':
        redirect(getBaseUrl() . '/vendor/index.php');
        break;
    case 'delivery':
        redirect(getBaseUrl() . '/delivery/index.php');
        break;
    case 'user':
        redirect('customer/index.php');
        break;
    default:
        redirect('login.php');
        break;
}
?>
