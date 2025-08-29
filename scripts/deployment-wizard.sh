#!/bin/bash

# APM PHP Examples - Interactive Deployment Wizard
# This script guides users through the deployment process with validation

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Configuration
CONFIG_FILE="config/deployment.env"
WIZARD_LOG="logs/wizard.log"

# Create logs directory
mkdir -p logs config

# Initialize wizard log
echo "=== APM PHP Examples - Deployment Wizard ===" > "$WIZARD_LOG"
echo "Timestamp: $(date)" >> "$WIZARD_LOG"
echo "" >> "$WIZARD_LOG"

echo -e "${BLUE}${BOLD}🧙‍♂️ APM PHP Examples - Interactive Deployment Wizard${NC}"
echo "============================================================="
echo -e "${CYAN}Welcome! This wizard will guide you through setting up your local PHP development environment.${NC}"
echo ""

# Function to log wizard actions
log_wizard() {
    local message="$1"
    echo "$(date): $message" >> "$WIZARD_LOG"
}

# Function to get user input with validation
get_input() {
    local prompt="$1"
    local default="$2"
    local var_name="$3"
    local validation_func="$4"
    
    while true; do
        if [ -n "$default" ]; then
            read -p "$prompt [$default]: " input
            input="${input:-$default}"
        else
            read -p "$prompt: " input
        fi
        
        if [ -n "$validation_func" ] && ! $validation_func "$input"; then
            echo -e "${RED}Invalid input. Please try again.${NC}"
            continue
        fi
        
        eval "$var_name='$input'"
        log_wizard "User input: $var_name = $input"
        break
    done
}

# Function to validate PHP version
validate_php_version() {
    local version="$1"
    if [[ "$version" =~ ^8\.[1-4]$ ]]; then
        return 0
    else
        echo -e "${RED}Please enter a valid PHP version (8.1, 8.2, 8.3, or 8.4)${NC}"
        return 1
    fi
}

# Function to validate deployment type
validate_deployment_type() {
    local type="$1"
    case "$type" in
        1|apache-mod-php|local-apache-mod-php) return 0 ;;
        2|apache-fpm|local-apache-fpm) return 0 ;;
        3|nginx-fpm|local-nginx-fpm) return 0 ;;
        4|php-cli|local-php-cli) return 0 ;;
        *) 
            echo -e "${RED}Please enter 1-4 or a valid deployment type${NC}"
            return 1 
            ;;
    esac
}

