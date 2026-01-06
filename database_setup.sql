-- Create database
CREATE DATABASE IF NOT EXISTS decathlon_db;
USE decathlon_db;

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Create product_images table
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Sports Clothing', 'Athletic wear and sportswear for all activities'),
('Footwear', 'Sports shoes and athletic footwear'),
('Equipment', 'Sports equipment and gear'),
('Accessories', 'Sports accessories and essentials'),
('Fitness', 'Fitness equipment and workout gear'),
('Outdoor', 'Outdoor sports and adventure gear');

-- Insert sample products
INSERT INTO products (name, description, price, stock_quantity, category_id) VALUES
('Running Shoes', 'Professional running shoes with advanced cushioning', 89.99, 50, 2),
('Sports T-Shirt', 'Moisture-wicking athletic t-shirt', 29.99, 100, 1),
('Yoga Mat', 'Non-slip exercise yoga mat', 34.99, 30, 5),
('Tennis Racket', 'Professional grade tennis racket', 129.99, 20, 3),
('Water Bottle', 'Insulated sports water bottle', 19.99, 80, 4),
('Gym Bag', 'Spacious sports duffel bag', 49.99, 40, 4),
('Basketball', 'Official size basketball', 24.99, 60, 3),
('Football', 'Professional football', 39.99, 45, 3),
('Swimming Goggles', 'Anti-fog swimming goggles', 15.99, 70, 4),
('Cycling Helmet', 'Safety cycling helmet', 59.99, 35, 4);

-- Insert product images
INSERT INTO product_images (product_id, image_url, is_primary) VALUES
(1, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300', TRUE),
(2, 'https://images.unsplash.com/photo-1521572163464-f52485b1e407?w=300', TRUE),
(3, 'https://images.unsplash.com/photo-1544161515-4df6c4d81d0e?w=300', TRUE),
(4, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=300', TRUE),
(5, 'https://images.unsplash.com/photo-1602143407151-7111542de19e?w=300', TRUE),
(6, 'https://images.unsplash.com/photo-1556820545-3f8c0809e552?w=300', TRUE),
(7, 'https://images.unsplash.com/photo-1552667466-07770ae110d0?w=300', TRUE),
(8, 'https://images.unsplash.com/photo-1553531384-cc11fc53696a?w=300', TRUE),
(9, 'https://images.unsplash.com/photo-1575299469153-93b32fd3321d?w=300', TRUE),
(10, 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=300', TRUE);

-- Insert admin user
INSERT INTO users (first_name, last_name, email, password, role) VALUES
('Admin', 'User', 'admin@decathlon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample customers
INSERT INTO users (first_name, last_name, email, password, phone, address) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', '123 Main St, City, State'),
('Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321', '456 Oak Ave, City, State');

-- Insert sample orders
INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) VALUES
(2, 119.98, 'delivered', '123 Main St, City, State', 'credit_card'),
(3, 54.98, 'processing', '456 Oak Ave, City, State', 'paypal');

-- Insert sample order items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 89.99),
(1, 2, 1, 29.99),
(2, 3, 1, 34.99),
(2, 5, 1, 19.99);
