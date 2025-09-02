#!/bin/bash
# simple-php CLI Server Startup Script
# Usage: ./start-simple-php.sh [IP] [PORT]

IP=${1:-0.0.0.0}
PORT=${2:-8080}

echo "🚀 Starting simple-php CLI Server"
echo "IP: $IP, Port: $PORT"
echo "URL: http://$IP:$PORT/"
echo ""

cd "simple-php"
php -S "$IP:$PORT" -t public
