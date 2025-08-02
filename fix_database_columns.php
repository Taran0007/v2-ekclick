<?php
// Database Column Fix Script
// Run this once to add missing columns to existing database

require_once 'config.php';

try {
    echo "<h2>Database Column Fix Script</h2>";
    
    // Add cuisine_type column to vendors table
    echo "<p>Adding cuisine_type column to vendors table...</p>";
    try {
        $pdo->exec("ALTER TABLE vendors ADD COLUMN cuisine_type VARCHAR(50) DEFAULT NULL AFTER category");
        echo "<p style='color: green;'>✓ cuisine_type column added to vendors table</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ cuisine_type column already exists in vendors table</p>";
        } else {
            echo "<p style='color: red;'>Error adding cuisine_type column: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add date_of_birth column to users table
    echo "<p>Adding date_of_birth column to users table...</p>";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN date_of_birth DATE DEFAULT NULL AFTER phone");
        echo "<p style='color: green;'>✓ date_of_birth column added to users table</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ date_of_birth column already exists in users table</p>";
        } else {
            echo "<p style='color: red;'>Error adding date_of_birth column: " . $e->getMessage() . "</p>";
        }
    }
    
    // Update existing vendors with sample cuisine types
    echo "<p>Updating existing vendors with sample cuisine types...</p>";
    try {
        $updates = [
            "UPDATE vendors SET cuisine_type = 'Grocery' WHERE category = 'Grocery' AND cuisine_type IS NULL",
            "UPDATE vendors SET cuisine_type = 'Books & Stationery' WHERE category = 'Books' AND cuisine_type IS NULL",
            "UPDATE vendors SET cuisine_type = 'Fast Food' WHERE category = 'Food' AND cuisine_type IS NULL",
            "UPDATE vendors SET cuisine_type = 'Restaurant' WHERE business_type = 'Restaurant' AND cuisine_type IS NULL",
            "UPDATE vendors SET cuisine_type = 'Italian' WHERE shop_name LIKE '%pizza%' AND cuisine_type IS NULL",
            "UPDATE vendors SET cuisine_type = 'American' WHERE shop_name LIKE '%burger%' AND cuisine_type IS NULL"
        ];
        
        foreach ($updates as $update) {
            $pdo->exec($update);
        }
        echo "<p style='color: green;'>✓ Sample cuisine types added to existing vendors</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>Warning updating cuisine types: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3 style='color: green;'>✅ Database column fixes completed!</h3>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>The missing columns have been added to your database</li>";
    echo "<li>You can now browse restaurants and favorites without errors</li>";
    echo "<li>Profile page will display properly</li>";
    echo "</ul>";
    echo "<p><a href='customer/browse.php'>Test Browse Page</a> | <a href='customer/favorites.php'>Test Favorites Page</a> | <a href='customer/profile.php'>Test Profile Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}
?>
