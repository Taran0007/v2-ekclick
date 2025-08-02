<?php
// E-CLICK Database Setup Script
// Run this file once to create the new database and migrate data

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('OLD_DB_NAME', 'deliverease');
define('NEW_DB_NAME', 'eclick');

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>E-CLICK Database Setup</h2>";
    
    // Create new database
    echo "<p>Creating database 'eclick'...</p>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . NEW_DB_NAME);
    echo "<p style='color: green;'>✓ Database 'eclick' created successfully!</p>";
    
    // Switch to new database
    $pdo->exec("USE " . NEW_DB_NAME);
    
    // Read and execute the database schema
    echo "<p>Setting up database schema...</p>";
    $sql = file_get_contents('database.sql');
    
    // Remove the CREATE DATABASE and USE statements from the file
    $sql = preg_replace('/CREATE DATABASE.*?;/', '', $sql);
    $sql = preg_replace('/USE.*?;/', '', $sql);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Skip errors for statements that might already exist
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p style='color: orange;'>Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✓ Database schema created successfully!</p>";
    
    // Try to migrate data from old database if it exists
    try {
        $pdo->exec("USE " . OLD_DB_NAME);
        echo "<p>Found existing 'deliverease' database. Migrating data...</p>";
        
        // Migrate users
        $result = $pdo->query("SELECT COUNT(*) FROM users");
        if ($result && $result->fetchColumn() > 0) {
            $pdo->exec("INSERT IGNORE INTO " . NEW_DB_NAME . ".users SELECT * FROM " . OLD_DB_NAME . ".users");
            echo "<p style='color: green;'>✓ Users migrated</p>";
        }
        
        // Migrate vendors
        $result = $pdo->query("SELECT COUNT(*) FROM vendors");
        if ($result && $result->fetchColumn() > 0) {
            $pdo->exec("INSERT IGNORE INTO " . NEW_DB_NAME . ".vendors SELECT * FROM " . OLD_DB_NAME . ".vendors");
            echo "<p style='color: green;'>✓ Vendors migrated</p>";
        }
        
        // Migrate products
        $result = $pdo->query("SELECT COUNT(*) FROM products");
        if ($result && $result->fetchColumn() > 0) {
            $pdo->exec("INSERT IGNORE INTO " . NEW_DB_NAME . ".products SELECT * FROM " . OLD_DB_NAME . ".products");
            echo "<p style='color: green;'>✓ Products migrated</p>";
        }
        
        // Migrate other tables as needed
        $tables = ['orders', 'order_items', 'reviews', 'user_addresses', 'favorites'];
        foreach ($tables as $table) {
            try {
                $result = $pdo->query("SELECT COUNT(*) FROM $table");
                if ($result && $result->fetchColumn() > 0) {
                    $pdo->exec("INSERT IGNORE INTO " . NEW_DB_NAME . ".$table SELECT * FROM " . OLD_DB_NAME . ".$table");
                    echo "<p style='color: green;'>✓ $table migrated</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>Note: Could not migrate $table - " . $e->getMessage() . "</p>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: blue;'>No existing 'deliverease' database found. Starting fresh.</p>";
    }
    
    // Switch back to new database and create demo data
    $pdo->exec("USE " . NEW_DB_NAME);
    
    // Create demo users with proper password hashes
    echo "<p>Creating demo users...</p>";
    $demo_password = password_hash('123456', PASSWORD_DEFAULT);
    
    $demo_users = [
        ['admin', $demo_password, 'admin@eclick.com', 'System Administrator', '+1234567890', 'admin', '123 Admin Street', 'City'],
        ['vendor1', $demo_password, 'vendor1@eclick.com', 'Local Grocery Store Owner', '+1234567891', 'vendor', '456 Vendor Avenue', 'City'],
        ['vendor2', $demo_password, 'vendor2@eclick.com', 'Book Store Owner', '+1234567894', 'vendor', '789 Book Street', 'City'],
        ['vendor3', $demo_password, 'vendor3@eclick.com', 'Restaurant Owner', '+1234567895', 'vendor', '101 Food Avenue', 'City'],
        ['delivery1', $demo_password, 'delivery1@eclick.com', 'John Delivery Driver', '+1234567892', 'delivery', '789 Delivery Lane', 'City'],
        ['customer1', $demo_password, 'customer1@eclick.com', 'Jane Customer', '+1234567893', 'user', '321 Customer Road', 'City']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, email, full_name, phone, role, address, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($demo_users as $user) {
        $stmt->execute($user);
    }
    echo "<p style='color: green;'>✓ Demo users created (password: 123456)</p>";
    
    // Create demo vendors
    echo "<p>Creating demo vendors...</p>";
    $demo_vendors = [
        [2, 'Fresh Mart Grocery', 'Local Grocery Store Owner', 'vendor1@eclick.com', '+1234567891', 'Grocery', 'Grocery Store', '456 Vendor Avenue', 'City', 'Fresh groceries and daily essentials', 1, 'active'],
        [3, 'BookWorm Corner', 'Book Store Owner', 'vendor2@eclick.com', '+1234567894', 'Books', 'Book Store', '789 Book Street', 'City', 'Books, stationery and educational materials', 1, 'active'],
        [4, 'Tasty Bites Restaurant', 'Restaurant Owner', 'vendor3@eclick.com', '+1234567895', 'Food', 'Restaurant', '101 Food Avenue', 'City', 'Delicious meals and beverages', 1, 'active']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO vendors (user_id, shop_name, owner_name, email, phone, category, business_type, address, city, description, accepts_custom_orders, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($demo_vendors as $vendor) {
        $stmt->execute($vendor);
    }
    echo "<p style='color: green;'>✓ Demo vendors created</p>";
    
    // Create some demo products
    echo "<p>Creating demo products...</p>";
    $demo_products = [
        [1, 'Fresh Apples', 'Organic red apples', 'Fruits', 2.99, 100, 1, 1],
        [1, 'Whole Milk', 'Fresh whole milk 1 gallon', 'Dairy', 3.49, 50, 1, 1],
        [1, 'Bread Loaf', 'Whole wheat bread', 'Bakery', 2.49, 30, 1, 1],
        [2, 'Programming Book', 'Learn PHP Programming', 'Programming', 29.99, 20, 1, 1],
        [2, 'Notebook Set', 'Pack of 5 notebooks', 'Stationery', 12.99, 40, 1, 1],
        [3, 'Burger Combo', 'Beef burger with fries and drink', 'Fast Food', 8.99, 0, 1, 1],
        [3, 'Pizza Margherita', 'Classic margherita pizza', 'Pizza', 12.99, 0, 1, 1]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO products (vendor_id, name, description, category, price, stock, is_available, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($demo_products as $product) {
        $stmt->execute($product);
    }
    echo "<p style='color: green;'>✓ Demo products created</p>";
    
    echo "<h3 style='color: green;'>✅ Setup Complete!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin / 123456</li>";
    echo "<li><strong>Vendor:</strong> vendor1 / 123456 (or vendor2, vendor3)</li>";
    echo "<li><strong>Delivery:</strong> delivery1 / 123456</li>";
    echo "<li><strong>Customer:</strong> customer1 / 123456</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Go to E-CLICK Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
