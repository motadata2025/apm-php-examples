# Laravel Application - Manual Deployment Guide

## 📋 Overview

This guide provides comprehensive instructions for manually deploying the Laravel application **without using Makefiles**. This is useful for production environments, CI/CD pipelines, or situations where direct control over the deployment process is required.

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
                 php8.4-intl php8.4-bcmath php8.4-soap

# RHEL/CentOS
sudo yum install php84-cli php84-fpm php84-mysql php84-pgsql php84-redis \
                 php84-mbstring php84-xml php84-curl php84-zip php84-gd \
                 php84-intl php84-bcmath php84-soap
```

### **Required System Packages**
```bash
# Ubuntu/Debian
sudo apt install git curl unzip supervisor redis-server

# RHEL/CentOS
sudo yum install git curl unzip supervisor redis
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
      - ./docker/mysql/init:/docker-entrypoint-initdb.d
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
      - ./docker/postgres/init:/docker-entrypoint-initdb.d
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
docker compose logs postgres
docker compose logs redis
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

# Database (PostgreSQL) - Alternative
# DB_CONNECTION=pgsql
# DB_HOST=localhost
# DB_PORT=5437
# DB_DATABASE=laravel_app_db
# DB_USERNAME=postgres
# DB_PASSWORD=postgrespassword

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
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

# Broadcasting
BROADCAST_DRIVER=log
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

#### **1. Install Apache and PHP-FPM**
```bash
# Ubuntu/Debian
sudo apt install apache2 php8.4-fpm

# RHEL/CentOS
sudo yum install httpd php84-fpm
```

#### **2. Configure PHP-FPM**
Edit `/etc/php/8.4/fpm/pool.d/laravel.conf`:
```ini
[laravel]
user = www-data
group = www-data
listen = /run/php/php8.4-fpm-laravel.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
```

#### **3. Create Apache Virtual Host**
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
        SetHandler "proxy:unix:/run/php/php8.4-fpm-laravel.sock|fcgi://localhost"
    </FilesMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/laravel-app_error.log
    CustomLog ${APACHE_LOG_DIR}/laravel-app_access.log combined
</VirtualHost>

# Listen on port 8004
Listen 8004
```

#### **4. Enable Site and Modules**
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

#### **1. Install Nginx and PHP-FPM**
```bash
# Ubuntu/Debian
sudo apt install nginx php8.4-fpm

# RHEL/CentOS
sudo yum install nginx php84-fpm
```

#### **2. Create Nginx Server Block**
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

#### **3. Enable Site**
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

# Copy application files (adjust source path as needed)
sudo cp -r /path/to/laravel-app/* /var/www/laravel-app/

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

# Allow SSH (if needed)
sudo ufw allow ssh

# Enable firewall
sudo ufw enable
```

### **2. SSL/TLS Configuration (Production)**
For production deployments, configure SSL certificates:

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache  # For Apache
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Obtain certificate
sudo certbot --apache -d your-domain.com         # For Apache
sudo certbot --nginx -d your-domain.com          # For Nginx
```

## 📊 Monitoring and Maintenance

### **1. Log Monitoring**
```bash
# Application logs
tail -f /var/www/laravel-app/storage/logs/laravel.log

# Web server logs
tail -f /var/log/apache2/laravel-app_error.log   # Apache
tail -f /var/log/nginx/laravel-app_error.log     # Nginx

# PHP-FPM logs
tail -f /var/log/php8.4-fpm.log
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

# Check queue status
php /var/www/laravel-app/artisan queue:monitor
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
opcache.fast_shutdown=1
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
# Or for PostgreSQL
psql -h localhost -p 5437 -U postgres -d laravel_app_db
```

**Queue Worker Issues**:
```bash
# Restart queue workers
sudo supervisorctl restart laravel-worker:*

# Check worker status
sudo supervisorctl status
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

This manual deployment guide provides complete instructions for deploying the Laravel application without relying on Makefiles, suitable for production environments and CI/CD pipelines.
