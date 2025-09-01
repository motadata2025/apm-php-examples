-- MySQL Database Initialization for CodeIgniter Application
-- Creates database and tables specific to CodeIgniter

-- Create database
CREATE DATABASE IF NOT EXISTS codeigniter_app_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE codeigniter_app_db;

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

-- Create additional tables for CodeIgniter
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
(1, 'Welcome to CodeIgniter', 'This is a sample post for the CodeIgniter application.'),
(2, 'Getting Started', 'Learn how to use this application effectively.'),
(3, 'Advanced Features', 'Explore the advanced features of CodeIgniter.');

-- Grant privileges
GRANT ALL PRIVILEGES ON codeigniter_app_db.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON codeigniter_app_db.* TO 'codeigniter_app_user'@'%';

FLUSH PRIVILEGES;
