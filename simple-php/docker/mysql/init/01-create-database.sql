-- MySQL Database Initialization for Simple PHP Application
-- Creates database and tables specific to Simple PHP

-- Create database
CREATE DATABASE IF NOT EXISTS simple_php_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE simple_php_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create sample data
INSERT IGNORE INTO users (name, email) VALUES 
('John Doe', 'john@example.com'),
('Jane Smith', 'jane@example.com'),
('Bob Johnson', 'bob@example.com');

-- Create additional tables for Simple PHP
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample posts
INSERT IGNORE INTO posts (user_id, title, content) VALUES 
(1, 'Welcome to Simple PHP', 'This is a sample post for the Simple PHP application.'),
(2, 'Getting Started', 'Learn how to use this application effectively.'),
(3, 'Advanced Features', 'Explore the advanced features of Simple PHP.');

-- Grant privileges
GRANT ALL PRIVILEGES ON simple_php_db.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON simple_php_db.* TO 'simple_php_user'@'%';

FLUSH PRIVILEGES;
