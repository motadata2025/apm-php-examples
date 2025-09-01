#!/bin/bash

# Database Setup Script
# APM PHP Examples - Simple PHP Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${PURPLE}🗄️ Setting up databases for Simple PHP Application${NC}"
echo -e "=================================================="

# Load environment variables
if [ -f ".env" ]; then
    source .env
else
    echo -e "${RED}❌ .env file not found${NC}"
    exit 1
fi

# MySQL setup
echo -e "\n${BLUE}📊 Setting up MySQL database...${NC}"
mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} -e "
CREATE DATABASE IF NOT EXISTS ${DB_DATABASE};
USE ${DB_DATABASE};

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
);

-- Insert sample data
INSERT IGNORE INTO users (email, name) VALUES 
    ('john.doe@example.com', 'John Doe'),
    ('jane.smith@example.com', 'Jane Smith'),
    ('bob.johnson@example.com', 'Bob Johnson'),
    ('alice.brown@example.com', 'Alice Brown'),
    ('charlie.wilson@example.com', 'Charlie Wilson');

-- Create products table for additional testing
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Insert sample products
INSERT IGNORE INTO products (name, price, description) VALUES 
    ('Laptop', 999.99, 'High-performance laptop for development'),
    ('Mouse', 29.99, 'Wireless optical mouse'),
    ('Keyboard', 79.99, 'Mechanical keyboard with RGB lighting'),
    ('Monitor', 299.99, '27-inch 4K display'),
    ('Headphones', 149.99, 'Noise-cancelling wireless headphones');
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ MySQL database setup completed${NC}"
else
    echo -e "${RED}❌ MySQL database setup failed${NC}"
    exit 1
fi

# PostgreSQL setup
echo -e "\n${BLUE}🐘 Setting up PostgreSQL database...${NC}"
PGPASSWORD=${POSTGRES_PASSWORD} psql -h ${POSTGRES_HOST} -p ${POSTGRES_PORT} -U ${POSTGRES_USERNAME} -d postgres -c "
CREATE DATABASE ${POSTGRES_DATABASE};" 2>/dev/null || echo "Database already exists"

PGPASSWORD=${POSTGRES_PASSWORD} psql -h ${POSTGRES_HOST} -p ${POSTGRES_PORT} -U ${POSTGRES_USERNAME} -d ${POSTGRES_DATABASE} -c "
-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS \$\$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
\$\$ language 'plpgsql';

DROP TRIGGER IF EXISTS update_users_updated_at ON users;
CREATE TRIGGER update_users_updated_at 
    BEFORE UPDATE ON users 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

-- Insert sample data
INSERT INTO users (email, name) VALUES 
    ('postgres.user1@example.com', 'PostgreSQL User 1'),
    ('postgres.user2@example.com', 'PostgreSQL User 2'),
    ('postgres.user3@example.com', 'PostgreSQL User 3'),
    ('postgres.user4@example.com', 'PostgreSQL User 4'),
    ('postgres.user5@example.com', 'PostgreSQL User 5')
ON CONFLICT (email) DO NOTHING;

-- Create orders table for additional testing
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample orders
INSERT INTO orders (user_id, total_amount, status) VALUES 
    (1, 1299.97, 'completed'),
    (2, 79.99, 'pending'),
    (3, 449.98, 'shipped'),
    (4, 29.99, 'completed'),
    (5, 229.98, 'processing')
ON CONFLICT DO NOTHING;
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ PostgreSQL database setup completed${NC}"
else
    echo -e "${RED}❌ PostgreSQL database setup failed${NC}"
    exit 1
fi

# Redis setup (clear and set some sample data)
echo -e "\n${BLUE}🔴 Setting up Redis cache...${NC}"
redis-cli -h ${REDIS_HOST} -p ${REDIS_PORT} <<EOF
FLUSHDB
SET app:name "Simple PHP Application"
SET app:version "1.0.0"
SET app:environment "production"
HSET app:stats users_count 5 products_count 5 orders_count 5
SADD app:features "database" "cache" "queue" "api"
EXPIRE app:stats 3600
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Redis cache setup completed${NC}"
else
    echo -e "${RED}❌ Redis cache setup failed${NC}"
    exit 1
fi

echo -e "\n${GREEN}🎉 Database setup completed successfully!${NC}"
echo -e "\n${BLUE}📊 Summary:${NC}"
echo -e "  ${GREEN}✅ MySQL: Database '${DB_DATABASE}' with users and products tables${NC}"
echo -e "  ${GREEN}✅ PostgreSQL: Database '${POSTGRES_DATABASE}' with users and orders tables${NC}"
echo -e "  ${GREEN}✅ Redis: Cache initialized with application data${NC}"
echo -e "\n${YELLOW}💡 You can now test database operations in the application${NC}"
