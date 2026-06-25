-- Create Database
CREATE DATABASE IF NOT EXISTS animal_mart DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE animal_mart;

-- Disable Foreign Key Checks to prevent drop issues during re-runs
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS animals;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    role ENUM('buyer', 'seller', 'both') DEFAULT 'buyer',
    status ENUM('active', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins Table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Animals Table
CREATE TABLE animals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    seller_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    breed VARCHAR(100) NOT NULL,
    age_months INT NOT NULL,
    weight_kg DECIMAL(8,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    location VARCHAR(155) NOT NULL,
    description TEXT,
    main_image VARCHAR(255) NOT NULL,
    images TEXT, -- JSON Array of additional image URLs
    status ENUM('available', 'sold', 'pending') DEFAULT 'available',
    rating DECIMAL(3,2) DEFAULT 0.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders Table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT DEFAULT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    payment_method VARCHAR(50) NOT NULL,
    shipping_address TEXT NOT NULL,
    tracking_number VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items Table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    animal_id INT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_gateway VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews Table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    animal_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist Table
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    animal_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, animal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Messages Table
CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Categories
INSERT INTO categories (name, slug, image) VALUES
('Cow', 'cow', 'cow.png'),
('Goat', 'goat', 'goat.png'),
('Sheep', 'sheep', 'sheep.png'),
('Chicken', 'chicken', 'chicken.png'),
('Horse', 'horse', 'horse.png'),
('Dog', 'dog', 'dog.png'),
('Cat', 'cat', 'cat.png'),
('Rabbit', 'rabbit', 'rabbit.png'),
('Fish', 'fish', 'fish.png'),
('Birds', 'birds', 'birds.png');

-- Seed Users (Passwords are hashed 'password123')
INSERT INTO users (name, email, password, phone, address, role, status) VALUES
('Kiran Kumar', 'kiran@gmail.com', '$2y$10$wNnQ.jH533m81vVn46ZgKe4aVqC/U0Wd7gE3VfMszZ97.B7.o4b1i', '9876543210', '123 Main St, Chennai, Tamil Nadu', 'both', 'active'),
('John Doe', 'john@gmail.com', '$2y$10$wNnQ.jH533m81vVn46ZgKe4aVqC/U0Wd7gE3VfMszZ97.B7.o4b1i', '9123456780', '456 Cross St, Coimbatore, Tamil Nadu', 'buyer', 'active'),
('Seller Rajesh', 'rajesh@gmail.com', '$2y$10$wNnQ.jH533m81vVn46ZgKe4aVqC/U0Wd7gE3VfMszZ97.B7.o4b1i', '9345678901', '78 Farm Rd, Madurai, Tamil Nadu', 'seller', 'active');

-- Seed Admins (Password is hashed 'admin123')
INSERT INTO admins (username, password, email) VALUES
('admin', '$2y$10$tZ26mU7e31gX4G2/K4sQvOcjV8YkQ2d6nQ4hE6U5u57v19.W.m6Gq', 'admin@animalmart.com');

-- Seed Animals
-- Seller Rajesh is ID 3, Kiran is ID 1
INSERT INTO animals (category_id, seller_id, name, breed, age_months, weight_kg, price, location, description, main_image, images, status, rating) VALUES
(1, 3, 'Gir Cow', 'Gir', 36, 380.00, 75000.00, 'Madurai', 'Healthy Gir cow yielding 15 liters of milk daily. Vaccinated and vet checked.', 'gir_cow.jpg', '["gir_cow_side.jpg", "gir_cow_head.jpg"]', 'available', 4.5),
(2, 3, 'Jamunapari Goat', 'Jamunapari', 18, 45.00, 18000.00, 'Coimbatore', 'High-quality Jamunapari breeding buck. Fast growing and healthy.', 'jamunapari_goat.jpg', '[]', 'available', 4.8),
(6, 1, 'Golden Retriever Puppy', 'Golden Retriever', 3, 8.50, 25000.00, 'Chennai', 'Purebred Golden Retriever puppy, active, friendly, first dose of vaccination completed.', 'golden_retriever.jpg', '["golden_retriever2.jpg"]', 'available', 5.0),
(7, 1, 'Persian Cat', 'Persian Triple Coat', 12, 4.20, 12000.00, 'Chennai', 'Extremely playful Persian cat with standard triple coat. Litter trained.', 'persian_cat.jpg', '[]', 'available', 4.2),
(5, 3, 'Kathiawari Horse', 'Kathiawari Stallion', 48, 420.00, 350000.00, 'Tiruchirappalli', 'Beautiful Kathiawari stallion. Highly trained, suitable for riding and shows.', 'kathiawari_horse.jpg', '[]', 'available', 4.9),
(4, 3, 'Kadaknath Chicken', 'Kadaknath', 6, 1.80, 1200.00, 'Salem', 'Authentic black Kadaknath chickens. Excellent for breeding and healthy meat.', 'kadaknath_chicken.jpg', '[]', 'available', 4.0),
(8, 1, 'Angora Rabbit', 'English Angora', 4, 1.50, 2500.00, 'Ooty', 'Very fluffy white Angora rabbit. Friendly and kids-friendly.', 'angora_rabbit.jpg', '[]', 'available', 4.7),
(9, 1, 'Discus Fish Pair', 'Blue Diamond Discus', 8, 0.15, 6500.00, 'Chennai', 'Active breeding pair of Blue Diamond Discus fish. Eating well.', 'discus_fish.jpg', '[]', 'available', 4.6),
(10, 1, 'African Love Birds', 'Fischer Lovebirds', 5, 0.10, 3500.00, 'Madurai', 'Healthy active pair of green Fischer lovebirds with cage.', 'lovebirds.jpg', '[]', 'available', 4.3);

-- Seed Reviews
INSERT INTO reviews (animal_id, user_id, rating, comment, status) VALUES
(1, 2, 5, 'Excellent cow, yielding milk exactly as described! Very happy buyer.', 'approved'),
(3, 2, 4, 'Very playful puppy. Minor delay in getting vaccination card, but overall great experience.', 'approved');
