#!/bin/bash
# slim-framework CLI Server Startup Script
# Usage: ./start-slim-framework.sh [IP] [PORT]

IP=${1:-0.0.0.0}
PORT=${2:-8080}

echo "🚀 Starting slim-framework CLI Server"
echo "IP: $IP, Port: $PORT"
echo "URL: http://$IP:$PORT/"
echo ""

cd "slim-framework"
php -S "$IP:$PORT" -t public
