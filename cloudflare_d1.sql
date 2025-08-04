-- Ek-Click Database Schema for Cloudflare D1 (SQLite)
PRAGMA foreign_keys = ON;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT NOT NULL,
    phone TEXT NOT NULL,
    date_of_birth TEXT,
    role TEXT CHECK (role IN ('admin', 'vendor', 'delivery', 'user')) DEFAULT 'user',
    is_active INTEGER DEFAULT 1,
    address TEXT,
    city TEXT,
    map_link TEXT,
    referral_code TEXT UNIQUE,
    referred_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    shop_name TEXT NOT NULL,
    owner_name TEXT,
    email TEXT,
    phone TEXT,
    shop_description TEXT,
    description TEXT,
    category TEXT NOT NULL,
    cuisine_type TEXT,
    business_type TEXT,
    address TEXT NOT NULL,
    city TEXT NOT NULL,
    state TEXT,
    zip_code TEXT,
    map_link TEXT,
    latitude REAL,
    longitude REAL,
    logo TEXT,
    is_open INTEGER DEFAULT 1,
    status TEXT CHECK (status IN ('pending', 'active', 'suspended', 'rejected')) DEFAULT 'pending',
    rating REAL DEFAULT 0,
    total_reviews INTEGER DEFAULT 0,
    delivery_fee REAL DEFAULT 0,
    min_order_amount REAL DEFAULT 0,
    delivery_radius REAL DEFAULT 5.0,
    estimated_delivery_time INTEGER DEFAULT 30,
    free_delivery_threshold REAL DEFAULT 25.00,
    tax_rate REAL DEFAULT 8.25,
    payment_methods TEXT,
    operating_hours TEXT,
    accepts_custom_orders INTEGER DEFAULT 1,
    fee_status TEXT CHECK (fee_status IN ('pending', 'paid', 'failed')) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create remaining tables with SQLite syntax...

-- Create indexes
CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_vendor_status ON vendors(status);
CREATE INDEX idx_vendor_user ON vendors(user_id);

-- Insert admin user (password: admin123)
INSERT INTO users (
    username, 
    password, 
    email, 
    full_name, 
    phone, 
    role, 
    address, 
    city
) VALUES (
    'admin',
    '$2y$10$8H4.zhT0R7gZeY1.7cKYZO78K4fx.kJrYCjhHY46GmtHvEJdJM0CO',
    'admin@eclick.com',
    'System Administrator',
    '+1234567890',
    'admin',
    '123 Admin Street',
    'City'
);
