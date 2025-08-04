-- Modified database.sql for remote database compatibility
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date_of_birth DATE,
    role ENUM('admin', 'vendor', 'delivery', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    address TEXT,
    city VARCHAR(50),
    map_link TEXT,
    referral_code VARCHAR(20),
    referred_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE INDEX username_idx (username),
    UNIQUE INDEX email_idx (email),
    UNIQUE INDEX referral_idx (referral_code)
);

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shop_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    shop_description TEXT,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    cuisine_type VARCHAR(50),
    business_type VARCHAR(50),
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50),
    zip_code VARCHAR(20),
    map_link TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    logo VARCHAR(255),
    is_open BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'active', 'suspended', 'rejected') DEFAULT 'pending',
    rating DECIMAL(3,2) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    delivery_radius DECIMAL(5,2) DEFAULT 5.0,
    estimated_delivery_time INT DEFAULT 30,
    free_delivery_threshold DECIMAL(10,2) DEFAULT 25.00,
    tax_rate DECIMAL(5,2) DEFAULT 8.25,
    payment_methods TEXT,
    operating_hours TEXT,
    accepts_custom_orders BOOLEAN DEFAULT TRUE,
    fee_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create remaining tables...
[Previous tables creation SQL statements]

-- Insert admin user with hashed password 'admin123'
INSERT INTO users (username, password, email, full_name, phone, role, address, city) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'admin@eclick.com', 'System Administrator', '+1234567890', 'admin', '123 Admin Street', 'City');

-- Create necessary indexes
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_vendor_status ON vendors(status);
CREATE INDEX idx_order_status ON orders(status);
CREATE INDEX idx_order_number ON orders(order_number);
