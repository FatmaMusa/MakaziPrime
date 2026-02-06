# MakaziPrime - Real Estate Management System

**Project Name:** MakaziPrime Real Estate System  
**Student Requirement:** Native PHP, Vanilla JavaScript, MySQL (No Frameworks)  
**Database Norm:** 3NF (Third Normal Form)

---

## 1. Project Overview

**MakaziPrime** is a complete Real Estate Management System built from scratch for academic purposes. It connects buyers/renters with properties through a clean, modern interface without relying on heavy frameworks.

* **Frontend:** HTML5, CSS3, Vanilla JavaScript (Fetch API for AJAX).
* **Backend:** Plain PHP (no frameworks, procedural or simple OOP).
* **Database:** MySQL (Relational, 3NF).
* **Core Features:** User registration/login, Admin dashboard, Property CRUD (Create, Read, Update, Delete), Image uploads, Search/Filter logic, Booking system.

### Brand Identity
* **Brand Name:** MakaziPrime (Swahili: *Makazi* = Habitat/Settlement + English: *Prime*)
* **Tagline:** "Find Your Space. Build Your Future."

### Color Palette

| Color Role | Hex Code | Visual Name | Usage |
| :--- | :--- | :--- | :--- |
| **Primary Brand** | `#2C3E50` | Estate Navy | Navbar, Headings, Footer |
| **Action** | `#27AE60` | Growth Green | "Book Now" buttons, Success messages |
| **Accent** | `#D4AC0D` | Premium Gold | Featured tags, Borders, Icons |
| **Light Shade** | `#ECF0F1` | Cloud Mist | Form backgrounds, Table rows |
| **Text** | `#333333` | Slate Grey | Main content text |
| **Background** | `#FFFFFF` | Pure White | Page body, Cards |

---

## 2. System Architecture & Folder Structure

This structure strictly separates the Backend API (Logic) from the Frontend Pages (View).

```text
/makaziprime_project
│
├── /config
│   └── db.php                # Database Connection Class (PDO)
│
├── /api                      # BACKEND (PHP Scripts - Return JSON)
│   ├── /auth
│   │   ├── register.php
│   │   └── login.php
│   ├── /properties
│   │   ├── create.php        # Admin: Add Property + Image Upload
│   │   ├── read.php          # Public: Get all properties (JSON)
│   │   └── delete.php        # Admin: Remove property
│   └── /bookings
│       └── create.php        # User: Book a viewing/property
│
├── /assets                   # STATIC FILES
│   ├── /css
│   │   ├── style.css         # Global styles
│   │   └── admin.css         # Admin dashboard specific styles
│   ├── /js
│   │   ├── main.js           # Fetch properties, Search logic
│   │   └── admin.js          # Admin Fetch logic (Add/Delete)
│   └── /uploads              # Property images go here
│
├── /pages                    # FRONTEND (HTML Views)
│   ├── index.html            # Homepage (Property Listings)
│   ├── login.html            # User Login
│   ├── register.html         # User Signup
│   ├── bookings.html         # User Dashboard (My Bookings)
│   └── admin.html            # Admin Dashboard (Protected)
│
├── database.sql              # The MySQL dump file
└── README.md                 # This documentation



-- Create Database
CREATE DATABASE IF NOT EXISTS makaziprime_db;
USE makaziprime_db;

-- 1. USERS TABLE
-- Stores both standard users (buyers) and admins
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'admin') DEFAULT 'buyer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. PROPERTY TYPES (Categorization)
-- Examples: Apartment, Villa, Office Space, Land
CREATE TABLE property_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. PROPERTIES TABLE (Main Inventory)
CREATE TABLE properties (
    property_id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,          -- e.g. "Luxury 2BR Apartment"
    description TEXT,
    location VARCHAR(100) NOT NULL,       -- e.g. "Masaki, Dar es Salaam"
    price DECIMAL(15, 2) NOT NULL,        
    bedrooms INT DEFAULT 0,               
    bathrooms INT DEFAULT 0,              
    area_sqft INT,                        
    status ENUM('available', 'sold', 'reserved') DEFAULT 'available',
    image_url VARCHAR(255),               -- Path to image in /assets/uploads/
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES property_types(type_id) ON DELETE CASCADE
);

-- 4. BOOKINGS TABLE (Transactions/Reservations)
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_fee DECIMAL(15, 2) NOT NULL,    -- Reservation Fee
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. BOOKING DETAILS (Normalization)
-- Links specific properties to a booking
CREATE TABLE booking_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    property_id INT NOT NULL,
    agreed_price DECIMAL(15, 2) NOT NULL, -- Price snapshot at booking time
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id)
);

-- 6. ADMIN LOGS (Audit Trail)
-- Tracks actions for system security analysis
CREATE TABLE admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
);



4. Implementation Guide
Phase A: Database Connection
File: /config/db.php

Use PDO (PHP Data Objects).

Strict Rule: Do not use mysqli_connect.

Wrap connection logic in a class or function to be included in API files.

Phase B: Admin & Property Management
File: /api/properties/create.php

POST Request Only: Ensure $_SERVER['REQUEST_METHOD'] === 'POST'.

File Upload:

Verify $_FILES['image'].

Validate file type (jpg/png/webp only).

Move file to /assets/uploads/.

Database Insert:

Use Prepared Statements: INSERT INTO properties (...) VALUES (?, ?, ?).

Phase C: Frontend & Fetch API
File: /assets/js/main.js

Do NOT use jQuery.

Use fetch() to call backend APIs.

JavaScript
// Example: Load Properties
async function loadProperties() {
    const response = await fetch('api/properties/read.php');
    const properties = await response.json();
    renderGrid(properties); // Function to update DOM
}


5. Security Checklist (For Project Report)
SQL Injection Prevention

Threat: Hackers inserting SQL code into input fields (e.g., ' OR 1=1 --).

Solution: Never concatenate strings in SQL queries. Always use PDO prepare() and execute() with placeholders (? or :name).

Cross-Site Scripting (XSS)

Threat: Malicious scripts injected into Property Descriptions.

Solution: When outputting user-generated content to HTML, sanitize it using htmlspecialchars() in PHP or use .innerText instead of .innerHTML in JavaScript.

Password Security

Threat: Storing passwords as plain text makes them readable if the DB is leaked.

Solution:

Registration: password_hash($password, PASSWORD_DEFAULT)

Login: password_verify($input_password, $stored_hash)