# Laravel Application - Manual Deployment Guide

## 📋 Overview

This guide provides comprehensive instructions for manually deploying the Laravel application **without using Makefiles**. This is essential for production environments, CI/CD pipelines, or situations where direct control over the deployment process is required.

## 🎯 Prerequisites

### **System Requirements**
- **Operating System**: Ubuntu 20.04+, RHEL 8+, or compatible Linux distribution
- **PHP**: Version 8.1, 8.2, 8.3, or 8.4 with required extensions
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis 6.0+
- **Process Manager**: Supervisor (for queue workers)

### **Required PHP Extensions**
```bash
# Ubuntu/Debian
sudo apt install php8.4-cli php8.4-fpm php8.4-mysql php8.4-pgsql php8.4-redis \
                 php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd \
                 php8.4-intl php8.4-bcmath php8.4-soap php8.4-tokenizer

# RHEL/CentOS
sudo yum install php84-cli php84-fpm php84-mysql php84-pgsql php84-redis \
                 php84-mbstring php84-xml php84-curl php84-zip php84-gd \
                 php84-intl php84-bcmath php84-soap php84-tokenizer
```

## 🐳 Docker Setup (Supporting Services)

### **1. Create Docker Compose Configuration**

Create `docker-compose.yml`:
```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: laravel_mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: laravel_app_db
      MYSQL_USER: laravel
      MYSQL_PASSWORD: laravelpassword
    ports:
      - "3311:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - laravel_network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  postgres:
    image: postgres:15
    container_name: laravel_postgres
    restart: unless-stopped
    environment:
      POSTGRES_DB: laravel_app_db
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgrespassword
    ports:
      - "5437:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - laravel_network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    container_name: laravel_redis
    restart: unless-stopped
    ports:
      - "6384:6379"
    volumes:
      - redis_data:/data
    networks:
      - laravel_network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3

volumes:
  mysql_data:
  postgres_data:
  redis_data:

networks:
  laravel_network:
    driver: bridge
```

### **2. Start Docker Services**
```bash
# Start all services
docker compose up -d

# Verify services are healthy
docker compose ps

# Check logs if needed
docker compose logs mysql
```

## ⚙️ Laravel Application Configuration

### **1. Environment Configuration**

Create `.env` file:
```bash
# Application
APP_NAME="Laravel APM Example"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_URL=http://localhost:8004

# Database (MySQL)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3311
DB_DATABASE=laravel_app_db
DB_USERNAME=root
DB_PASSWORD=rootpassword

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=localhost
REDIS_PASSWORD=null
REDIS_PORT=6384

# Mail
MAIL_MAILER=log
MAIL_HOST=localhost
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@laravel-apm.local"
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
```

### **2. Install Dependencies**
```bash
# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Create storage symlink
php artisan storage:link

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### **3. Database Setup**
```bash
# Run database migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force
```

## 🌐 Web Server Configuration

### **Option A: Apache with PHP-FPM**

#### **1. Create Apache Virtual Host**
Create `/etc/apache2/sites-available/laravel-app.conf`:
```apache
<VirtualHost *:8004>
    ServerName laravel-app.local
    DocumentRoot /var/www/laravel-app/public
    
    <Directory /var/www/laravel-app/public>
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
        SetHandler "proxy:unix:/run/php/php8.4-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/laravel-app_error.log
    CustomLog ${APACHE_LOG_DIR}/laravel-app_access.log combined
</VirtualHost>

Listen 8004
```

#### **2. Enable Site**
```bash
# Enable required modules
sudo a2enmod rewrite proxy_fcgi setenvif

# Enable site
sudo a2ensite laravel-app

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart apache2
```

### **Option B: Nginx with PHP-FPM**

#### **1. Create Nginx Server Block**
Create `/etc/nginx/sites-available/laravel-app`:
```nginx
server {
    listen 8004;
    server_name laravel-app.local localhost;
    root /var/www/laravel-app/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
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
    access_log /var/log/nginx/laravel-app_access.log;
    error_log /var/log/nginx/laravel-app_error.log;
}
```

#### **2. Enable Site**
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/laravel-app /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

## 📁 File Permissions and Deployment

### **1. Copy Application Files**
```bash
# Create application directory
sudo mkdir -p /var/www/laravel-app

# Copy application files
sudo cp -r . /var/www/laravel-app/

# Set ownership
sudo chown -R www-data:www-data /var/www/laravel-app

# Set permissions
sudo find /var/www/laravel-app -type f -exec chmod 644 {} \;
sudo find /var/www/laravel-app -type d -exec chmod 755 {} \;

# Set special permissions for storage and cache
sudo chmod -R 775 /var/www/laravel-app/storage
sudo chmod -R 775 /var/www/laravel-app/bootstrap/cache
```

### **2. Configure Laravel Scheduler**
Add to crontab (`sudo crontab -e`):
```bash
* * * * * cd /var/www/laravel-app && php artisan schedule:run >> /dev/null 2>&1
```

### **3. Configure Queue Workers**
Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/laravel-app/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## 🔒 Security Configuration

### **1. Firewall Setup**
```bash
# Allow HTTP traffic on port 8004
sudo ufw allow 8004/tcp

# Enable firewall
sudo ufw enable
```

### **2. Laravel Security**
```bash
# Set proper APP_KEY
php artisan key:generate

# Configure trusted proxies in config/trustedproxy.php
# Configure CORS in config/cors.php
# Review security headers in middleware
```

## 📊 Monitoring and Maintenance

### **1. Log Monitoring**
```bash
# Application logs
tail -f /var/www/laravel-app/storage/logs/laravel.log

# Web server logs
tail -f /var/log/apache2/laravel-app_error.log   # Apache
tail -f /var/log/nginx/laravel-app_error.log     # Nginx
```

### **2. Health Checks**
```bash
# Test application response
curl -I http://localhost:8004

# Check database connectivity
php /var/www/laravel-app/artisan tinker
>>> DB::connection()->getPdo();

# Check Redis connectivity
redis-cli -p 6384 ping
```

### **3. Performance Optimization**
```bash
# Optimize Composer autoloader
composer dump-autoload --optimize

# Cache Laravel configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable OPcache (add to php.ini)
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
```

## 🚨 Troubleshooting

### **Common Issues**

**Permission Errors**:
```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/laravel-app/storage
sudo chmod -R 775 /var/www/laravel-app/storage
```

**Database Connection Issues**:
```bash
# Test database connection
mysql -h localhost -P 3311 -u root -p
```

**Queue Worker Issues**:
```bash
# Restart queue workers
sudo supervisorctl restart laravel-worker:*
```

**Web Server Issues**:
```bash
# Check Apache status
sudo systemctl status apache2
sudo apache2ctl configtest

# Check Nginx status
sudo systemctl status nginx
sudo nginx -t
```

## 🎯 Laravel-Specific Commands

### **Artisan Commands**
```bash
# Database operations
php artisan migrate
php artisan migrate:rollback
php artisan db:seed

# Cache management
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue management
php artisan queue:work
php artisan queue:restart
php artisan queue:monitor

# Development
php artisan serve --host=0.0.0.0 --port=8004
php artisan tinker
```

This manual deployment guide provides complete instructions for deploying the Laravel application without relying on Makefiles, suitable for production environments and CI/CD pipelines.
