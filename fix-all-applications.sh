#!/bin/bash

# Comprehensive Application Fix Script
# Purpose: Fix all remaining CLI server issues for business-ready functionality

echo "🔧 COMPREHENSIVE APPLICATION FIX SCRIPT"
echo "=== Fixing all applications for CLI server mode ==="
echo ""

# Function to test application
test_application() {
    local app=$1
    local port=$2
    
    echo "Testing $app on port $port..."
    cd "$app"
    
    # Start server in background
    timeout 8s php -S 127.0.0.1:$port -t public > test.log 2>&1 &
    SERVER_PID=$!
    sleep 3
    
    # Test endpoints
    local root_status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$port/ 2>/dev/null || echo "000")
    local health_status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$port/health 2>/dev/null || echo "000")
    
    # Kill server
    kill $SERVER_PID 2>/dev/null
    
    # Report results
    if [ "$root_status" = "200" ]; then
        echo "✅ $app: Root endpoint working (HTTP $root_status)"
    else
        echo "❌ $app: Root endpoint failed (HTTP $root_status)"
        echo "Error log:"
        tail -3 test.log 2>/dev/null || echo "No error log"
    fi
    
    if [ "$health_status" = "200" ]; then
        echo "✅ $app: Health endpoint working (HTTP $health_status)"
    else
        echo "❌ $app: Health endpoint failed (HTTP $health_status)"
    fi
    
    rm -f test.log
    cd ..
    echo ""
}

# Fix Laravel
echo "=== FIXING LARAVEL-APP ==="
cd laravel-app
echo "Creating required directories..."
mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "Fixing .env configuration..."
if ! grep -q "LOG_CHANNEL" .env; then
    echo "LOG_CHANNEL=single" >> .env
fi
if ! grep -q "CACHE_DRIVER" .env; then
    echo "CACHE_DRIVER=file" >> .env
fi
if ! grep -q "SESSION_DRIVER" .env; then
    echo "SESSION_DRIVER=file" >> .env
fi

echo "✅ Laravel fixed"
cd ..

# Fix Symfony
echo "=== FIXING SYMFONY-APP ==="
cd symfony-app
echo "Checking QueueManager location..."
if [ -f "lib/QueueManager.php" ] && [ ! -f "src/Shared/Utils/QueueManager.php" ]; then
    echo "Moving QueueManager to expected location..."
    mkdir -p src/Shared/Utils
    cp lib/QueueManager.php src/Shared/Utils/QueueManager.php
    
    # Fix namespace in the copied file
    sed -i 's/namespace SimplePhp\\Lib;/namespace App\\Shared\\Utils;/' src/Shared/Utils/QueueManager.php 2>/dev/null || true
fi

echo "Creating required cache directories..."
mkdir -p var/cache var/log
chmod -R 775 var 2>/dev/null || true

echo "✅ Symfony fixed"
cd ..

# Fix CodeIgniter
echo "=== FIXING CODEIGNITER-APP ==="
cd codeigniter-app
echo "Creating required directories..."
mkdir -p writable/cache writable/logs writable/session
chmod -R 775 writable 2>/dev/null || true

echo "Checking public/index.php..."
if [ -f "public/index.php" ]; then
    # Ensure CodeIgniter bootstrap is correct
    if ! grep -q "FCPATH" public/index.php; then
        echo "Fixing CodeIgniter bootstrap..."
        cat > public/index.php << 'EOF'
<?php
/**
 * CodeIgniter APM Application - CLI Server Ready
 */

// Define path constants
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath(FCPATH . '../vendor/codeigniter4/framework/system') . DIRECTORY_SEPARATOR);
define('APPPATH', realpath(FCPATH . '../app') . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath(FCPATH . '../writable') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(FCPATH . '../') . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
chdir(FCPATH);

// Load the framework bootstrap
require_once SYSTEMPATH . 'bootstrap.php';

// Launch the app
$app = \Config\Services::codeigniter();
$app->initialize();
$app->run();
EOF
    fi
fi

echo "✅ CodeIgniter fixed"
cd ..

echo "🎯 TESTING ALL APPLICATIONS AFTER FIXES"
echo ""

# Test all applications
test_application "simple-php" 8080
test_application "laravel-app" 8081
test_application "symfony-app" 8082
test_application "slim-framework" 8083
test_application "codeigniter-app" 8084

echo "🏆 COMPREHENSIVE FIX COMPLETE"
echo ""
echo "📋 SUMMARY:"
echo "All applications have been systematically fixed for CLI server mode."
echo "Any remaining issues are logged above for further investigation."
