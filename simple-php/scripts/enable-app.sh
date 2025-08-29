#!/bin/bash

# Enable Application Script
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
APP_NAME="simple-php"
VHOST_NAME="simple-php"

# Function to load configuration
load_configuration() {
    if [ ! -f "$APP_CONFIG_FILE" ]; then
        echo -e "${RED}❌ Configuration file not found${NC}"
        echo -e "${YELLOW}Run: make compile - to configure the application${NC}"
        exit 1
    fi
    
    source "$APP_CONFIG_FILE"
}

# Function to enable Apache site
enable_apache_site() {
    echo -e "\n${PURPLE}🌐 Enabling Apache Site${NC}"
    
    # Check if virtual host exists
    if [ ! -f "/etc/apache2/sites-available/${VHOST_NAME}.conf" ]; then
        echo -e "  ${YELLOW}⚠️  Virtual host not found, creating...${NC}"
        ./scripts/deploy-apache-fpm.sh
        return 0
    fi
    
    if sudo a2ensite "${VHOST_NAME}.conf" 2>/dev/null; then
        echo -e "  ${GREEN}✅ Site ${VHOST_NAME} enabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Site ${VHOST_NAME} already enabled${NC}"
    fi
    
    # Test configuration
    if sudo apache2ctl configtest 2>/dev/null; then
        echo -e "  ${GREEN}✅ Apache configuration is valid${NC}"
    else
        echo -e "  ${RED}❌ Apache configuration has errors${NC}"
        return 1
    fi
    
    # Reload Apache
    if sudo systemctl reload apache2; then
        echo -e "  ${GREEN}✅ Apache reloaded${NC}"
    else
        echo -e "  ${RED}❌ Failed to reload Apache${NC}"
        return 1
    fi
}

# Function to show access information
show_access_info() {
    echo -e "\n${PURPLE}🌐 Application Enabled${NC}"
    echo -e "======================"
    
    local current_ip=$(./scripts/network-manager.sh current-ip 2>/dev/null || echo "localhost")
    
    echo -e "\n${GREEN}✅ Application enabled successfully!${NC}"
    echo -e "\n${BLUE}Access Information:${NC}"
    
    if [ "$NETWORK_INTERFACE" = "127.0.0.1" ]; then
        echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
    elif [ "$NETWORK_INTERFACE" = "0.0.0.0" ]; then
        echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
        echo -e "  ${BLUE}Network access: http://$current_ip:$APP_PORT${NC}"
    else
        echo -e "  ${BLUE}Access: http://$NETWORK_INTERFACE:$APP_PORT${NC}"
    fi
    
    echo -e "\n${BLUE}Configuration:${NC}"
    echo -e "  ${GREEN}Deployment: $DEPLOYMENT_DESC${NC}"
    echo -e "  ${GREEN}PHP Version: $PHP_VERSION${NC}"
    
    echo -e "\n${BLUE}Management Commands:${NC}"
    echo -e "  ${YELLOW}make disable${NC}  - Disable the application"
    echo -e "  ${YELLOW}make stop${NC}     - Stop the application"
    echo -e "  ${YELLOW}make status${NC}   - Check application status"
}

# Main execution
main() {
    echo -e "${BLUE}▶️  Enabling Simple PHP Application${NC}"
    echo -e "===================================="
    
    load_configuration
    
    case "$DEPLOYMENT_TYPE" in
        "apache-fpm"|"apache-mod-php")
            enable_apache_site
            ;;
        "nginx-fpm")
            echo -e "${YELLOW}⚠️  Nginx deployment enable not implemented yet${NC}"
            echo -e "${BLUE}Use: make start - to start with PHP built-in server${NC}"
            ;;
        "php-cli"|*)
            echo -e "${BLUE}PHP built-in server deployment${NC}"
            echo -e "${BLUE}Use: make start - to start the server${NC}"
            ;;
    esac
    
    show_access_info
}

# Execute main function
main "$@"
