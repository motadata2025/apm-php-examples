#!/bin/bash

# Cleanup Script - Complete Application Removal
# APM PHP Examples - Simple PHP Application

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
APP_NAME="Simple PHP"
VHOST_NAME="simple-php"

# Function to load configuration
load_configuration() {
    if [ -f "$APP_CONFIG_FILE" ]; then
        source "$APP_CONFIG_FILE"
        return 0
    else
        return 1
    fi
}

# Function to stop any running processes
stop_all_processes() {
    echo -e "\n${PURPLE}🛑 Stopping All Application Processes${NC}"
    
    # Stop PHP built-in server if running
    local pid_file="$CONFIG_DIR/app.pid"
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if ps -p "$pid" > /dev/null 2>&1; then
            echo -e "  ${BLUE}Stopping PHP server (PID: $pid)...${NC}"
            kill "$pid" 2>/dev/null || true
            sleep 2
            if ps -p "$pid" > /dev/null 2>&1; then
                kill -9 "$pid" 2>/dev/null || true
            fi
            echo -e "  ${GREEN}✅ PHP server stopped${NC}"
        fi
        rm -f "$pid_file"
    fi
}

# Function to cleanup Apache configuration
cleanup_apache() {
    echo -e "\n${PURPLE}🌐 Cleaning Up Apache Configuration${NC}"
    
    local app_port=""
    if load_configuration; then
        app_port="$APP_PORT"
    fi
    
    # Disable and remove all simple-php sites
    for site in /etc/apache2/sites-available/simple-php*.conf; do
        if [ -f "$site" ]; then
            local site_name=$(basename "$site" .conf)
            echo -e "  ${BLUE}Disabling site: $site_name${NC}"
            sudo a2dissite "$site_name.conf" 2>/dev/null || true
        fi
    done
    
    # Remove virtual host configurations
    echo -e "  ${BLUE}Removing virtual host configurations...${NC}"
    sudo rm -f /etc/apache2/sites-available/simple-php*.conf
    
    # Remove ports from Apache configuration
    if [ -n "$app_port" ]; then
        echo -e "  ${BLUE}Removing port $app_port from Apache configuration...${NC}"
        sudo sed -i "/Listen $app_port/d" /etc/apache2/ports.conf
    fi
    
    # Remove any other ports that might have been added
    echo -e "  ${BLUE}Cleaning up any remaining application ports...${NC}"
    sudo sed -i '/Listen 800[0-9]/d' /etc/apache2/ports.conf
    
    # Reload Apache
    if sudo systemctl reload apache2 2>/dev/null; then
        echo -e "  ${GREEN}✅ Apache configuration cleaned and reloaded${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Apache reload failed (might not be running)${NC}"
    fi
}

# Function to cleanup application copies
cleanup_application_copies() {
    echo -e "\n${PURPLE}📁 Cleaning Up Application Copies${NC}"
    
    # Remove from Apache directory
    local apache_dirs=("/var/www/simple-php" "/var/www/simple-php-*")
    for dir in "${apache_dirs[@]}"; do
        if [ -d "$dir" ] 2>/dev/null; then
            echo -e "  ${BLUE}Removing: $dir${NC}"
            sudo rm -rf "$dir"
            echo -e "  ${GREEN}✅ Removed: $dir${NC}"
        fi
    done
    
    # Check for any other copies
    if sudo find /var/www -name "*simple-php*" -type d 2>/dev/null | grep -q .; then
        echo -e "  ${BLUE}Removing additional application copies...${NC}"
        sudo find /var/www -name "*simple-php*" -type d -exec rm -rf {} + 2>/dev/null || true
        echo -e "  ${GREEN}✅ Additional copies removed${NC}"
    else
        echo -e "  ${GREEN}✅ No application copies found in /var/www${NC}"
    fi
}

# Function to cleanup configuration files
cleanup_configuration() {
    echo -e "\n${PURPLE}⚙️ Cleaning Up Configuration Files${NC}"
    
    # Remove application configuration
    if [ -f "$APP_CONFIG_FILE" ]; then
        echo -e "  ${BLUE}Removing application configuration...${NC}"
        rm -f "$APP_CONFIG_FILE"
        echo -e "  ${GREEN}✅ Application configuration removed${NC}"
    fi
    
    # Remove network state
    if [ -f "$CONFIG_DIR/network.state" ]; then
        echo -e "  ${BLUE}Removing network state...${NC}"
        rm -f "$CONFIG_DIR/network.state"
        echo -e "  ${GREEN}✅ Network state removed${NC}"
    fi
    
    # Remove PHP version state
    if [ -f "$CONFIG_DIR/php-version.state" ]; then
        echo -e "  ${BLUE}Removing PHP version state...${NC}"
        rm -f "$CONFIG_DIR/php-version.state"
        echo -e "  ${GREEN}✅ PHP version state removed${NC}"
    fi
    
    # Clean up config directory if empty
    if [ -d "$CONFIG_DIR" ] && [ -z "$(ls -A $CONFIG_DIR)" ]; then
        rmdir "$CONFIG_DIR"
        echo -e "  ${GREEN}✅ Empty config directory removed${NC}"
    fi
}

