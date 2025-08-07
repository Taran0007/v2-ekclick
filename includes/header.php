<?php
require_once __DIR__ . '/url_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(getBaseUrl() . '/login.php');
}

$user_role = getUserRole();
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];

// Get current page for active menu highlighting
$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '.php');
$current_page = basename($current_url, '.php');

// Get the current directory (admin, vendor, delivery, etc.)
$current_dir = dirname($_SERVER['PHP_SELF']);
$current_dir = basename($current_dir);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - Ek-Click</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Light Theme Variables */
            --primary-color: #f14242ff;
            --secondary-color: #4ECDC4;
            --dark-color: #2C3E50;
            --light-bg: #F8F9FA;
            --text-color: #2C3E50;
            --bg-color: #F8F9FA;
            --card-bg: #FFFFFF;
            --border-color: #E9ECEF;
            --shadow-color: rgba(0,0,0,0.05);
            --hover-bg: #F8F9FA;
        }
        
        :root[data-theme="dark"] {
            /* Dark Theme Variables */
            --primary-color: #e73f3fff;
            --secondary-color: #4ECDC4;
            --dark-color: #E9ECEF;
            --light-bg: #1A1D21;
            --text-color: #E9ECEF;
            --bg-color: #1A1D21;
            --card-bg: #2C3034;
            --border-color: #373B3E;
            --shadow-color: rgba(0,0,0,0.2);
            --hover-bg: #2C3034;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .sidebar {
            background: var(--card-bg);
            min-height: 100vh;
            box-shadow: 2px 0 10px var(--shadow-color);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-weight: 700;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 15px 25px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: var(--hover-bg);
            border-left-color: var(--primary-color);
            padding-left: 30px;
        }
        
        .sidebar-menu a.active {
            background: var(--hover-bg);
            border-left-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .sidebar-menu i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .top-navbar {
            background: var(--card-bg);
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            box-shadow: 0 2px 10px var(--shadow-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Dark Mode Styles */
        .dashboard-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .table {
            color: var(--text-color);
        }

        .modal-content {
            background: var(--card-bg);
            color: var(--text-color);
        }

        .form-control, .form-select {
            background-color: var(--bg-color);
            border-color: var(--border-color);
            color: var(--text-color);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--bg-color);
            border-color: var(--primary-color);
            color: var(--text-color);
        }

        /* Theme Toggle Button */
        .theme-toggle {
            background: transparent;
            border: none;
            color: var(--text-color);
            padding: 5px;
            margin-left: 15px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-toggle i {
            font-size: 1.2rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px var(--shadow-color);
            margin-bottom: 20px;
            transition: transform 0.3s;
            border: 1px solid var(--border-color);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            color: var(--text-color);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        /* Additional stat card styles for dark mode */
        :root[data-theme="dark"] .stat-card {
            background: var(--card-bg);
        }
        
        :root[data-theme="dark"] .stat-number {
            color: #fff;
            text-shadow: 0 0 10px rgba(255,255,255,0.1);
        }
        
        :root[data-theme="dark"] .text-muted {
            color: #a9b6c2 !important;
        }
        
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary-custom:hover {
            background: #FF5252;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .table-custom {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-custom thead {
            background: var(--light-bg);
        }
        
        .badge-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Currency symbol styles */
        .currency-symbol {
            font-family: 'Arial Unicode MS', 'Segoe UI', sans-serif;
        }
        
        /* Replace $ with ₹ for all price elements */
        .price::before {
            content: '₹';
            font-family: 'Arial Unicode MS', 'Segoe UI', sans-serif;
            margin-right: 1px;
        }
        
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-truck"></i> Ek-Click</h4>
            <small><?php echo ucfirst($user_role); ?> Panel</small>
        </div>
        
        <div class="sidebar-menu">
            <?php if ($user_role == 'admin'): ?>
                <a href="<?php echo getBaseUrl(); ?>/admin/index.php" class="<?php echo ($current_dir == 'admin' && $current_page == 'index') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?php echo getBaseUrl(); ?>/admin/users.php" class="<?php echo ($current_dir == 'admin' && $current_page == 'users') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="<?php echo getBaseUrl(); ?>/admin/vendors.php" class="<?php echo ($current_dir == 'admin' && $current_page == 'vendors') ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> Vendors
                </a>
                <a href="<?php echo getBaseUrl(); ?>/admin/orders.php" class="<?php echo ($current_dir == 'admin' && $current_page == 'orders') ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="<?php echo getBaseUrl(); ?>/admin/disputes.php" class="<?php echo ($current_dir == 'admin' && $current_page == 'disputes') ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i> Disputes
                </a>
                <a href="<?php echo getBaseUrl(); ?>/admin/settings.php" class="<?php echo ($current_dir == 'admin' && $current_page == 'settings') ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            <?php elseif ($user_role == 'vendor'): ?>
                <a href="<?php echo getBaseUrl(); ?>/vendor/index.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?php echo getBaseUrl(); ?>/vendor/products.php" class="<?php echo $current_page == 'products' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="<?php echo getBaseUrl(); ?>/vendor/orders.php" class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="<?php echo getBaseUrl(); ?>/vendor/earnings.php" class="<?php echo $current_page == 'earnings' ? 'active' : ''; ?>">
                    <i class="fas fa-rupee-sign"></i> Earnings
                </a>
                <a href="<?php echo getBaseUrl(); ?>/vendor/shop-settings.php" class="<?php echo $current_page == 'shop-settings' ? 'active' : ''; ?>">
                    <i class="fas fa-store-alt"></i> Shop Settings
                </a>
            <?php elseif ($user_role == 'delivery'): ?>
                <a href="<?php echo getBaseUrl(); ?>/delivery/index.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="<?php echo getBaseUrl(); ?>/delivery/available-orders.php" class="<?php echo $current_page == 'available-orders' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Available Orders
                </a>
                <a href="<?php echo getBaseUrl(); ?>/delivery/active-deliveries.php" class="<?php echo $current_page == 'active-deliveries' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Active Deliveries
                </a>
                <a href="<?php echo getBaseUrl(); ?>/delivery/delivery-history.php" class="<?php echo $current_page == 'delivery-history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Delivery History
                </a>
                <a href="<?php echo getBaseUrl(); ?>/delivery/earnings.php" class="<?php echo $current_page == 'earnings' ? 'active' : ''; ?>">
                    <i class="fas fa-rupee-sign"></i> Earnings
                </a>
            <?php else: // Customer ?>
                <a href="<?php echo getBaseUrl(); ?>/customer/index.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="<?php echo getBaseUrl(); ?>/customer/browse.php" class="<?php echo $current_page == 'browse' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> Browse Shops
                </a>
                <a href="<?php echo getBaseUrl(); ?>/customer/custom-order.php" class="<?php echo $current_page == 'custom-order' ? 'active' : ''; ?>">
                    <i class="fas fa-plus"></i> Custom Order
                </a>
                <a href="<?php echo getBaseUrl(); ?>/customer/cart.php" class="<?php echo $current_page == 'cart' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a>
                <a href="<?php echo getBaseUrl(); ?>/customer/orders.php" class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> My Orders
                </a>
                <a href="<?php echo getBaseUrl(); ?>/customer/favorites.php" class="<?php echo $current_page == 'favorites' ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i> Favorites
                </a>
                <a href="<?php echo getBaseUrl(); ?>/customer/profile.php" class="<?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
            <?php endif; ?>
            
            <a href="<?php echo getBaseUrl(); ?>/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h3><?php echo $page_title ?? 'Dashboard'; ?></h3>
            <div class="d-flex align-items-center">
                <div class="user-avatar me-2">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>
                <span class="me-3">Welcome, <strong><?php echo $full_name; ?></strong></span>
                <button class="theme-toggle" id="themeToggle" title="Toggle theme">
                    <i class="fas fa-sun"></i>
                </button>
            </div>
        </div>

        <!-- Theme Toggle Script -->
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const themeToggle = document.getElementById('themeToggle');
                const themeIcon = themeToggle.querySelector('i');
                
                // Check for saved theme preference or default to light
                const currentTheme = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', currentTheme);
                updateThemeIcon(currentTheme);
                
                // Toggle theme on button click
                themeToggle.addEventListener('click', () => {
                    const currentTheme = document.documentElement.getAttribute('data-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    updateThemeIcon(newTheme);
                });
                
                // Update icon based on theme
                function updateThemeIcon(theme) {
                    if (theme === 'dark') {
                        themeIcon.classList.remove('fa-sun');
                        themeIcon.classList.add('fa-moon');
                    } else {
                        themeIcon.classList.remove('fa-moon');
                        themeIcon.classList.add('fa-sun');
                    }
                }
            });
        </script>
