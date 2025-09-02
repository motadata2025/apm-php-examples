#!/bin/bash

# Universal CLI Server Startup Script
# Purpose: Start any PHP APM application with configurable IP:Port

# Default configuration
DEFAULT_IP="0.0.0.0"
DEFAULT_PORT="8080"
DEFAULT_APP="simple-php"

# Available applications
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

# Function to display usage
show_usage() {
    echo "🚀 Universal PHP APM CLI Server Startup"
    echo ""
    echo "Usage: $0 [APPLICATION] [IP] [PORT]"
    echo ""
    echo "Available Applications:"
    for app in "${APPLICATIONS[@]}"; do
        echo "  - $app"
    done
    echo ""
    echo "Examples:"
    echo "  $0                           # Start simple-php on 0.0.0.0:8080"
    echo "  $0 laravel-app               # Start laravel-app on 0.0.0.0:8080"
    echo "  $0 symfony-app 127.0.0.1     # Start symfony-app on 127.0.0.1:8080"
    echo "  $0 slim-framework 0.0.0.0 9000  # Start slim-framework on 0.0.0.0:9000"
    echo ""
    echo "IP Options:"
    echo "  0.0.0.0    - Listen on all interfaces (default)"
    echo "  127.0.0.1  - Listen on localhost only"
    echo "  [custom]   - Listen on specific IP address"
    echo ""
    echo "Port Range: 1024-65535 (default: 8080)"
}

# Function to validate application
validate_application() {
    local app=$1
    for valid_app in "${APPLICATIONS[@]}"; do
        if [ "$app" = "$valid_app" ]; then
            return 0
        fi
    done
    return 1
}

# Function to validate IP address
validate_ip() {
    local ip=$1
    if [[ $ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        return 0
    fi
    return 1
}

# Function to validate port
validate_port() {
    local port=$1
    if [[ $port =~ ^[0-9]+$ ]] && [ $port -ge 1024 ] && [ $port -le 65535 ]; then
        return 0
    fi
    return 1
}

# Parse command line arguments
APP=${1:-$DEFAULT_APP}
IP=${2:-$DEFAULT_IP}
PORT=${3:-$DEFAULT_PORT}

# Show help if requested
if [ "$1" = "-h" ] || [ "$1" = "--help" ]; then
    show_usage
    exit 0
fi

# Validate inputs
if ! validate_application "$APP"; then
    echo "❌ Error: Invalid application '$APP'"
    echo ""
    show_usage
    exit 1
fi

if ! validate_ip "$IP"; then
    echo "❌ Error: Invalid IP address '$IP'"
    echo ""
    show_usage
    exit 1
fi

if ! validate_port "$PORT"; then
    echo "❌ Error: Invalid port '$PORT' (must be 1024-65535)"
    echo ""
    show_usage
    exit 1
fi

# Check if application directory exists
if [ ! -d "$APP" ]; then
    echo "❌ Error: Application directory '$APP' not found"
    exit 1
fi

# Check if public directory exists
if [ ! -d "$APP/public" ]; then
    echo "❌ Error: Public directory '$APP/public' not found"
    exit 1
fi

# Display startup information
echo "🚀 Starting PHP CLI Server"
echo "=========================="
echo "Application: $APP"
echo "IP Address:  $IP"
echo "Port:        $PORT"
echo "Document Root: $APP/public"
echo "URL:         http://$IP:$PORT/"
echo ""

# Check if port is already in use
if netstat -tuln 2>/dev/null | grep -q ":$PORT "; then
    echo "⚠️  Warning: Port $PORT appears to be in use"
    echo "   The server may fail to start or bind to a different port"
    echo ""
fi

# Start the server
echo "🔧 Starting server..."
echo "   Press Ctrl+C to stop the server"
echo ""

cd "$APP"
php -S "$IP:$PORT" -t public

echo ""
echo "🛑 Server stopped"
