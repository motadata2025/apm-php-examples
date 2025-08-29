#!/bin/bash

# Simple PHP Application - Start Script
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

# Function to check if application is already running
check_if_running() {
    if [ -f "$PID_FILE" ]; then
        local pid=$(cat "$PID_FILE")
        if ps -p "$pid" > /dev/null 2>&1; then
            echo -e "  ${YELLOW}⚠️  Application is already running (PID: $pid)${NC}"
            echo -e "  ${BLUE}Use: make stop - to stop the application first${NC}"
            exit 1
        else
            # Remove stale PID file
            rm -f "$PID_FILE"
        fi
    fi
}

# Function to check for IP changes and update configuration
check_ip_changes() {
    echo -e "\n${PURPLE}🔍 Checking Network Status${NC}"
    
    if [ ! -f "scripts/network-manager.sh" ]; then
        echo -e "  ${YELLOW}⚠️  Network manager not found, skipping IP check${NC}"
        return 0
    fi
    
    local ip_status=$(./scripts/network-manager.sh check-change)
    local current_ip=$(./scripts/network-manager.sh current-ip)
    
    case $ip_status in
        "changed")
            echo -e "  ${YELLOW}⚠️  IP address has changed!${NC}"
            echo -e "  ${BLUE}Current IP: $current_ip${NC}"
            echo -e "  ${BLUE}Updating configuration...${NC}"
            ./scripts/network-manager.sh update
            echo -e "  ${GREEN}✅ Configuration updated${NC}"
            ;;
        "same")
            echo -e "  ${GREEN}✅ IP address is stable ($current_ip)${NC}"
            ;;
        "new")
            echo -e "  ${BLUE}ℹ️  First run, saving IP configuration${NC}"
            ./scripts/network-manager.sh save "detected" "$current_ip"
            ;;
    esac
}

# Function to load configuration
load_configuration() {
    echo -e "\n${PURPLE}📋 Loading Configuration${NC}"
    
    if [ ! -f "$APP_CONFIG_FILE" ]; then
        echo -e "  ${RED}❌ Configuration file not found${NC}"
        echo -e "  ${YELLOW}Run: make compile - to configure the application${NC}"
        exit 1
    fi
    
    # Source configuration
    source "$APP_CONFIG_FILE"
    
    # Validate required variables
    if [ -z "$APP_PORT" ] || [ -z "$PHP_VERSION" ] || [ -z "$DEPLOYMENT_TYPE" ]; then
        echo -e "  ${RED}❌ Invalid configuration file${NC}"
        echo -e "  ${YELLOW}Run: make compile - to reconfigure the application${NC}"
        exit 1
    fi
    
    echo -e "  ${GREEN}✅ Configuration loaded${NC}"
    echo -e "    ${BLUE}PHP Version: $PHP_VERSION${NC}"
    echo -e "    ${BLUE}Port: $APP_PORT${NC}"
    echo -e "    ${BLUE}Deployment: $DEPLOYMENT_DESC${NC}"
    echo -e "    ${BLUE}Network: ${NETWORK_DISPLAY:-$NETWORK_INTERFACE}${NC}"
}

# Function to validate deployment readiness
validate_deployment() {
    echo -e "\n${PURPLE}🔍 Validating Deployment${NC}"
    
    # Check if webserver manager is available
    if [ -f "scripts/webserver-manager.sh" ]; then
        if ./scripts/webserver-manager.sh validate "$PHP_VERSION" "$DEPLOYMENT_TYPE"; then
            echo -e "  ${GREEN}✅ Deployment validation passed${NC}"
        else
            echo -e "  ${RED}❌ Deployment validation failed${NC}"
            echo -e "  ${YELLOW}Run: make compile - to reconfigure${NC}"
            exit 1
        fi
    else
        echo -e "  ${YELLOW}⚠️  Webserver manager not found, skipping validation${NC}"
    fi
}

