# APM PHP Examples - Manual Deployment Guidebook

## 📋 Overview

This comprehensive guidebook provides step-by-step instructions for manually deploying all PHP applications **without using Makefiles**. It mirrors the Makefile workflows and is designed for production environments, CI/CD pipelines, or situations requiring direct control over the deployment process.

## 🎯 Prerequisites

### **System Requirements**
- **Operating System**: Ubuntu 20.04+, RHEL 8+, Arch Linux, or compatible Linux distribution
- **Docker**: Version 20.10+ with Docker Compose v2 (or v1 fallback)
- **PHP**: Version 8.1, 8.2, 8.3, or 8.4 with required extensions
- **Composer**: Latest stable version for dependency management
- **Git**: For repository management and updates

### **Required PHP Extensions**
```bash
# Ubuntu/Debian
sudo apt install php8.4-cli php8.4-fpm php8.4-mysql php8.4-pgsql php8.4-redis \
                 php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd \
                 php8.4-intl php8.4-bcmath php8.4-tokenizer

# RHEL/CentOS/AlmaLinux
sudo yum install php84-cli php84-fpm php84-mysql php84-pgsql php84-redis \
                 php84-mbstring php84-xml php84-curl php84-zip php84-gd \
                 php84-intl php84-bcmath php84-tokenizer

# Arch Linux
sudo pacman -S php php-fpm php-mysql php-pgsql php-redis php-intl php-gd
```

### **System Packages**
```bash
# Ubuntu/Debian
sudo apt install git curl unzip supervisor redis-server mysql-client postgresql-client

# RHEL/CentOS
sudo yum install git curl unzip supervisor redis mysql postgresql

# Arch Linux
sudo pacman -S git curl unzip supervisor redis mysql postgresql
```

## 🏗️ Universal Deployment Process

### **Step 1: Environment Setup**

#### **1.1 Clone and Navigate**
```bash
# Clone repository (if not already done)
git clone <repository-url>
cd apm-php-examples

# Navigate to specific application
cd <application-name>  # simple-php, slim-framework, symfony-app, codeigniter-app, laravel-app
```

#### **1.2 Environment Configuration**
```bash
# Copy environment template
cp .env.example .env
cp config/app.env.example config/app.env

# Edit configuration files
nano .env
nano config/app.env
```

**Required Environment Variables:**
```bash
# Application Settings
APP_NAME="<Application Name>"
APP_PORT=<port>          # 8000-8004 depending on app
APP_ENV=production       # or development
APP_DEBUG=false          # true for development

# Database Configuration
MYSQL_HOST=localhost
MYSQL_PORT=<mysql-port>  # 3307-3311 depending on app
MYSQL_DATABASE=<db-name>
MYSQL_USERNAME=root
MYSQL_PASSWORD=rootpassword

POSTGRES_HOST=localhost
POSTGRES_PORT=<pg-port>  # 5433-5437 depending on app
POSTGRES_DATABASE=<db-name>
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword

REDIS_HOST=localhost
REDIS_PORT=<redis-port>  # 6380-6384 depending on app
```

### **Step 2: Docker Services Setup**

#### **2.1 Start Docker Services**
```bash
# Validate Docker Compose configuration
docker compose config

# Start all services in background
docker compose up -d

# Verify services are running
docker compose ps

# Check service health
docker compose logs mysql
docker compose logs postgres
docker compose logs redis
```

#### **2.2 Wait for Service Readiness**
```bash
# Wait for MySQL to be ready
until docker exec <app>_mysql mysqladmin ping -h localhost --silent; do
    echo "Waiting for MySQL..."
    sleep 2
done

# Wait for PostgreSQL to be ready
until docker exec <app>_postgres pg_isready -U postgres; do
    echo "Waiting for PostgreSQL..."
    sleep 2
done

# Wait for Redis to be ready
until docker exec <app>_redis redis-cli ping; do
    echo "Waiting for Redis..."
    sleep 2
done

echo "All services are ready!"
```

### **Step 3: Application Dependencies**

#### **3.1 Install PHP Dependencies**
```bash
# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# For development environment
composer install --optimize-autoloader

# Update dependencies if needed
composer update
```

#### **3.2 Framework-Specific Setup**

**Laravel Applications:**
```bash
# Generate application key
php artisan key:generate

# Create storage symlink
php artisan storage:link

# Cache configuration (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear cache (development)
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Symfony Applications:**
```bash
# Clear cache
php bin/console cache:clear --env=prod

# Warm up cache
php bin/console cache:warmup --env=prod

