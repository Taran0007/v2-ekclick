<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Redirect based on user role
$role = getUserRole();

switch ($role) {
    case 'admin':
        redirect('admin/index.php');
        break;
    case 'vendor':
        redirect('vendor/index.php');
        break;
    case 'delivery':
        redirect('delivery/index.php');
        break;
    case 'user':
        redirect('customer/index.php');
        break;
    default:
        redirect('login.php');
        break;
}
?>
