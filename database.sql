-- Ek-Click Database Schema
-- Multi-vendor local delivery platform
-- Compatible with MySQL/MariaDB for shared hosting

CREATE DATABASE IF NOT EXISTS eclick;
USE eclick;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date_of_birth DATE,
    role ENUM('admin', 'vendor', 'delivery', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    address TEXT,
    city VARCHAR(50),
    map_link TEXT,
    referral_code VARCHAR(20) UNIQUE,
    referred_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
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

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    preparation_time INT DEFAULT 15,
    ingredients TEXT,
    allergens TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    vendor_id INT NOT NULL,
    delivery_person_id INT,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'picked_up', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    payment_method ENUM('cash', 'card', 'wallet') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    delivery_map_link TEXT,
    pickup_address TEXT NOT NULL,
    pickup_map_link TEXT,
    order_type ENUM('catalog', 'custom') DEFAULT 'catalog',
    order_description TEXT,
    order_image VARCHAR(255),
    special_instructions TEXT,
    vendor_notes TEXT,
    delivery_notes TEXT,
    estimated_delivery_time DATETIME,
    actual_delivery_time DATETIME,
    admin_approved BOOLEAN DEFAULT FALSE,
    vendor_approved BOOLEAN DEFAULT FALSE,
    delivery_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (delivery_person_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    vendor_id INT,
    delivery_person_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    review_type ENUM('vendor', 'delivery') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (delivery_person_id) REFERENCES users(id)
);

-- Chats table
CREATE TABLE IF NOT EXISTS chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);

-- Coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    discount_type ENUM('fixed', 'percentage') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2),
    usage_limit INT,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Coupon usage table
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Disputes table
CREATE TABLE IF NOT EXISTS disputes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    user_id INT NOT NULL,
    type ENUM('order_issue', 'delivery_issue', 'payment_issue', 'quality_issue', 'other') DEFAULT 'other',
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    admin_notes TEXT,
    resolved_at DATETIME NULL,
    resolved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Favorites table
CREATE TABLE IF NOT EXISTS favorites (
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
);

-- User addresses table
CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_type VARCHAR(50) DEFAULT 'home',
    street_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(50) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    map_link TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Dispute responses table
CREATE TABLE IF NOT EXISTS dispute_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dispute_id INT NOT NULL,
    user_id INT NOT NULL,
    response TEXT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dispute_id) REFERENCES disputes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert demo users
INSERT INTO users (username, password, email, full_name, phone, role, address, city) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'admin@eclick.com', 'System Administrator', '+1234567890', 'admin', '123 Admin Street', 'City'),
('vendor1', '$2y$10$YourHashedPasswordHere', 'vendor1@eclick.com', 'Local Grocery Store Owner', '+1234567891', 'vendor', '456 Vendor Avenue', 'City'),
('vendor2', '$2y$10$YourHashedPasswordHere', 'vendor2@eclick.com', 'Book Store Owner', '+1234567894', 'vendor', '789 Book Street', 'City'),
('vendor3', '$2y$10$YourHashedPasswordHere', 'vendor3@eclick.com', 'Restaurant Owner', '+1234567895', 'vendor', '101 Food Avenue', 'City'),
('delivery1', '$2y$10$YourHashedPasswordHere', 'delivery1@eclick.com', 'John Delivery Driver', '+1234567892', 'delivery', '789 Delivery Lane', 'City'),
('customer1', '$2y$10$YourHashedPasswordHere', 'customer1@eclick.com', 'Jane Customer', '+1234567893', 'user', '321 Customer Road', 'City');

-- Insert demo vendors
INSERT INTO vendors (user_id, shop_name, owner_name, email, phone, category, cuisine_type, business_type, address, city, description, accepts_custom_orders) VALUES
(2, 'Fresh Mart Grocery', 'Local Grocery Store Owner', 'vendor1@eclick.com', '+1234567891', 'Grocery', 'Grocery', 'Grocery Store', '456 Vendor Avenue', 'City', 'Fresh groceries and daily essentials', TRUE),
(3, 'BookWorm Corner', 'Book Store Owner', 'vendor2@eclick.com', '+1234567894', 'Books', 'Books & Stationery', 'Book Store', '789 Book Street', 'City', 'Books, stationery and educational materials', TRUE),
(4, 'Tasty Bites Restaurant', 'Restaurant Owner', 'vendor3@eclick.com', '+1234567895', 'Food', 'Fast Food', 'Restaurant', '101 Food Avenue', 'City', 'Delicious meals and beverages', TRUE);

-- Note: Default passwords are:
-- admin: admin123
-- vendor1: vendor123
-- vendor2: vendor123  
-- vendor3: vendor123
-- delivery1: delivery123
-- customer1: customer123
-- You need to generate proper password hashes using PHP's password_hash() function
 