# For development
php bin/console cache:clear --env=dev
```

**CodeIgniter Applications:**
```bash
# No specific setup required
# Ensure writable directories have proper permissions
chmod -R 775 writable/
```

### **Step 4: Database Setup**

#### **4.1 Database Connection Testing**
```bash
# Test MySQL connection
mysql -h localhost -P <mysql-port> -u root -p<password> -e "SELECT 1;"

# Test PostgreSQL connection
PGPASSWORD=<password> psql -h localhost -p <pg-port> -U postgres -d <database> -c "SELECT 1;"

# Test Redis connection
redis-cli -h localhost -p <redis-port> ping
```

#### **4.2 Run Database Migrations**

**Laravel:**
```bash
# Run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force

# Or combined
php artisan migrate:fresh --seed --force
```

**Symfony:**
```bash
# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures (if available)
php bin/console doctrine:fixtures:load --no-interaction
```

**CodeIgniter:**
```bash
# Run migrations
php spark migrate

# Run seeders
php spark db:seed <SeederName>
```

### **Step 5: Web Server Deployment**

#### **Option A: PHP Built-in Server (Development)**
```bash
# Start PHP built-in server
php -S 0.0.0.0:<app-port> -t public/

# For applications without public/ directory
php -S 0.0.0.0:<app-port>

# Background execution
nohup php -S 0.0.0.0:<app-port> -t public/ > server.log 2>&1 &
```

#### **Option B: Apache with PHP-FPM (Production)**

**Install Apache and PHP-FPM:**
```bash
# Ubuntu/Debian
sudo apt install apache2 php8.4-fpm

# RHEL/CentOS
sudo yum install httpd php84-fpm
```

**Configure PHP-FPM Pool:**
```bash
# Create application-specific pool
sudo tee /etc/php/8.4/fpm/pool.d/<app-name>.conf << EOF
[<app-name>]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm-<app-name>.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
EOF

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

**Create Apache Virtual Host:**
```bash
# Create virtual host configuration
sudo tee /etc/apache2/sites-available/<app-name>.conf << EOF
<VirtualHost *:<app-port>>
    ServerName <app-name>.local
    DocumentRoot /var/www/<app-name>/public
    
    <Directory /var/www/<app-name>/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.4-fpm-<app-name>.sock|fcgi://localhost"
    </FilesMatch>
    
    ErrorLog \${APACHE_LOG_DIR}/<app-name>_error.log
    CustomLog \${APACHE_LOG_DIR}/<app-name>_access.log combined
</VirtualHost>

Listen <app-port>
EOF

# Enable required modules
sudo a2enmod rewrite proxy_fcgi setenvif

# Enable site
sudo a2ensite <app-name>

# Restart Apache
sudo systemctl restart apache2
```

#### **Option C: Nginx with PHP-FPM (Production)**

**Install Nginx:**
```bash
# Ubuntu/Debian
sudo apt install nginx

# RHEL/CentOS
sudo yum install nginx
```

**Create Nginx Server Block:**
```bash
# Create server block
sudo tee /etc/nginx/sites-available/<app-name> << EOF
server {
    listen <app-port>;
    server_name <app-name>.local localhost;
    root /var/www/<app-name>/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Application routing
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Security: deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/<app-name>_access.log;
    error_log /var/log/nginx/<app-name>_error.log;
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/<app-name> /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

### **Step 6: File Permissions and Deployment**

#### **6.1 Copy Application Files**
```bash
# Create application directory
sudo mkdir -p /var/www/<app-name>

# Copy application files
sudo cp -r . /var/www/<app-name>/

# Set ownership
sudo chown -R www-data:www-data /var/www/<app-name>

