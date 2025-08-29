#!/bin/bash

# APM PHP Examples - Check Local Applications Status
# This script checks the status of locally running PHP applications

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
CONFIG_FILE="config/deployment.env"
NETWORK_IP="127.0.0.1"

# Load configuration if it exists
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
    NETWORK_IP="${NETWORK_INTERFACE:-127.0.0.1}"
fi

# Applications and their ports
declare -A apps=(
    ["simple-php"]=8080
    ["laravel-app"]=8081
    ["symfony-app"]=8082
    ["slim-framework"]=8083
    ["codeigniter-app"]=8084
)

echo "Local Applications Status:"
echo "========================="

# Function to check if port is open
check_port() {
    local port="$1"
    local app="$2"
    
    # Check if port is listening
    if command -v netstat >/dev/null 2>&1; then
        if netstat -tuln 2>/dev/null | grep -q ":$port "; then
            return 0
        fi
    elif command -v ss >/dev/null 2>&1; then
        if ss -tuln 2>/dev/null | grep -q ":$port "; then
            return 0
        fi
    fi
    
    # Fallback: try to connect
    if command -v nc >/dev/null 2>&1; then
        if nc -z "$NETWORK_IP" "$port" 2>/dev/null; then
            return 0
        fi
    fi
    
    return 1
}

# Function to check application health
check_health() {
    local port="$1"
    local app="$2"
    
    # Try health endpoint first
    if curl -s -f "http://$NETWORK_IP:$port/health" >/dev/null 2>&1; then
        return 0
    fi
    
    # Try root endpoint
    if curl -s -f "http://$NETWORK_IP:$port/" >/dev/null 2>&1; then
        return 0
    fi
    
    return 1
}

# Function to get PID from PID file
get_pid() {
    local app="$1"
    local pid_file=".$app.pid"
    
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            echo "$pid"
        else
            # Clean up stale PID file
            rm "$pid_file" 2>/dev/null
            echo ""
        fi
    else
        echo ""
    fi
}

# Check each application
for app in "${!apps[@]}"; do
    port="${apps[$app]}"
    pid=$(get_pid "$app")
    
    printf "%-15s " "$app:"
    
    if check_port "$port" "$app"; then
        if check_health "$port" "$app"; then
            if [ -n "$pid" ]; then
                echo -e "${GREEN}✅ Running (PID: $pid, Port: $port)${NC}"
            else
                echo -e "${GREEN}✅ Running (Port: $port)${NC}"
            fi
        else
            echo -e "${YELLOW}⚠️  Port open but not responding (Port: $port)${NC}"
        fi
    else
        if [ -n "$pid" ]; then
            echo -e "${YELLOW}⚠️  Process running but port not accessible (PID: $pid)${NC}"
        else
            echo -e "${RED}❌ Not running${NC}"
        fi
    fi
done

echo ""
echo "Quick Health Check:"
echo "=================="

# Quick health check for all apps
all_healthy=true
for app in "${!apps[@]}"; do
    port="${apps[$app]}"
    if check_health "$port" "$app"; then
        echo -e "${GREEN}✅ $app${NC}"
    else
        echo -e "${RED}❌ $app${NC}"
        all_healthy=false
    fi
done

echo ""
if [ "$all_healthy" = true ]; then
    echo -e "${GREEN}🎉 All applications are healthy!${NC}"
else
    echo -e "${YELLOW}⚠️  Some applications are not responding${NC}"
    echo ""
    echo "Troubleshooting:"
    echo "• Check if applications are started: ./start-all-local.sh"
    echo "• Check supporting services: make status"
    echo "• View application logs in their respective directories"
fi

echo ""
echo "Access URLs:"
echo "==========="
for app in "${!apps[@]}"; do
    port="${apps[$app]}"
    echo "  $app: http://$NETWORK_IP:$port"
done
