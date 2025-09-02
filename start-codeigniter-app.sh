#!/bin/bash
# codeigniter-app CLI Server Startup Script
# Usage: ./start-codeigniter-app.sh [IP] [PORT]

IP=${1:-0.0.0.0}
PORT=${2:-8080}

echo "🚀 Starting codeigniter-app CLI Server"
echo "IP: $IP, Port: $PORT"
echo "URL: http://$IP:$PORT/"
echo ""

cd "codeigniter-app"
php -S "$IP:$PORT" -t public