# Set permissions
sudo find /var/www/<app-name> -type f -exec chmod 644 {} \;
sudo find /var/www/<app-name> -type d -exec chmod 755 {} \;
```

#### **6.2 Framework-Specific Permissions**

**Laravel:**
```bash
# Set special permissions for storage and cache
sudo chmod -R 775 /var/www/<app-name>/storage
sudo chmod -R 775 /var/www/<app-name>/bootstrap/cache
```

**Symfony:**
```bash
# Set permissions for var directory
sudo chmod -R 775 /var/www/<app-name>/var
```

**CodeIgniter:**
```bash
# Set permissions for writable directory
sudo chmod -R 775 /var/www/<app-name>/writable
```

### **Step 7: Process Management (Production)**

#### **7.1 Laravel Queue Workers**
```bash
# Create Supervisor configuration
sudo tee /etc/supervisor/conf.d/<app-name>-worker.conf << EOF
[program:<app-name>-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/<app-name>/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/<app-name>/storage/logs/worker.log
stopwaitsecs=3600
EOF

# Update Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start <app-name>-worker:*
```

#### **7.2 Laravel Scheduler**
```bash
# Add to crontab
echo "* * * * * cd /var/www/<app-name> && php artisan schedule:run >> /dev/null 2>&1" | sudo crontab -
```

### **Step 8: Security Configuration**

#### **8.1 Firewall Setup**
```bash
# Allow application port
sudo ufw allow <app-port>/tcp

# Allow SSH (if needed)
sudo ufw allow ssh

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

#### **8.2 SSL/TLS Configuration (Production)**
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Obtain certificate
sudo certbot --apache -d your-domain.com         # For Apache
sudo certbot --nginx -d your-domain.com          # For Nginx
```

## 🔧 Application-Specific Variations

### **Simple PHP Application**
- **Port**: 8000
- **Database Ports**: MySQL 3307, PostgreSQL 5433, Redis 6380
- **Special Notes**: No framework-specific setup required
- **Entry Point**: `public/index.php`

### **Slim Framework Application**
- **Port**: 8001
- **Database Ports**: MySQL 3309, PostgreSQL 5435, Redis 6382
- **Special Notes**: Lightweight framework, minimal setup
- **Entry Point**: `public/index.php`

### **Symfony Application**
- **Port**: 8002
- **Database Ports**: MySQL 3308, PostgreSQL 5434, Redis 6381
- **Special Notes**: Requires cache warming, has 51 migrations
- **Entry Point**: `public/index.php`
- **Console**: `php bin/console`

### **CodeIgniter Application**
- **Port**: 8003
- **Database Ports**: MySQL 3310, PostgreSQL 5436, Redis 6383
- **Special Notes**: Has 10 migrations and 7 seeders
- **Entry Point**: `public/index.php`
- **CLI**: `php spark`

### **Laravel Application**
- **Port**: 8004
- **Database Ports**: MySQL 3311, PostgreSQL 5437, Redis 6384
- **Special Notes**: Requires key generation, has 23 migrations and 6 seeders
- **Entry Point**: `public/index.php`
- **Artisan**: `php artisan`

## 🚨 Troubleshooting Guide

### **Common Issues and Solutions**

#### **Connection Refused Errors**
```bash
# Check if services are running
docker compose ps

# Check service logs
docker compose logs <service-name>

# Restart services
docker compose restart <service-name>
```

#### **Permission Errors**
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/<app-name>

# Fix permissions
sudo chmod -R 755 /var/www/<app-name>
sudo chmod -R 775 /var/www/<app-name>/storage  # Laravel
sudo chmod -R 775 /var/www/<app-name>/var      # Symfony
sudo chmod -R 775 /var/www/<app-name>/writable # CodeIgniter
```

#### **Database Connection Issues**
```bash
# Test database connectivity
mysql -h localhost -P <port> -u root -p
psql -h localhost -p <port> -U postgres -d <database>
redis-cli -h localhost -p <port> ping

# Check Docker network
docker network ls
docker network inspect <app>_network
```

#### **Port Conflicts**
```bash
# Check what's using the port
sudo netstat -tulnp | grep :<port>
sudo ss -tulnp | grep :<port>

# Kill process using port
sudo kill -9 <PID>

# Change port in configuration
# Edit .env and config/app.env files
```

#### **Missing PHP Extensions**
```bash
# Check installed extensions
php -m

# Install missing extensions
sudo apt install php8.4-<extension>  # Ubuntu/Debian
sudo yum install php84-<extension>   # RHEL/CentOS

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### **Health Check Commands**
```bash
# Application health
curl -I http://localhost:<app-port>

# Database connectivity
mysql -h localhost -P <mysql-port> -u root -p -e "SELECT 1;"
PGPASSWORD=<password> psql -h localhost -p <pg-port> -U postgres -c "SELECT 1;"
redis-cli -h localhost -p <redis-port> ping

# Service status
sudo systemctl status apache2    # or nginx
sudo systemctl status php8.4-fpm
docker compose ps
```

## 📊 Performance Optimization

### **Production Optimizations**
```bash
# PHP OPcache (add to php.ini)
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2

# Composer optimization
composer dump-autoload --optimize --classmap-authoritative

# Framework-specific optimizations
php artisan config:cache    # Laravel
php artisan route:cache     # Laravel
php artisan view:cache      # Laravel
php bin/console cache:warmup --env=prod  # Symfony
```

### **Monitoring Setup**
```bash
# Log monitoring
tail -f /var/log/apache2/<app-name>_error.log
tail -f /var/log/nginx/<app-name>_error.log
tail -f /var/www/<app-name>/storage/logs/laravel.log

# Resource monitoring
htop
docker stats
```

This manual deployment guidebook provides comprehensive instructions for deploying all APM PHP applications without relying on Makefiles, suitable for production environments and CI/CD pipelines.
