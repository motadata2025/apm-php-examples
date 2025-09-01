#!/bin/bash

# Slim Framework Application - Stop Script
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
PID_FILE="$CONFIG_DIR/app.pid"
APP_CONFIG_FILE="$CONFIG_DIR/app.env"

# Application info
APP_NAME="Slim Framework"
VHOST_NAME="slim-framework"

# Function to load configuration
load_configuration() {
    if [ -f "$APP_CONFIG_FILE" ]; then
        source "$APP_CONFIG_FILE"
        return 0
    else
        return 1
    fi
}

# Function to stop PHP-CLI application
stop_php_cli() {
    echo -e "\n${PURPLE}🛑 Stopping PHP Built-in Server${NC}"

    if [ ! -f "$PID_FILE" ]; then
        echo -e "  ${YELLOW}⚠️  Application is not running (no PID file)${NC}"
        return 0
    fi

    local pid=$(cat "$PID_FILE")

    if ps -p "$pid" > /dev/null 2>&1; then
        echo -e "  ${BLUE}Stopping application (PID: $pid)...${NC}"
        kill "$pid"

        # Wait for graceful shutdown
        local count=0
        while ps -p "$pid" > /dev/null 2>&1 && [ $count -lt 10 ]; do
            sleep 1
            count=$((count + 1))
        done

        # Force kill if still running
        if ps -p "$pid" > /dev/null 2>&1; then
            echo -e "  ${YELLOW}⚠️  Forcing application shutdown...${NC}"
            kill -9 "$pid" 2>/dev/null || true
        fi

        echo -e "  ${GREEN}✅ Application stopped${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Application was not running${NC}"
    fi

    # Remove PID file
    rm -f "$PID_FILE"
}

# Function to stop Apache deployment
stop_apache_deployment() {
    echo -e "\n${PURPLE}🛑 Stopping Apache Deployment${NC}"

    # Load configuration to get port
    local app_port=""
    if [ -f "$APP_CONFIG_FILE" ]; then
        source "$APP_CONFIG_FILE"
        app_port="$APP_PORT"
    fi

    # Disable Apache site
    local disable_output
    disable_output=$(sudo a2dissite "${VHOST_NAME}.conf" 2>&1)
    local disable_status=$?

    if [ $disable_status -eq 0 ] || echo "$disable_output" | grep -q "disabled"; then
        echo -e "  ${GREEN}✅ Apache site disabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Site ${VHOST_NAME} was not enabled${NC}"
    fi

    # Remove virtual host configuration
    if [ -f "/etc/apache2/sites-available/${VHOST_NAME}.conf" ]; then
        echo -e "  ${BLUE}Removing virtual host configuration...${NC}"
        sudo rm -f "/etc/apache2/sites-available/${VHOST_NAME}.conf"
        echo -e "  ${GREEN}✅ Virtual host configuration removed${NC}"
    fi

    # Remove port from Apache configuration
    if [ -n "$app_port" ]; then
        echo -e "  ${BLUE}Removing port $app_port from Apache configuration...${NC}"
        sudo sed -i "/Listen $app_port/d" /etc/apache2/ports.conf
        echo -e "  ${GREEN}✅ Port $app_port removed from Apache${NC}"
    fi

    # Remove copied application from Apache directory
    local apache_dir="/var/www/${VHOST_NAME}"
    if [ -d "$apache_dir" ]; then
        echo -e "  ${BLUE}Removing application copy from Apache directory...${NC}"
        sudo rm -rf "$apache_dir"
        echo -e "  ${GREEN}✅ Application copy removed${NC}"
    else
        echo -e "  ${YELLOW}⚠️  No application copy found in Apache directory${NC}"
    fi

    # Reload Apache
    if sudo systemctl reload apache2 2>/dev/null; then
        echo -e "  ${GREEN}✅ Apache reloaded${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Failed to reload Apache${NC}"
    fi
}

# Function to stop Nginx deployment
stop_nginx_deployment() {
    echo -e "\n${PURPLE}🛑 Stopping Nginx Deployment${NC}"

    # Disable the site
    if [ -L "/etc/nginx/sites-enabled/${VHOST_NAME}" ]; then
        echo -e "  ${BLUE}Disabling Nginx site...${NC}"
        sudo rm -f "/etc/nginx/sites-enabled/${VHOST_NAME}"
        echo -e "  ${GREEN}✅ Nginx site disabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Nginx site already disabled${NC}"
    fi

    # Remove virtual host configuration
    if [ -f "/etc/nginx/sites-available/${VHOST_NAME}" ]; then
        echo -e "  ${BLUE}Removing virtual host configuration...${NC}"
        sudo rm -f "/etc/nginx/sites-available/${VHOST_NAME}"
        echo -e "  ${GREEN}✅ Virtual host configuration removed${NC}"
    fi

    # Remove application copy from Nginx directory
    local nginx_dir="/var/www/${VHOST_NAME}"
    if [ -d "$nginx_dir" ]; then
        echo -e "  ${BLUE}Removing application copy from Nginx directory...${NC}"
        sudo rm -rf "$nginx_dir"
        echo -e "  ${GREEN}✅ Application copy removed${NC}"
    else
        echo -e "  ${YELLOW}⚠️  No application copy found in Nginx directory${NC}"
    fi

    # Reload Nginx
    if systemctl is-active nginx >/dev/null 2>&1; then
        if sudo systemctl reload nginx 2>/dev/null; then
            echo -e "  ${GREEN}✅ Nginx reloaded${NC}"
        else
            echo -e "  ${YELLOW}⚠️  Failed to reload Nginx${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  Nginx is not running${NC}"
    fi
}

# Function to stop the application
stop_application() {
    echo -e "\n${PURPLE}🛑 Stopping $APP_NAME Application${NC}"

    if load_configuration; then
        case "$DEPLOYMENT_TYPE" in
            "apache-fpm"|"apache-mod-php")
                stop_apache_deployment
                ;;
            "nginx-fpm")
                stop_nginx_deployment
                ;;
            "php-cli"|*)
                stop_php_cli
                ;;
        esac
    else
        # No configuration found, try to stop any running processes
        echo -e "  ${YELLOW}⚠️  No configuration found, attempting to stop any running processes${NC}"
        stop_php_cli
        stop_apache_deployment
    fi
}

# Main execution
main() {
    echo -e "${BLUE}🛑 $APP_NAME - Application Shutdown${NC}"
    echo -e "======================================"

    stop_application

    echo -e "\n${GREEN}✅ Shutdown complete${NC}"
}

# Execute main function
main "$@"
