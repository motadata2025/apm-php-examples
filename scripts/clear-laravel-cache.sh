#!/bin/bash

# Clear Laravel Configuration Cache
# This script clears Laravel's cached configuration to force reload of config/app.php

echo "🧹 Clearing Laravel Configuration Cache..."

# Remove Laravel configuration cache files
sudo rm -f /var/www/laravel-app/bootstrap/cache/config.php 2>/dev/null || true
sudo rm -f /var/www/laravel-app/bootstrap/cache/services.php 2>/dev/null || true
sudo rm -f /var/www/laravel-app/bootstrap/cache/packages.php 2>/dev/null || true
sudo rm -f /var/www/laravel-app/bootstrap/cache/routes.php 2>/dev/null || true

echo "✅ Laravel cache cleared successfully"

# Also clear any potential cache in source directory
rm -f bootstrap/cache/config.php 2>/dev/null || true
rm -f bootstrap/cache/services.php 2>/dev/null || true
rm -f bootstrap/cache/packages.php 2>/dev/null || true
rm -f bootstrap/cache/routes.php 2>/dev/null || true

echo "✅ Source cache cleared successfully"
echo "🔄 Laravel will now reload configuration from config/app.php"