# Function to validate IP address
validate_ip() {
    local ip="$1"
    if [[ "$ip" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        return 0
    else
        echo -e "${RED}Please enter a valid IP address${NC}"
        return 1
    fi
}

# Function to display system information
display_system_info() {
    echo -e "\n${YELLOW}🖥️  System Information${NC}"
    echo "======================"
    echo "OS: $(uname -s) $(uname -r)"
    echo "Architecture: $(uname -m)"
    
    if command -v php >/dev/null 2>&1; then
        echo "Current PHP: $(php -r 'echo PHP_VERSION;')"
    else
        echo "PHP: Not installed"
    fi
    
    if command -v apache2 >/dev/null 2>&1; then
        echo "Apache: $(apache2 -v | head -1 | cut -d' ' -f3)"
    elif command -v httpd >/dev/null 2>&1; then
        echo "Apache: $(httpd -v | head -1 | cut -d' ' -f3)"
    else
        echo "Apache: Not installed"
    fi
    
    if command -v nginx >/dev/null 2>&1; then
        echo "Nginx: $(nginx -v 2>&1 | cut -d' ' -f3)"
    else
        echo "Nginx: Not installed"
    fi
    
    if command -v docker >/dev/null 2>&1; then
        echo "Docker: $(docker --version | cut -d' ' -f3 | tr -d ',')"
    else
        echo "Docker: Not installed"
    fi
    
    if command -v composer >/dev/null 2>&1; then
        echo "Composer: $(composer --version --no-ansi | cut -d' ' -f3)"
    else
        echo "Composer: Not installed"
    fi
}

# Function to recommend deployment type
recommend_deployment_type() {
    echo -e "\n${YELLOW}💡 Deployment Type Recommendations${NC}"
    echo "==================================="
    
    local has_apache=false
    local has_nginx=false
    local has_php_fpm=false
    
    if command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1; then
        has_apache=true
    fi
    
    if command -v nginx >/dev/null 2>&1; then
        has_nginx=true
    fi
    
    if command -v php-fpm8.4 >/dev/null 2>&1 || command -v php8.4-fpm >/dev/null 2>&1; then
        has_php_fpm=true
    fi
    
    echo -e "${BLUE}Based on your system:${NC}"
    
    if $has_apache && $has_php_fpm; then
        echo -e "${GREEN}✅ Apache + PHP-FPM (Recommended for production)${NC}"
    elif $has_apache; then
        echo -e "${GREEN}✅ Apache + mod_php (Good for development)${NC}"
    fi
    
    if $has_nginx && $has_php_fpm; then
        echo -e "${GREEN}✅ Nginx + PHP-FPM (Recommended for high performance)${NC}"
    fi
    
    echo -e "${GREEN}✅ PHP CLI (Always available, great for development)${NC}"
    
    echo ""
    echo -e "${CYAN}Recommendations:${NC}"
    echo "• For development: PHP CLI (easiest setup)"
    echo "• For production-like testing: Apache/Nginx + PHP-FPM"
    echo "• For simple setups: Apache + mod_php"
}

# Function to configure network settings
configure_network() {
    echo -e "\n${YELLOW}🌐 Network Configuration${NC}"
    echo "========================="
    
    echo -e "${CYAN}Detecting your network interfaces...${NC}"
    ./scripts/detect-network-ip.sh
    
    # Load the detected IP
    if [ -f "$CONFIG_FILE" ]; then
        source "$CONFIG_FILE"
        echo -e "${GREEN}✅ Network IP configured: ${NETWORK_INTERFACE}${NC}"
    else
        echo -e "${RED}❌ Failed to configure network IP${NC}"
        exit 1
    fi
}

# Function to configure PHP settings
configure_php() {
    echo -e "\n${YELLOW}🐘 PHP Configuration${NC}"
    echo "===================="
    
    echo -e "${CYAN}Available PHP versions: 8.1, 8.2, 8.3, 8.4${NC}"
    echo -e "${CYAN}Current system PHP: $(php -r 'echo PHP_VERSION;' 2>/dev/null || echo 'Not installed')${NC}"
    echo ""
    
    get_input "Select PHP version" "8.4" "PHP_VERSION" "validate_php_version"
    
    # Update configuration
    if grep -q "^PHP_VERSION=" "$CONFIG_FILE" 2>/dev/null; then
        sed -i "s/^PHP_VERSION=.*/PHP_VERSION=$PHP_VERSION/" "$CONFIG_FILE"
    else
        echo "PHP_VERSION=$PHP_VERSION" >> "$CONFIG_FILE"
    fi
    
    echo -e "${GREEN}✅ PHP version set to: $PHP_VERSION${NC}"
}

# Function to configure deployment type
configure_deployment() {
    echo -e "\n${YELLOW}🚀 Deployment Configuration${NC}"
    echo "============================"
    
    recommend_deployment_type
    
    echo ""
    echo -e "${CYAN}Available deployment types:${NC}"
    echo "1. Apache + mod_php (Simple, good for development)"
    echo "2. Apache + PHP-FPM (Production-ready, better performance)"
    echo "3. Nginx + PHP-FPM (High performance, modern)"
    echo "4. PHP CLI (Built-in server, easiest setup)"
    echo ""
    
    get_input "Select deployment type (1-4)" "4" "DEPLOYMENT_TYPE_INPUT" "validate_deployment_type"
    
    # Convert input to deployment type
    case "$DEPLOYMENT_TYPE_INPUT" in
        1|apache-mod-php) DEPLOYMENT_TYPE="local-apache-mod-php" ;;
        2|apache-fpm) DEPLOYMENT_TYPE="local-apache-fpm" ;;
        3|nginx-fpm) DEPLOYMENT_TYPE="local-nginx-fpm" ;;
        4|php-cli) DEPLOYMENT_TYPE="local-php-cli" ;;
        *) DEPLOYMENT_TYPE="$DEPLOYMENT_TYPE_INPUT" ;;
    esac
    
    # Update configuration
    if grep -q "^DEPLOYMENT_TYPE=" "$CONFIG_FILE" 2>/dev/null; then
        sed -i "s/^DEPLOYMENT_TYPE=.*/DEPLOYMENT_TYPE=$DEPLOYMENT_TYPE/" "$CONFIG_FILE"
    else
        echo "DEPLOYMENT_TYPE=$DEPLOYMENT_TYPE" >> "$CONFIG_FILE"
    fi
    
    echo -e "${GREEN}✅ Deployment type set to: $DEPLOYMENT_TYPE${NC}"
}

