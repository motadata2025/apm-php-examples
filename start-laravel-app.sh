#!/bin/bash
# laravel-app CLI Server Startup Script
# Usage: ./start-laravel-app.sh [IP] [PORT]

IP=${1:-0.0.0.0}
PORT=${2:-8080}

echo "🚀 Starting laravel-app CLI Server"
echo "IP: $IP, Port: $PORT"
echo "URL: http://$IP:$PORT/"
echo ""

cd "laravel-app"
php -S "$IP:$PORT" -t public