# Function to start the application
start_application() {
    echo -e "\n${PURPLE}🚀 Starting $APP_NAME Application${NC}"
    
    # Check if port is available (only for PHP-CLI deployments)
    if [ "$DEPLOYMENT_TYPE" = "php-cli" ]; then
        if command -v netstat >/dev/null 2>&1; then
            if netstat -tuln 2>/dev/null | grep -q ":$APP_PORT "; then
                echo -e "  ${RED}❌ Port $APP_PORT is already in use${NC}"
                exit 1
            fi
        elif command -v ss >/dev/null 2>&1; then
            if ss -tuln 2>/dev/null | grep -q ":$APP_PORT "; then
                echo -e "  ${RED}❌ Port $APP_PORT is already in use${NC}"
                exit 1
            fi
        fi
    fi
    
    # Get deployment command
    local start_cmd=""
    case $DEPLOYMENT_TYPE in
        "php-cli")
            if [ ! -f "public/index.php" ]; then
                echo -e "  ${RED}❌ public/index.php not found${NC}"
                exit 1
            fi
            start_cmd="php${PHP_VERSION} -S ${NETWORK_INTERFACE}:${APP_PORT} -t public"
            ;;
        "apache-fpm")
            echo -e "  ${BLUE}Deploying with Apache PHP-FPM...${NC}"
            ./scripts/deploy-apache-fpm.sh
            return 0
            ;;
        "apache-mod-php")
            echo -e "  ${BLUE}Deploying with Apache mod_php...${NC}"
            ./scripts/deploy-apache-mod-php.sh
            return 0
            ;;
        "nginx-fpm")
            echo -e "  ${BLUE}Deploying with Nginx PHP-FPM...${NC}"
            ./scripts/deploy-nginx-fpm.sh
            return 0
            ;;
        *)
            echo -e "  ${RED}❌ Unknown deployment type: $DEPLOYMENT_TYPE${NC}"
            exit 1
            ;;
    esac
    
    # Start the application
    echo -e "  ${BLUE}Starting command: $start_cmd${NC}"
    echo -e "  ${GREEN}✅ Application starting...${NC}"
    
    # Start in background and save PID
    nohup $start_cmd > logs/app.log 2>&1 &
    local app_pid=$!
    echo $app_pid > "$PID_FILE"
    
    # Wait a moment and check if it started successfully
    sleep 2
    if ps -p "$app_pid" > /dev/null 2>&1; then
        echo -e "  ${GREEN}✅ Application started successfully (PID: $app_pid)${NC}"
        
        # Show access information
        local current_ip=$(./scripts/network-manager.sh current-ip 2>/dev/null || echo "localhost")
        echo -e "\n${PURPLE}🌐 Access Information${NC}"
        echo -e "================================"
        
        if [ "$NETWORK_INTERFACE" = "127.0.0.1" ]; then
            echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
        elif [ "$NETWORK_INTERFACE" = "0.0.0.0" ]; then
            echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
            echo -e "  ${BLUE}Network access: http://$current_ip:$APP_PORT${NC}"
            echo -e "  ${GREEN}✅ Accessible from any IP (dynamic IP safe)${NC}"
        else
            echo -e "  ${BLUE}Access: http://$NETWORK_INTERFACE:$APP_PORT${NC}"
        fi
        
        echo -e "\n${YELLOW}Management commands:${NC}"
        echo -e "  ${BLUE}make stop${NC}    - Stop the application"
        echo -e "  ${BLUE}make status${NC}  - Check application status"
        echo -e "  ${BLUE}tail -f logs/app.log${NC} - View application logs"
        
    else
        echo -e "  ${RED}❌ Application failed to start${NC}"
        echo -e "  ${YELLOW}Check logs: tail logs/app.log${NC}"
        rm -f "$PID_FILE"
        exit 1
    fi
}

# Main execution
main() {
    echo -e "${BLUE}🚀 $APP_NAME - Application Startup${NC}"
    echo -e "====================================="
    
    # Ensure logs directory exists
    mkdir -p logs
    
    check_if_running
    check_ip_changes
    load_configuration
    validate_deployment
    start_application
}

# Execute main function
main "$@"
