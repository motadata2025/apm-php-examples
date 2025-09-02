#!/bin/bash
# Ubuntu/Debian Deployment Script for PHP APM Applications

echo "🚀 Deploying PHP APM Applications on Ubuntu/Debian"

# Update package list
sudo apt update

# Install PHP 8.3 and required extensions
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-common
sudo apt install -y php8.3-curl php8.3-json php8.3-mbstring
sudo apt install -y php8.3-mysql php8.3-sqlite3 php8.3-redis
sudo apt install -y php8.3-gd php8.3-zip php8.3-xml

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

echo "✅ Ubuntu/Debian deployment complete"
