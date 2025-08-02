<?php
// Demo Users Creation Script
require_once 'config.php';

try {
    echo "<h2>Creating Demo Users</h2>";
    
    // Clear existing demo users first
    echo "<p>Clearing existing demo users...</p>";
    $pdo->exec("DELETE FROM users WHERE username IN ('admin', 'vendor1', 'vendor2', 'vendor3', 'delivery1', 'customer1')");
    $pdo->exec("DELETE FROM vendors WHERE user_id IN (SELECT id FROM users WHERE username IN ('vendor1', 'vendor2', 'vendor3'))");
    
    // Create password hash for '123456'
    $password_hash = password_hash('123456', PASSWORD_DEFAULT);
    echo "<p>Password hash created for '123456'</p>";
    
    // Create demo users
    $demo_users = [
        ['admin', $password_hash, 'admin@deliverease.com', 'System Administrator', '+1234567890', 'admin', '123 Admin Street', 'City'],
        ['vendor1', $password_hash, 'vendor1@deliverease.com', 'Local Grocery Store Owner', '+1234567891', 'vendor', '456 Vendor Avenue', 'City'],
        ['vendor2', $password_hash, 'vendor2@deliverease.com', 'Book Store Owner', '+1234567894', 'vendor', '789 Book Street', 'City'],
        ['vendor3', $password_hash, 'vendor3@deliverease.com', 'Restaurant Owner', '+1234567895', 'vendor', '101 Food Avenue', 'City'],
        ['delivery1', $password_hash, 'delivery1@deliverease.com', 'John Delivery Driver', '+1234567892', 'delivery', '789 Delivery Lane', 'City'],
        ['customer1', $password_hash, 'customer1@deliverease.com', 'Jane Customer', '+1234567893', 'user', '321 Customer Road', 'City']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, role, address, city, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    foreach ($demo_users as $user) {
        $stmt->execute($user);
        echo "<p style='color: green;'>✓ Created user: {$user[0]} / 123456 ({$user[5]})</p>";
    }
    
    // Get user IDs for vendors
    $vendor_users = $pdo->query("SELECT id, username FROM users WHERE role = 'vendor'")->fetchAll();
    
    // Create demo vendors
    echo "<p>Creating demo vendors...</p>";
    $demo_vendors = [
        ['Fresh Mart Grocery', 'Local Grocery Store Owner', 'vendor1@deliverease.com', '+1234567891', 'Grocery', 'Grocery', 'Grocery Store', '456 Vendor Avenue', 'City', 'Fresh groceries and daily essentials', 1, 'active'],
        ['BookWorm Corner', 'Book Store Owner', 'vendor2@deliverease.com', '+1234567894', 'Books', 'Books & Stationery', 'Book Store', '789 Book Street', 'City', 'Books, stationery and educational materials', 1, 'active'],
        ['Tasty Bites Restaurant', 'Restaurant Owner', 'vendor3@deliverease.com', '+1234567895', 'Food', 'Fast Food', 'Restaurant', '101 Food Avenue', 'City', 'Delicious meals and beverages', 1, 'active']
    ];
    
    $vendor_stmt = $pdo->prepare("INSERT INTO vendors (user_id, shop_name, owner_name, email, phone, category, cuisine_type, business_type, address, city, description, accepts_custom_orders, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $i = 0;
    foreach ($vendor_users as $vendor_user) {
        if ($i < count($demo_vendors)) {
            $vendor_data = array_merge([$vendor_user['id']], $demo_vendors[$i]);
            $vendor_stmt->execute($vendor_data);
            echo "<p style='color: green;'>✓ Created vendor: {$demo_vendors[$i][0]}</p>";
            $i++;
        }
    }
    
    // Create some demo products
    echo "<p>Creating demo products...</p>";
    $vendor_ids = $pdo->query("SELECT id FROM vendors ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($vendor_ids) >= 3) {
        $demo_products = [
            [$vendor_ids[0], 'Fresh Apples', 'Organic red apples', 'Fruits', 2.99, 100, 1, 1],
            [$vendor_ids[0], 'Whole Milk', 'Fresh whole milk 1 gallon', 'Dairy', 3.49, 50, 1, 1],
            [$vendor_ids[1], 'Programming Book', 'Learn PHP Programming', 'Programming', 29.99, 20, 1, 1],
            [$vendor_ids[1], 'Notebook Set', 'Pack of 5 notebooks', 'Stationery', 12.99, 40, 1, 1],
            [$vendor_ids[2], 'Burger Combo', 'Beef burger with fries and drink', 'Fast Food', 8.99, 0, 1, 1],
            [$vendor_ids[2], 'Pizza Margherita', 'Classic margherita pizza', 'Pizza', 12.99, 0, 1, 1]
        ];
        
        $product_stmt = $pdo->prepare("INSERT INTO products (vendor_id, name, description, category, price, stock, is_available, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($demo_products as $product) {
            $product_stmt->execute($product);
        }
        echo "<p style='color: green;'>✓ Demo products created</p>";
    }
    
    echo "<h3 style='color: green;'>✅ Demo Users Created Successfully!</h3>";
    echo "<p><strong>Login Credentials (All passwords: 123456):</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin / 123456</li>";
    echo "<li><strong>Vendor:</strong> vendor1 / 123456 (also vendor2, vendor3)</li>";
    echo "<li><strong>Delivery:</strong> delivery1 / 123456</li>";
    echo "<li><strong>Customer:</strong> customer1 / 123456</li>";
    echo "</ul>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
