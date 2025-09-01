#!/bin/bash

# Simple PHP Application - Status Script
# APM PHP Examples - Independent Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration files
CONFIG_DIR="config"
APP_CONFIG_FILE="$CONFIG_DIR/app.env"
NETWORK_STATE_FILE="$CONFIG_DIR/network.state"
PID_FILE="$CONFIG_DIR/app.pid"

# Application info
APP_NAME="Simple PHP"

# Function to check application status
check_application_status() {
    echo -e "\n${PURPLE}📊 Application Status${NC}"
    echo -e "====================="
    
    if [ -f "$PID_FILE" ]; then
        local pid=$(cat "$PID_FILE")
        if ps -p "$pid" > /dev/null 2>&1; then
            echo -e "  ${GREEN}✅ Application is running (PID: $pid)${NC}"
            
            # Get process info
            local cpu_mem=$(ps -p "$pid" -o %cpu,%mem --no-headers 2>/dev/null || echo "N/A N/A")
            echo -e "  ${BLUE}CPU/Memory: $cpu_mem${NC}"
            
            # Check if port is listening
            if [ -f "$APP_CONFIG_FILE" ]; then
                source "$APP_CONFIG_FILE"
                if command -v netstat >/dev/null 2>&1; then
                    if netstat -tuln 2>/dev/null | grep -q ":$APP_PORT "; then
                        echo -e "  ${GREEN}✅ Port $APP_PORT is listening${NC}"
                    else
                        echo -e "  ${YELLOW}⚠️  Port $APP_PORT is not listening${NC}"
                    fi
                fi
            fi
        else
            echo -e "  ${RED}❌ Application is not running (stale PID file)${NC}"
            rm -f "$PID_FILE"
        fi
    else
        echo -e "  ${YELLOW}⚠️  Application is not running${NC}"
    fi
}

# Function to show configuration status
show_configuration_status() {
    echo -e "\n${PURPLE}⚙️  Configuration Status${NC}"
    echo -e "========================="
    
    if [ -f "$APP_CONFIG_FILE" ]; then
        source "$APP_CONFIG_FILE"
        echo -e "  ${GREEN}✅ Configuration loaded${NC}"
        echo -e "    ${BLUE}PHP Version: ${PHP_VERSION:-N/A}${NC}"
        echo -e "    ${BLUE}Port: ${APP_PORT:-N/A}${NC}"
        echo -e "    ${BLUE}Deployment: ${DEPLOYMENT_DESC:-N/A}${NC}"
        echo -e "    ${BLUE}Network: ${NETWORK_DISPLAY:-$NETWORK_INTERFACE}${NC}"
    else
        echo -e "  ${RED}❌ No configuration found${NC}"
        echo -e "  ${YELLOW}Run: make compile - to configure the application${NC}"
    fi
}

# Function to show network status
show_network_status() {
    if [ -f "scripts/network-manager.sh" ]; then
        ./scripts/network-manager.sh status
    else
        echo -e "\n${YELLOW}⚠️  Network manager not available${NC}"
    fi
}

# Function to show PHP status
show_php_status() {
    if [ -f "scripts/php-version-manager.sh" ]; then
        ./scripts/php-version-manager.sh status
    else
        echo -e "\n${YELLOW}⚠️  PHP version manager not available${NC}"
    fi
}

# Function to show access information
show_access_info() {
    echo -e "\n${PURPLE}🌐 Access Information${NC}"
    echo -e "====================="
    
    if [ -f "$APP_CONFIG_FILE" ]; then
        source "$APP_CONFIG_FILE"
        
        if [ -f "$PID_FILE" ] && ps -p "$(cat "$PID_FILE")" > /dev/null 2>&1; then
            local current_ip=$(./scripts/network-manager.sh current-ip 2>/dev/null || echo "localhost")
            
            if [ "$NETWORK_INTERFACE" = "127.0.0.1" ]; then
                echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
            elif [ "$NETWORK_INTERFACE" = "0.0.0.0" ]; then
                echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
                echo -e "  ${BLUE}Network access: http://$current_ip:$APP_PORT${NC}"
                echo -e "  ${GREEN}✅ Dynamic IP safe configuration${NC}"
            else
                echo -e "  ${BLUE}Access: http://$NETWORK_INTERFACE:$APP_PORT${NC}"
            fi
        else
            echo -e "  ${YELLOW}⚠️  Application is not running${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  No configuration available${NC}"
    fi
}

# Function to show logs
show_recent_logs() {
    echo -e "\n${PURPLE}📋 Recent Logs${NC}"
    echo -e "==============="
    
    if [ -f "logs/app.log" ]; then
        echo -e "  ${BLUE}Last 10 lines from logs/app.log:${NC}"
        tail -10 logs/app.log 2>/dev/null || echo -e "  ${YELLOW}⚠️  Unable to read log file${NC}"
    else
        echo -e "  ${YELLOW}⚠️  No log file found${NC}"
    fi
}

# Main execution
main() {
    echo -e "${BLUE}📊 $APP_NAME - Status Report${NC}"
    echo -e "=================================="
    
    check_application_status
    show_configuration_status
    show_access_info
    show_network_status
    show_php_status
    show_recent_logs
    
    echo -e "\n${BLUE}Management Commands:${NC}"
    echo -e "  ${YELLOW}make start${NC}         - Start the application"
    echo -e "  ${YELLOW}make stop${NC}          - Stop the application"
    echo -e "  ${YELLOW}make compile${NC}       - Reconfigure the application"
    echo -e "  ${YELLOW}make php-status${NC}    - Show PHP version details"
    echo -e "  ${YELLOW}make network-status${NC} - Show network details"
}

# Execute main function
main "$@"
