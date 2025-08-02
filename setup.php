<?php
// This file helps set up the database with demo data
// Run this file after importing database.sql

require_once 'config.php';

$message = '';
$error = '';

// Check if setup is already done
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    
    if ($user_count > 0) {
        $message = "Database already has data. Setup might have been completed already.";
    }
} catch (PDOException $e) {
    $error = "Database connection error. Make sure you've imported database.sql first.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['setup'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Create demo users with proper password hashes
        $demo_users = [
            ['admin', 'admin123', 'admin@deliverease.com', 'System Administrator', '+1234567890', 'admin', '123 Admin Street', 'New York'],
            ['vendor1', 'vendor123', 'vendor1@deliverease.com', 'Pizza Palace Owner', '+1234567891', 'vendor', '456 Vendor Avenue', 'New York'],
            ['vendor2', 'vendor123', 'vendor2@deliverease.com', 'Burger King Owner', '+1234567892', 'vendor', '789 Vendor Street', 'New York'],
            ['delivery1', 'delivery123', 'delivery1@deliverease.com', 'John Delivery Driver', '+1234567893', 'delivery', '789 Delivery Lane', 'New York'],
            ['delivery2', 'delivery123', 'delivery2@deliverease.com', 'Jane Delivery Driver', '+1234567894', 'delivery', '321 Delivery Road', 'New York'],
            ['customer1', 'customer123', 'customer1@deliverease.com', 'Jane Customer', '+1234567895', 'user', '321 Customer Road', 'New York'],
            ['customer2', 'customer123', 'customer2@deliverease.com', 'Bob Customer', '+1234567896', 'user', '654 Customer Avenue', 'New York']
        ];
        
        $user_ids = [];
        foreach ($demo_users as $user) {
            $hashed_password = password_hash($user[1], PASSWORD_DEFAULT);
            $referral_code = strtoupper(substr(md5($user[0] . time()), 0, 8));
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, role, address, city, referral_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user[0], $hashed_password, $user[2], $user[3], $user[4], $user[5], $user[6], $user[7], $referral_code]);
            
            $user_ids[$user[0]] = $pdo->lastInsertId();
        }
        
        // Create vendor profiles
        $vendors = [
            ['vendor1', 'Pizza Palace', 'Best pizzas in town! Fresh ingredients and fast delivery.', 'Restaurant', '456 Vendor Avenue', 'New York'],
            ['vendor2', 'Burger Kingdom', 'Juicy burgers and crispy fries. Your favorite fast food spot!', 'Restaurant', '789 Vendor Street', 'New York']
        ];
        
        $vendor_ids = [];
        foreach ($vendors as $vendor) {
            $stmt = $pdo->prepare("INSERT INTO vendors (user_id, shop_name, shop_description, category, address, city) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_ids[$vendor[0]], $vendor[1], $vendor[2], $vendor[3], $vendor[4], $vendor[5]]);
            $vendor_ids[$vendor[0]] = $pdo->lastInsertId();
        }
        
        // Create sample products for vendor1 (Pizza Palace)
        $pizza_products = [
            ['Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella, and basil', 'Pizza', 12.99, 50],
            ['Pepperoni Pizza', 'Loaded with pepperoni and extra cheese', 'Pizza', 14.99, 50],
            ['Vegetarian Pizza', 'Fresh vegetables with mozzarella cheese', 'Pizza', 13.99, 50],
            ['BBQ Chicken Pizza', 'BBQ sauce, grilled chicken, onions, and cilantro', 'Pizza', 16.99, 50],
            ['Garlic Bread', 'Crispy bread with garlic butter and herbs', 'Sides', 5.99, 100],
            ['Caesar Salad', 'Fresh romaine lettuce with caesar dressing', 'Salads', 8.99, 30],
            ['Coca Cola', 'Refreshing soft drink', 'Beverages', 2.99, 200],
            ['Chocolate Cake', 'Rich chocolate cake slice', 'Desserts', 6.99, 20]
        ];
        
        foreach ($pizza_products as $product) {
            $stmt = $pdo->prepare("INSERT INTO products (vendor_id, name, description, category, price, stock) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vendor_ids['vendor1'], $product[0], $product[1], $product[2], $product[3], $product[4]]);
        }
        
        // Create sample products for vendor2 (Burger Kingdom)
        $burger_products = [
            ['Classic Burger', 'Beef patty with lettuce, tomato, and special sauce', 'Burgers', 9.99, 50],
            ['Cheese Burger', 'Classic burger with melted cheddar cheese', 'Burgers', 10.99, 50],
            ['Chicken Burger', 'Crispy chicken breast with mayo and lettuce', 'Burgers', 11.99, 50],
            ['Veggie Burger', 'Plant-based patty with fresh vegetables', 'Burgers', 10.99, 30],
            ['French Fries', 'Crispy golden fries', 'Sides', 3.99, 100],
            ['Onion Rings', 'Crispy battered onion rings', 'Sides', 4.99, 50],
            ['Milkshake', 'Creamy vanilla milkshake', 'Beverages', 5.99, 50],
            ['Apple Pie', 'Warm apple pie slice', 'Desserts', 4.99, 30]
        ];
        
        foreach ($burger_products as $product) {
            $stmt = $pdo->prepare("INSERT INTO products (vendor_id, name, description, category, price, stock) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vendor_ids['vendor2'], $product[0], $product[1], $product[2], $product[3], $product[4]]);
        }
        
        // Create sample coupons
        $coupons = [
            ['WELCOME10', 'Get 10% off on your first order', 'percentage', 10, 20, 10, '2025-01-01 00:00:00', '2025-12-31 23:59:59'],
            ['SAVE5', 'Save $5 on orders above $30', 'fixed', 5, 30, null, '2025-01-01 00:00:00', '2025-12-31 23:59:59'],
            ['FREESHIP', 'Free delivery on orders above $25', 'fixed', 5, 25, null, '2025-01-01 00:00:00', '2025-12-31 23:59:59']
        ];
        
        foreach ($coupons as $coupon) {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($coupon);
        }
        
        // Commit transaction
        $pdo->commit();
        
        $message = "Setup completed successfully! Demo data has been added to the database.";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeliverEase - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding: 40px 0;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .demo-credentials {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <h1 class="mb-4">DeliverEase Setup</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <h3>Setup Instructions</h3>
            <ol>
                <li>Make sure you have created a MySQL database named <code>deliverease</code></li>
                <li>Import the <code>database.sql</code> file into your database</li>
                <li>Update database credentials in <code>config.php</code> if needed</li>
                <li>Click the button below to add demo data</li>
            </ol>
            
            <form method="POST" class="mt-4">
                <button type="submit" name="setup" class="btn btn-primary btn-lg">
                    Initialize Demo Data
                </button>
            </form>
            
            <div class="demo-credentials">
                <h4>Demo Credentials (after setup)</h4>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Admin</h6>
                        <p class="mb-1">Username: <code>admin</code></p>
                        <p>Password: <code>admin123</code></p>
                        
                        <h6 class="mt-3">Vendors</h6>
                        <p class="mb-1">Username: <code>vendor1</code> / <code>vendor2</code></p>
                        <p>Password: <code>vendor123</code></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Delivery Personnel</h6>
                        <p class="mb-1">Username: <code>delivery1</code> / <code>delivery2</code></p>
                        <p>Password: <code>delivery123</code></p>
                        
                        <h6 class="mt-3">Customers</h6>
                        <p class="mb-1">Username: <code>customer1</code> / <code>customer2</code></p>
                        <p>Password: <code>customer123</code></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary">Go to Homepage</a>
                <a href="login.php" class="btn btn-success">Go to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
