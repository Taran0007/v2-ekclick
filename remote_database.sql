-- Ek-Click Database Schema
-- Multi-vendor local delivery platform
-- Compatible with MySQL/MariaDB for shared hosting

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

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

-- Insert initial admin user
INSERT INTO users (username, password, email, full_name, phone, role, address, city) VALUES
('admin', '$2y$10$8H4.zhT0R7gZeY1.7cKYZO78K4fx.kJrYCjhHY46GmtHvEJdJM0CO', 'admin@eclick.com', 'System Administrator', '+1234567890', 'admin', '123 Admin Street', 'City');

-- Create indexes for better performance
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_vendor_status ON vendors(status);
CREATE INDEX idx_order_status ON orders(status);
CREATE INDEX idx_order_user ON orders(user_id);
CREATE INDEX idx_order_vendor ON orders(vendor_id);
CREATE INDEX idx_review_vendor ON reviews(vendor_id);
