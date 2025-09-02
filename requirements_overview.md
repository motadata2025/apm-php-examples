# APM PHP Examples - Requirements Overview

## 📋 Overview

This document provides a consolidated overview of all sample applications in the APM PHP Examples repository, including their dependencies, ports, environment variables, and Docker setup requirements.

## 🏗️ Applications Summary

### **1. Simple PHP Application**
- **Framework**: Vanilla PHP
- **Port**: 8000
- **Purpose**: Comprehensive PHP application for APM testing and demonstration

### **2. Laravel Application**
- **Framework**: Laravel
- **Port**: 8004
- **Purpose**: Modern PHP framework with Eloquent ORM and Artisan CLI

### **3. Symfony Application**
- **Framework**: Symfony
- **Port**: 8002
- **Purpose**: Component-based PHP framework with Doctrine integration

### **4. Slim Framework Application**
- **Framework**: Slim
- **Port**: 8001
- **Purpose**: Microframework for APIs and small applications

### **5. CodeIgniter Application**
- **Framework**: CodeIgniter
- **Port**: 8003
- **Purpose**: Simple and elegant PHP framework with MVC pattern

## 🔌 Port Allocation Matrix

| Application       | App  | MySQL | PostgreSQL | Redis | Adminer |
|-------------------|------|-------|------------|-------|---------|
| simple-php        | 8000 | 3307  | 5433       | 6380  | 8080    |
| slim-framework    | 8001 | 3309  | 5435       | 6382  | 8081    |
| symfony-app       | 8002 | 3308  | 5434       | 6381  | 8082    |
| codeigniter-app   | 8003 | 3310  | 5436       | 6383  | 8083    |
| laravel-app       | 8004 | 3311  | 5437       | 6384  | 8084    |

## 🐳 Docker Services

Each application includes the following Docker services:

### **Database Services**
- **MySQL 8.0**: Primary database with application-specific database names
- **PostgreSQL 15**: Secondary database for multi-database testing
- **Redis 7-alpine**: Cache and session storage with 256MB memory limit

### **Management Tools**
- **Adminer**: Web-based database management interface (optional profile)

### **Network Configuration**
- **Isolated Networks**: Each app has its own Docker network (172.30-34.0.0/16)
- **Health Checks**: All services include comprehensive health monitoring
- **Persistent Volumes**: Data persistence for databases and cache

## ⚙️ System Requirements

### **Core Dependencies**
- **PHP**: 8.1, 8.2, 8.3, or 8.4
- **Docker**: Latest version with Docker Compose v2 support
- **Composer**: PHP dependency manager

### **PHP Extensions Required**
- `mysql` - MySQL database connectivity
- `pgsql` - PostgreSQL database connectivity  
- `redis` - Redis cache connectivity
- `curl` - HTTP client functionality
- `json` - JSON processing
- `mbstring` - Multibyte string handling

### **Web Server Options**
- **PHP Built-in Server** (default for development)
- **Apache 2.4+** with mod_php or PHP-FPM
- **Nginx 1.18+** with PHP-FPM

## 🔧 Environment Variables

### **Application Settings**
```bash
APP_NAME="Application Name"
APP_PORT=8000-8004
APP_ENV=production
APP_DEBUG=false
PHP_VERSION=8.4
```

### **Database Configuration**
```bash
# MySQL
MYSQL_HOST=localhost
MYSQL_PORT=3307-3311
MYSQL_DATABASE=app_specific_db
MYSQL_USERNAME=root
MYSQL_PASSWORD=rootpassword

# PostgreSQL  
POSTGRES_HOST=localhost
POSTGRES_PORT=5433-5437
POSTGRES_DATABASE=app_specific_db
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword

# Redis
REDIS_HOST=localhost
REDIS_PORT=6380-6384
```

### **Docker Configuration**
```bash
DOCKER_COMPOSE_CMD="docker compose"
DOCKER_NETWORK="app_network"
DOCKER_HEALTH_TIMEOUT=120
```

## 🚀 Quick Start Guide

### **Prerequisites Installation**
```bash
# Install Docker (Ubuntu/Debian)
sudo apt update
sudo apt install docker.io docker-compose-plugin

# Install PHP and extensions
sudo apt install php8.4 php8.4-cli php8.4-mysql php8.4-pgsql php8.4-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### **Running Any Application**
```bash
# Navigate to application directory
cd {application-name}

# Start Docker services
docker compose up -d

# Install dependencies
composer install

# Start application (varies by framework)
# Simple PHP: php -S localhost:8000 -t public
# Laravel: php artisan serve --port=8004
# Symfony: symfony server:start --port=8002
# Slim: php -S localhost:8001 -t public
# CodeIgniter: php spark serve --port=8003
```

### **Verification Commands**
```bash
# Check Docker services
docker compose ps

# Test database connections
mysql -h localhost -P 3307 -u root -p
psql -h localhost -p 5433 -U postgres -d app_db

# Test Redis connection
redis-cli -h localhost -p 6380 ping
```

## 🛡️ Security Considerations

### **Default Credentials**
- **MySQL Root**: `rootpassword`
- **PostgreSQL**: `postgrespassword`
- **Network Binding**: localhost only (127.0.0.1)

### **Production Recommendations**
- Change all default passwords
- Use environment-specific configuration files
- Enable firewall rules for external access
- Use HTTPS in production environments
- Implement proper authentication and authorization

## 📁 Essential Files Structure

Each application contains:
```
app-name/
├── docker-compose.yml          # Docker services configuration
├── composer.json              # PHP dependencies
├── public/                    # Web-accessible files
├── config/                    # Configuration files
├── docker/                    # Docker initialization scripts
└── README.md                  # Application-specific documentation
```

## 🔍 Troubleshooting

### **Common Issues**
1. **Port Conflicts**: Check if ports are already in use with `netstat -tuln`
2. **Docker Issues**: Ensure Docker daemon is running with `sudo systemctl start docker`
3. **PHP Extensions**: Verify extensions with `php -m | grep -E "(mysql|pgsql|redis)"`
4. **Permissions**: Ensure proper file permissions for web directories

### **Health Check Commands**
```bash
# Docker service health
docker compose ps

# Application connectivity
curl http://localhost:8000-8004

# Database connectivity
docker compose exec mysql-service mysql -u root -p -e "SELECT 1"
```

This overview provides all essential information needed to run any application in the repository with minimal setup complexity.
