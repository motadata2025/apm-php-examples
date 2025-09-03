#!/bin/bash

set -e

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Create logs directory
mkdir -p augment/logs

# Check if composer is available
if ! command -v composer &> /dev/null; then
    echo "composer missing" > augment/logs/composer-missing.log
    echo "Error: Composer not found. Please install Composer first."
    exit 1
fi

# Install dependencies
echo "Installing Composer dependencies..."
composer install --no-interaction --no-scripts

# Check if PHP server is already running
if [ -f augment/php-server.pid ]; then
    PID=$(cat augment/php-server.pid)
    if ps -p $PID > /dev/null 2>&1; then
        echo "PHP server is already running (PID: $PID)"
        exit 0
    else
        rm -f augment/php-server.pid
    fi
fi

# Start PHP built-in server
echo "Starting PHP built-in server on 0.0.0.0:8080..."
php -S 0.0.0.0:8080 -t public > augment/logs/php-server.log 2>&1 &
SERVER_PID=$!
echo $SERVER_PID > augment/php-server.pid

echo "PHP server started with PID: $SERVER_PID"

# Wait for server to start
echo "Waiting for server to start..."
sleep 5

# Verify server is responding
if curl -sSf http://127.0.0.1:8080/ > /dev/null 2>&1; then
    echo "Server is responding successfully!"
    echo "Access the application at: http://127.0.0.1:8080/"
else
    echo "Error: Server is not responding"
    if [ -f augment/php-server.pid ]; then
        PID=$(cat augment/php-server.pid)
        kill $PID 2>/dev/null || true
        rm -f augment/php-server.pid
    fi
    echo "Check augment/logs/php-server.log for details"
    exit 1
fi
