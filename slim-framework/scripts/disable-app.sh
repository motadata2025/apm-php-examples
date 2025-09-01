#!/bin/bash

# Disable Application Script
# APM PHP Examples - Slim Framework Application

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

# Application info
APP_NAME="Slim Framework"
VHOST_NAME="slim-framework"

# Function to load configuration
load_configuration() {
    if [ ! -f "$APP_CONFIG_FILE" ]; then
        echo -e "${YELLOW}⚠️  No configuration found${NC}"
        return 1
    fi
    
    source "$APP_CONFIG_FILE"
    return 0
}

# Function to disable Apache site
disable_apache_site() {
    echo -e "\n${PURPLE}🌐 Disabling Apache Site${NC}"
    
    if sudo a2dissite "${VHOST_NAME}.conf" 2>/dev/null; then
        echo -e "  ${GREEN}✅ Site ${VHOST_NAME} disabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Site ${VHOST_NAME} was not enabled${NC}"
    fi
    
    # Reload Apache
    if sudo systemctl reload apache2 2>/dev/null; then
        echo -e "  ${GREEN}✅ Apache reloaded${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Failed to reload Apache${NC}"
    fi
}

# Function to stop PHP built-in server
stop_php_server() {
    echo -e "\n${PURPLE}🛑 Stopping PHP Built-in Server${NC}"
    
    local pid_file="$CONFIG_DIR/app.pid"
    
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if ps -p "$pid" > /dev/null 2>&1; then
            echo -e "  ${BLUE}Stopping PHP server (PID: $pid)...${NC}"
            kill "$pid" 2>/dev/null || true
            
            # Wait for graceful shutdown
            local count=0
            while ps -p "$pid" > /dev/null 2>&1 && [ $count -lt 5 ]; do
                sleep 1
                count=$((count + 1))
            done
            
            # Force kill if still running
            if ps -p "$pid" > /dev/null 2>&1; then
                kill -9 "$pid" 2>/dev/null || true
            fi
            
            echo -e "  ${GREEN}✅ PHP server stopped${NC}"
        else
            echo -e "  ${YELLOW}⚠️  PHP server was not running${NC}"
        fi
        
        rm -f "$pid_file"
    else
        echo -e "  ${YELLOW}⚠️  No PHP server PID file found${NC}"
    fi
}

# Function to disable Nginx site
disable_nginx_site() {
    echo -e "\n${PURPLE}🌐 Disabling Nginx Site${NC}"

    # Disable the site (but keep files and configuration)
    if sudo rm -f "/etc/nginx/sites-enabled/${VHOST_NAME}" 2>/dev/null; then
        echo -e "  ${GREEN}✅ Site ${VHOST_NAME} disabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Site ${VHOST_NAME} was not enabled${NC}"
    fi

    # Test and reload Nginx
    if sudo nginx -t 2>/dev/null; then
        if sudo systemctl reload nginx 2>/dev/null; then
            echo -e "  ${GREEN}✅ Nginx reloaded${NC}"
        else
            echo -e "  ${YELLOW}⚠️  Failed to reload Nginx${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  Nginx configuration test failed${NC}"
    fi
}

# Main execution
main() {
    echo -e "${BLUE}⏸️  Disabling Slim Framework Application${NC}"
    echo -e "====================================="
    
    if load_configuration; then
        case "$DEPLOYMENT_TYPE" in
            "apache-fpm"|"apache-mod-php")
                disable_apache_site
                ;;
            "nginx-fpm")
                disable_nginx_site
                ;;
            "php-cli"|*)
                stop_php_server
                ;;
        esac
    else
        # Try to stop any running processes
        stop_php_server
        disable_apache_site
    fi
    
    echo -e "\n${GREEN}✅ Application disabled${NC}"
    echo -e "\n${BLUE}To re-enable:${NC}"
    echo -e "  ${YELLOW}make enable${NC}  - Enable the application"
    echo -e "  ${YELLOW}make start${NC}   - Start the application"
}

# Execute main function
main "$@"