# Function to cleanup logs
cleanup_logs() {
    echo -e "\n${PURPLE}📋 Cleaning Up Logs${NC}"
    
    # Remove application logs
    if [ -d "logs" ]; then
        echo -e "  ${BLUE}Removing application logs...${NC}"
        rm -rf logs/*
        echo -e "  ${GREEN}✅ Application logs cleared${NC}"
    fi
    
    # Clean Apache logs (optional - ask user)
    if [ -f "/var/log/apache2/simple-php_error.log" ] || [ -f "/var/log/apache2/simple-php_access.log" ]; then
        read -t 10 -p "Remove Apache logs for this application? (y/n): " remove_apache_logs || remove_apache_logs="n"
        if [[ "$remove_apache_logs" =~ ^[Yy]$ ]]; then
            echo -e "  ${BLUE}Removing Apache logs...${NC}"
            sudo rm -f /var/log/apache2/simple-php*.log
            echo -e "  ${GREEN}✅ Apache logs removed${NC}"
        else
            echo -e "  ${YELLOW}⚠️  Apache logs preserved${NC}"
        fi
    fi
}

# Function to cleanup Docker containers
cleanup_docker() {
    echo -e "\n${PURPLE}🐳 Docker Cleanup${NC}"

    # Check if application has Docker containers
    if [ -f "docker-compose.yml" ]; then
        echo -e "  ${BLUE}Application Docker containers detected${NC}"

        # Stop and remove containers using docker-helper.sh
        echo -e "  ${BLUE}Stopping and removing application containers...${NC}"
        if [ -f "scripts/docker-helper.sh" ]; then
            ./scripts/docker-helper.sh app-down 2>/dev/null || true
        else
            # Fallback to direct docker compose commands
            if command -v "docker" >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
                docker compose down 2>/dev/null || true
            elif command -v "docker-compose" >/dev/null 2>&1; then
                docker-compose down 2>/dev/null || true
            fi
        fi
        echo -e "  ${GREEN}✅ Application containers stopped${NC}"

        read -t 10 -p "Remove Docker volumes (will delete all database data)? (y/n): " remove_volumes || remove_volumes="y"
        if [[ "$remove_volumes" =~ ^[Yy]$ ]]; then
            echo -e "  ${BLUE}Removing Docker volumes...${NC}"
            if [ -f "scripts/docker-helper.sh" ]; then
                ./scripts/docker-helper.sh down docker-compose.yml 2>/dev/null || true
                # Use direct command for volume removal since docker-helper doesn't support -v flag
                if command -v "docker" >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
                    docker compose down -v 2>/dev/null || true
                elif command -v "docker-compose" >/dev/null 2>&1; then
                    docker-compose down -v 2>/dev/null || true
                fi
            else
                # Fallback to direct docker compose commands
                if command -v "docker" >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
                    docker compose down -v 2>/dev/null || true
                elif command -v "docker-compose" >/dev/null 2>&1; then
                    docker-compose down -v 2>/dev/null || true
                fi
            fi
            echo -e "  ${GREEN}✅ Docker volumes removed${NC}"
        else
            echo -e "  ${YELLOW}⚠️  Docker volumes preserved${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  No docker-compose.yml found - no containers to clean${NC}"
    fi
}

# Function to show cleanup summary
show_cleanup_summary() {
    echo -e "\n${PURPLE}📊 Cleanup Summary${NC}"
    echo -e "==================="
    
    echo -e "\n${GREEN}✅ Cleanup completed successfully!${NC}"
    echo -e "\n${BLUE}What was cleaned:${NC}"
    echo -e "  ${GREEN}✅ Application processes stopped${NC}"
    echo -e "  ${GREEN}✅ Apache configuration removed${NC}"
    echo -e "  ${GREEN}✅ Application copies removed${NC}"
    echo -e "  ${GREEN}✅ Configuration files removed${NC}"
    echo -e "  ${GREEN}✅ Application logs cleared${NC}"
    
    echo -e "\n${BLUE}To redeploy the application:${NC}"
    echo -e "  ${YELLOW}make setup${NC}    - Check system requirements"
    echo -e "  ${YELLOW}make compile${NC}  - Configure the application"
    echo -e "  ${YELLOW}make start${NC}    - Start the application"
}

# Main execution
main() {
    echo -e "${RED}🗑️  $APP_NAME - Complete Cleanup${NC}"
    echo -e "====================================="
    
    echo -e "\n${YELLOW}⚠️  WARNING: This will completely remove the application and its configuration!${NC}"
    read -t 15 -p "Are you sure you want to proceed? (y/n): " confirm || confirm="n"
    
    if [[ "$confirm" =~ ^[Yy]$ ]]; then
        stop_all_processes
        cleanup_apache
        cleanup_application_copies
        cleanup_configuration
        cleanup_logs
        cleanup_docker
        show_cleanup_summary
    else
        echo -e "\n${YELLOW}⚠️  Cleanup cancelled${NC}"
        echo -e "\n${BLUE}To stop the application without full cleanup:${NC}"
        echo -e "  ${YELLOW}make stop${NC}     - Stop the application"
        echo -e "  ${YELLOW}make disable${NC}  - Disable the application"
    fi
}

# Execute main function
main "$@"
