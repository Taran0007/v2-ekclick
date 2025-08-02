<?php
require_once 'config.php';

echo "Updating database schema...\n";

try {
    // Add missing columns to vendors table
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS owner_name VARCHAR(100)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS email VARCHAR(100)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS phone VARCHAR(20)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS shop_description TEXT");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS description TEXT");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS cuisine_type VARCHAR(50)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS state VARCHAR(50)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS zip_code VARCHAR(20)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS logo VARCHAR(255)");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS status ENUM('pending', 'active', 'suspended', 'rejected') DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) DEFAULT 0");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS min_order_amount DECIMAL(10,2) DEFAULT 0");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS delivery_radius DECIMAL(5,2) DEFAULT 5.0");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS estimated_delivery_time INT DEFAULT 30");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS free_delivery_threshold DECIMAL(10,2) DEFAULT 25.00");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS tax_rate DECIMAL(5,2) DEFAULT 8.25");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS payment_methods TEXT");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS operating_hours TEXT");
    $pdo->exec("ALTER TABLE vendors ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    
    echo "Updated vendors table\n";
    
    // Add missing columns to products table
    $pdo->exec("ALTER TABLE products MODIFY COLUMN category VARCHAR(50)");
    $pdo->exec("ALTER TABLE products CHANGE COLUMN image_url image VARCHAR(255)");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS is_available BOOLEAN DEFAULT TRUE");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS preparation_time INT DEFAULT 15");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS ingredients TEXT");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS allergens TEXT");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    
    echo "Updated products table\n";
    
    // Add missing columns to orders table
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS vendor_notes TEXT");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_notes TEXT");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    
    echo "Updated orders table\n";
    
    // Create cart table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product (user_id, product_id)
    )");
    
    echo "Created cart table\n";
    
    // Create favorites table
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        vendor_id INT NULL,
        product_id INT NULL,
        type ENUM('vendor', 'product') DEFAULT 'vendor',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_vendor (user_id, vendor_id),
        UNIQUE KEY unique_user_product (user_id, product_id)
    )");
    
    echo "Created favorites table\n";
    
    // Create user_addresses table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        address_type VARCHAR(50) DEFAULT 'home',
        street_address TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        state VARCHAR(50) NOT NULL,
        zip_code VARCHAR(20) NOT NULL,
        is_default BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    echo "Created user_addresses table\n";
    
    // Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "Created settings table\n";
    
    // Update disputes table
    $pdo->exec("ALTER TABLE disputes MODIFY COLUMN order_id INT NULL");
    $pdo->exec("ALTER TABLE disputes ADD COLUMN IF NOT EXISTS type ENUM('order_issue', 'delivery_issue', 'payment_issue', 'quality_issue', 'other') DEFAULT 'other'");
    $pdo->exec("ALTER TABLE disputes MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium'");
    $pdo->exec("ALTER TABLE disputes MODIFY COLUMN status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open'");
    $pdo->exec("ALTER TABLE disputes MODIFY COLUMN resolved_at DATETIME NULL");
    $pdo->exec("ALTER TABLE disputes ADD COLUMN IF NOT EXISTS resolved_by INT NULL");
    $pdo->exec("ALTER TABLE disputes ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    
    echo "Updated disputes table\n";
    
    // Create dispute_responses table
    $pdo->exec("CREATE TABLE IF NOT EXISTS dispute_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dispute_id INT NOT NULL,
        user_id INT NOT NULL,
        response TEXT NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (dispute_id) REFERENCES disputes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    echo "Created dispute_responses table\n";
    
    // Add some sample data to vendors table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vendors");
    $stmt->execute();
    $vendor_count = $stmt->fetchColumn();
    
    if ($vendor_count == 0) {
        // Insert sample vendors
        $pdo->exec("INSERT INTO vendors (user_id, shop_name, owner_name, email, phone, description, category, cuisine_type, address, city, state, zip_code, status, delivery_fee, min_order_amount, estimated_delivery_time, tax_rate) VALUES 
        (2, 'Pizza Palace', 'John Doe', 'pizza@example.com', '555-0101', 'Best pizza in town with fresh ingredients', 'Restaurant', 'Italian', '123 Main St', 'Anytown', 'CA', '12345', 'active', 2.99, 15.00, 25, 8.25),
        (2, 'Burger Barn', 'Jane Smith', 'burgers@example.com', '555-0102', 'Gourmet burgers and fries', 'Restaurant', 'American', '456 Oak Ave', 'Anytown', 'CA', '12345', 'active', 3.99, 12.00, 30, 8.25)");
        
        echo "Added sample vendors\n";
    }
    
    echo "Database schema updated successfully!\n";
    
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
