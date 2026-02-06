-- Create Tables for MakaziPrime
-- Database: Makazi

CREATE DATABASE IF NOT EXISTS Makazi;
USE Makazi;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'admin') DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. PROPERTY TYPES
CREATE TABLE IF NOT EXISTS property_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. PROPERTIES TABLE
CREATE TABLE IF NOT EXISTS properties (
    property_id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    listing_type ENUM('sale', 'rent') DEFAULT 'sale',
    title VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(100) NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    rent_period ENUM('monthly', 'yearly', 'daily') DEFAULT NULL,
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    area_sqft INT,
    status ENUM('available', 'sold', 'reserved', 'rented') DEFAULT 'available',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES property_types(type_id) ON DELETE CASCADE
);

-- 4. BOOKINGS TABLE
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_fee DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. BOOKING DETAILS
CREATE TABLE IF NOT EXISTS booking_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    property_id INT NOT NULL,
    agreed_price DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id)
);

-- 6. ADMIN LOGS
CREATE TABLE IF NOT EXISTS admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
);

-- Insert Default Admin User (Password: password123)
-- Hash generated using password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (full_name, email, password_hash, role) 
SELECT 'System Admin', 'admin@makaziprime.com', '$2y$10$d2Ej4kRohtqlJNkgviR2Iu1ue6am/H3n8Xnnwo7fy.AzrngbDUgltu', 'admin'
WHERE NOT EXISTS (SELECT email FROM users WHERE email = 'admin@makaziprime.com');

-- Insert Basic Property Types
INSERT INTO property_types (name) VALUES 
('Apartment'), 
('Villa'), 
('Office'), 
('Land') 
ON DUPLICATE KEY UPDATE name=name;

-- 7. VIEWING SLOTS TABLE (Schedule Viewing)
CREATE TABLE IF NOT EXISTS viewing_slots (
    slot_id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    viewing_date DATE NOT NULL,
    viewing_time TIME NOT NULL,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 8. FAVORITES TABLE (Wishlist)
CREATE TABLE IF NOT EXISTS favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, property_id)
);

-- 9. REVIEWS TABLE (Property Reviews)
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE
);
