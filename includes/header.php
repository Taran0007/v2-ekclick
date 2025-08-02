<?php
// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_role = getUserRole();
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - E-CLICK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6B6B;
            --secondary-color: #4ECDC4;
            --dark-color: #2C3E50;
            --light-bg: #F8F9FA;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
        }
        
        .sidebar {
            background: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
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
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: var(--light-bg);
            border-left-color: var(--primary-color);
            padding-left: 30px;
        }
        
        .sidebar-menu a.active {
            background: var(--light-bg);
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
            background: white;
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
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
            <h4><i class="fas fa-truck"></i> E-CLICK</h4>
            <small><?php echo ucfirst($user_role); ?> Panel</small>
        </div>
        
        <div class="sidebar-menu">
            <?php if ($user_role == 'admin'): ?>
                <a href="index.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php" class="<?php echo $current_page == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="vendors.php" class="<?php echo $current_page == 'vendors' ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> Vendors
                </a>
                <a href="orders.php" class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="disputes.php" class="<?php echo $current_page == 'disputes' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i> Disputes
                </a>
                <a href="settings.php" class="<?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            <?php elseif ($user_role == 'vendor'): ?>
                <a href="index.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="products.php" class="<?php echo $current_page == 'products' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="orders.php" class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="earnings.php" class="<?php echo $current_page == 'earnings' ? 'active' : ''; ?>">
                    <i class="fas fa-dollar-sign"></i> Earnings
                </a>
                <a href="shop-settings.php" class="<?php echo $current_page == 'shop-settings' ? 'active' : ''; ?>">
                    <i class="fas fa-store-alt"></i> Shop Settings
                </a>
            <?php elseif ($user_role == 'delivery'): ?>
                <a href="index.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="available-orders.php" class="<?php echo $current_page == 'available-orders' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Available Orders
                </a>
                <a href="active-deliveries.php" class="<?php echo $current_page == 'active-deliveries' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Active Deliveries
                </a>
                <a href="delivery-history.php" class="<?php echo $current_page == 'delivery-history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Delivery History
                </a>
                <a href="earnings.php" class="<?php echo $current_page == 'earnings' ? 'active' : ''; ?>">
                    <i class="fas fa-dollar-sign"></i> Earnings
                </a>
            <?php else: // Customer ?>
                <a href="index.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="browse.php" class="<?php echo $current_page == 'browse' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> Browse Shops
                </a>
                <a href="custom-order.php" class="<?php echo $current_page == 'custom-order' ? 'active' : ''; ?>">
                    <i class="fas fa-plus"></i> Custom Order
                </a>
                <a href="cart.php" class="<?php echo $current_page == 'cart' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a>
                <a href="orders.php" class="<?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> My Orders
                </a>
                <a href="favorites.php" class="<?php echo $current_page == 'favorites' ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i> Favorites
                </a>
                <a href="profile.php" class="<?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Profile
                </a>
            <?php endif; ?>
            
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-navbar">
            <h3><?php echo $page_title ?? 'Dashboard'; ?></h3>
            <div class="user-info">
                <span>Welcome, <strong><?php echo $full_name; ?></strong></span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>
            </div>
        </div>
