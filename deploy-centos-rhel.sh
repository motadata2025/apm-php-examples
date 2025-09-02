#!/bin/bash
# CentOS/RHEL Deployment Script for PHP APM Applications

echo "🚀 Deploying PHP APM Applications on CentOS/RHEL"

# Install EPEL and Remi repositories
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Enable PHP 8.3 module
sudo dnf module enable -y php:remi-8.3

# Install PHP and extensions
sudo dnf install -y php php-cli php-common
sudo dnf install -y php-curl php-json php-mbstring
sudo dnf install -y php-mysqlnd php-sqlite3 php-redis
sudo dnf install -y php-gd php-zip php-xml

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install dependencies for all applications
for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do
    if [ -d "$app" ]; then
        echo "Installing dependencies for $app..."
        cd "$app"
        composer install --no-dev --optimize-autoloader
        cd ..
    fi
done

echo "✅ CentOS/RHEL deployment complete"