# Function to run validation
run_validation() {
    echo -e "\n${YELLOW}🔍 Running Deployment Validation${NC}"
    echo "=================================="
    
    echo -e "${CYAN}Validating your system configuration...${NC}"
    
    if ./scripts/validate-deployment.sh; then
        echo -e "\n${GREEN}${BOLD}🎉 Validation successful!${NC}"
        return 0
    else
        echo -e "\n${RED}${BOLD}❌ Validation failed!${NC}"
        echo ""
        echo -e "${YELLOW}Would you like to:${NC}"
        echo "1. View detailed error log"
        echo "2. Try a different deployment type"
        echo "3. Exit and fix issues manually"
        echo ""
        
        get_input "Choose option (1-3)" "1" "VALIDATION_CHOICE"
        
        case "$VALIDATION_CHOICE" in
            1)
                echo -e "\n${BLUE}Validation Log:${NC}"
                cat logs/validation.log
                echo ""
                read -p "Press Enter to continue..."
                return 1
                ;;
            2)
                configure_deployment
                run_validation
                ;;
            3)
                echo -e "${YELLOW}Please fix the validation errors and run the wizard again.${NC}"
                exit 1
                ;;
        esac
    fi
}

# Main wizard flow
main() {
    log_wizard "Deployment wizard started"
    
    # Step 1: Display system information
    display_system_info
    
    # Step 2: Configure network
    configure_network
    
    # Step 3: Configure PHP
    configure_php
    
    # Step 4: Configure deployment type
    configure_deployment
    
    # Step 5: Run validation
    if run_validation; then
        echo -e "\n${GREEN}${BOLD}🎉 Configuration Complete!${NC}"
        echo "=========================="
        echo ""
        echo -e "${BLUE}Your configuration:${NC}"
        echo "• Deployment Type: $DEPLOYMENT_TYPE"
        echo "• PHP Version: $PHP_VERSION"
        echo "• Network Interface: $NETWORK_INTERFACE"
        echo ""
        echo -e "${BLUE}Next steps:${NC}"
        echo "1. Start supporting services: ${CYAN}make start-services${NC}"
        echo "2. Set up applications: ${CYAN}make setup${NC}"
        echo "3. Start applications: ${CYAN}make start${NC}"
        echo "4. Test deployment: ${CYAN}make test${NC}"
        echo ""
        echo -e "${GREEN}Happy coding! 🚀${NC}"
        
        log_wizard "Deployment wizard completed successfully"
    else
        echo -e "\n${RED}Configuration failed. Please check the errors above.${NC}"
        log_wizard "Deployment wizard failed"
        exit 1
    fi
}

# Run the wizard
main "$@"
