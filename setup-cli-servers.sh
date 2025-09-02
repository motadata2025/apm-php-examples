#!/bin/bash

# Business-Ready CLI Server Setup Script
# Purpose: Configure all PHP APM applications for CLI server mode

echo "🚀 BUSINESS-READY CLI SERVER SETUP"
echo "=== Configuring all applications for CLI mode ==="
echo ""

# Configuration
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
BASE_PORT=8080

# Function to test CLI server
test_cli_server() {
    local app=$1
    local port=$2
    
    echo "Testing $app on port $port..."
    cd "$app"
    
    # Start server in background
    timeout 10s php -S 127.0.0.1:$port -t public > server.log 2>&1 &
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
    fi
    
    if [ "$health_status" = "200" ]; then
        echo "✅ $app: Health endpoint working (HTTP $health_status)"
    else
        echo "❌ $app: Health endpoint failed (HTTP $health_status)"
    fi
    
    # Show any errors
    if [ -f "server.log" ] && [ -s "server.log" ]; then
        echo "Server log for $app:"
        head -3 server.log
    fi
    
    rm -f server.log
    cd ..
    echo ""
}

# Setup each application
for i in "${!APPLICATIONS[@]}"; do
    app="${APPLICATIONS[$i]}"
    port=$((BASE_PORT + i))
    
    echo "=== Setting up $app (Port $port) ==="
    
    if [ ! -d "$app" ]; then
        echo "❌ Directory $app not found"
        continue
    fi
    
    cd "$app"
    
    # Ensure public directory exists
    if [ ! -d "public" ]; then
        echo "❌ No public directory found in $app"
        cd ..
        continue
    fi
    
    # Framework-specific setup
    case $app in
        "laravel-app")
            echo "Setting up Laravel..."
            if [ ! -f ".env" ]; then
                echo "Creating Laravel .env file..."
                echo "APP_NAME=Laravel_APM" > .env
                echo "APP_ENV=local" >> .env
                echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env
                echo "APP_DEBUG=true" >> .env
                echo "APP_URL=http://localhost:$port" >> .env
            fi
            ;;
        "symfony-app")
            echo "Setting up Symfony..."
            if [ ! -f ".env" ]; then
                echo "Creating Symfony .env file..."
                echo "APP_ENV=dev" > .env
                echo "APP_SECRET=$(openssl rand -hex 32)" >> .env
            fi
            ;;
        "codeigniter-app")
            echo "Setting up CodeIgniter..."
            # CodeIgniter specific setup if needed
            ;;
        "slim-framework")
            echo "Setting up Slim Framework..."
            # Slim specific setup if needed
            ;;
        "simple-php")
            echo "Setting up Simple PHP..."
            # Already configured
            ;;
    esac
    
    cd ..
    
    # Test the CLI server
    test_cli_server "$app" "$port"
done

echo "🎯 CLI SERVER SETUP COMPLETE"
echo ""
echo "📋 USAGE INSTRUCTIONS:"
echo "To start individual applications:"
for i in "${!APPLICATIONS[@]}"; do
    app="${APPLICATIONS[$i]}"
    port=$((BASE_PORT + i))
    echo "  $app: cd $app && php -S 0.0.0.0:$port -t public"
done

echo ""
echo "🌐 Access URLs:"
for i in "${!APPLICATIONS[@]}"; do
    app="${APPLICATIONS[$i]}"
    port=$((BASE_PORT + i))
    echo "  $app: http://localhost:$port/"
done

echo ""
echo "✅ All applications configured for CLI server mode!"
