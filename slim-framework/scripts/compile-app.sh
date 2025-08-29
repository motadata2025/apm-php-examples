#!/bin/bash

# Slim Framework Application - Compilation Script
# APM PHP Examples - Independent Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="Slim Framework"
DEFAULT_PORT=8003
PORT_RANGE_START=8000
PORT_RANGE_END=9000
CONFIG_FILE="config/app.env"

echo -e "${BLUE}🔨 ${APP_NAME} - Application Compilation${NC}"
echo "============================================"

# Create config directory if it doesn't exist
mkdir -p config

# Function to check if app is running
check_app_running() {
    echo -e "\n${PURPLE}🔍 Checking if application is running...${NC}"
    
    local pid_file=".app.pid"
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            echo -e "  ${RED}❌ Application is currently running (PID: $pid)${NC}"
            echo -e "  ${YELLOW}Please stop the application first: make stop${NC}"
            exit 1
        else
            # Remove stale PID file
            rm -f "$pid_file"
        fi
    fi
    
    echo -e "  ${GREEN}✅ Application is not running${NC}"
}

# Function to get network configuration
get_network_config() {
    echo -e "\n${PURPLE}🌐 Network Configuration${NC}"
    
    # Get machine IP
    local machine_ip=$(hostname -I | awk '{print $1}' 2>/dev/null || echo "127.0.0.1")
    
    echo -e "  ${BLUE}Available network options:${NC}"
    echo -e "    1) localhost (127.0.0.1) - Local access only"
    echo -e "    2) public ($machine_ip) - Internet accessible"
    echo -e "    3) custom - Specify custom IP"
    echo ""
    
    while true; do
        read -p "Select network option (1-3): " choice
        case $choice in
            1)
                NETWORK_INTERFACE="127.0.0.1"
                echo -e "  ${GREEN}✅ Selected: localhost (127.0.0.1)${NC}"
                break
                ;;
            2)
                NETWORK_INTERFACE="0.0.0.0"
                PUBLIC_IP="$machine_ip"
                echo -e "  ${GREEN}✅ Selected: public access (0.0.0.0)${NC}"
                echo -e "  ${BLUE}Public IP: $machine_ip${NC}"
                break
                ;;
            3)
                read -p "Enter custom IP address: " custom_ip
                if [[ $custom_ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
                    NETWORK_INTERFACE="$custom_ip"
                    echo -e "  ${GREEN}✅ Selected: custom IP ($custom_ip)${NC}"
                    break
                else
                    echo -e "  ${RED}❌ Invalid IP address format${NC}"
                fi
                ;;
            *)
                echo -e "  ${RED}❌ Invalid choice. Please select 1, 2, or 3${NC}"
                ;;
        esac
    done
}

# Function to find available port
find_available_port() {
    local start_port=$1
    local end_port=$2
    
    for port in $(seq $start_port $end_port); do
        if ! netstat -tuln 2>/dev/null | grep -q ":$port " && ! ss -tuln 2>/dev/null | grep -q ":$port "; then
            echo $port
            return 0
        fi
    done
    
    return 1
}

# Function to get port configuration
get_port_config() {
    echo -e "\n${PURPLE}🔌 Port Configuration${NC}"
    
    echo -e "  ${BLUE}Default port for ${APP_NAME}: $DEFAULT_PORT${NC}"
    
    if netstat -tuln 2>/dev/null | grep -q ":$DEFAULT_PORT " || ss -tuln 2>/dev/null | grep -q ":$DEFAULT_PORT "; then
        echo -e "  ${YELLOW}⚠️  Port $DEFAULT_PORT is already in use${NC}"
        
        local available_port=$(find_available_port $PORT_RANGE_START $PORT_RANGE_END)
        if [ $? -eq 0 ]; then
            APP_PORT=$available_port
            echo -e "  ${GREEN}✅ Found available port: $APP_PORT${NC}"
        else
            echo -e "  ${RED}❌ No available ports in range $PORT_RANGE_START-$PORT_RANGE_END${NC}"
            exit 1
        fi
    else
        APP_PORT=$DEFAULT_PORT
        echo -e "  ${GREEN}✅ Port $DEFAULT_PORT is available${NC}"
    fi
}

# Function to select PHP version
select_php_version() {
    echo -e "\n${PURPLE}🐘 PHP Version Selection${NC}"
    
    local available_versions=()
    
    # Check available PHP versions
    for version in "8.1" "8.2" "8.3" "8.4"; do
        if command -v "php$version" >/dev/null 2>&1; then
            available_versions+=("$version")
        fi
    done
    
    # Add default PHP if available
    if command -v php >/dev/null 2>&1; then
        local default_version=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        if [[ ! " ${available_versions[@]} " =~ " ${default_version} " ]]; then
            available_versions+=("$default_version (default)")
        fi
    fi
    
    if [ ${#available_versions[@]} -eq 0 ]; then
        echo -e "  ${RED}❌ No PHP versions found${NC}"
        exit 1
    fi
    
    echo -e "  ${BLUE}Available PHP versions:${NC}"
    for i in "${!available_versions[@]}"; do
        echo -e "    $((i+1))) PHP ${available_versions[$i]}"
    done
    echo ""
    
    while true; do
        read -p "Select PHP version (1-${#available_versions[@]}): " choice
        if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#available_versions[@]}" ]; then
            SELECTED_PHP_VERSION="${available_versions[$((choice-1))]}"
            # Remove "(default)" suffix if present
            SELECTED_PHP_VERSION="${SELECTED_PHP_VERSION% (default)}"
            echo -e "  ${GREEN}✅ Selected: PHP $SELECTED_PHP_VERSION${NC}"
            break
        else
            echo -e "  ${RED}❌ Invalid choice${NC}"
        fi
    done
}

# Function to install dependencies
install_dependencies() {
    echo -e "\n${PURPLE}📦 Installing Dependencies${NC}"
    
    echo -e "  ${BLUE}Checking dependencies for PHP $SELECTED_PHP_VERSION...${NC}"
    
    # Check if composer.json exists
    if [ ! -f "composer.json" ]; then
        echo -e "  ${RED}❌ composer.json not found${NC}"
        exit 1
    fi
    
    # Ask user about dependency installation
    echo -e "  ${YELLOW}Dependencies need to be installed for PHP $SELECTED_PHP_VERSION${NC}"
    read -p "Install dependencies? (y/n): " install_deps
    
    if [[ "$install_deps" =~ ^[Yy]$ ]]; then
        echo -e "  ${BLUE}Installing dependencies...${NC}"
        
        # Use specific PHP version if available
        if command -v "php$SELECTED_PHP_VERSION" >/dev/null 2>&1; then
            "php$SELECTED_PHP_VERSION" $(which composer) install --optimize-autoloader --no-dev
        else
            composer install --optimize-autoloader --no-dev
        fi
        
        echo -e "  ${GREEN}✅ Dependencies installed${NC}"
        
        # Setup Slim environment
        setup_slim_environment
    else
        echo -e "  ${RED}❌ Compilation cancelled - dependencies required${NC}"
        exit 1
    fi
}

# Function to setup Slim environment
setup_slim_environment() {
    echo -e "\n${PURPLE}🏃 Setting up Slim Environment${NC}"
    
    # Create .env file if it doesn't exist
    if [ ! -f ".env" ]; then
        echo -e "  ${BLUE}Creating .env file...${NC}"
        cat > .env << EOF
# Slim Framework Environment Configuration
APP_ENV=production
APP_DEBUG=false

# Database Configuration
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DATABASE=apm_examples
MYSQL_USERNAME=root
MYSQL_PASSWORD=rootpassword

POSTGRES_HOST=localhost
POSTGRES_PORT=5432
POSTGRES_DATABASE=apm_examples
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
EOF
    fi
    
    # Ensure public directory exists
    if [ ! -d "public" ]; then
        echo -e "  ${YELLOW}⚠️  Public directory missing${NC}"
        mkdir -p public
    fi
    
    # Check if index.php exists
    if [ ! -f "public/index.php" ]; then
        echo -e "  ${YELLOW}⚠️  public/index.php missing${NC}"
        echo -e "  ${BLUE}Please ensure your Slim application is properly configured${NC}"
    fi
    
    echo -e "  ${GREEN}✅ Slim environment configured${NC}"
}

# Function to select web server
select_web_server() {
    echo -e "\n${PURPLE}🌐 Web Server Selection${NC}"
    
    local available_servers=()
    
    # Check PHP-CLI
    if command -v php >/dev/null 2>&1; then
        available_servers+=("php-cli:PHP Built-in Server")
    fi
    
    # Check Apache mod_php
    if (command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1) && 
       (apache2ctl -M 2>/dev/null | grep -q php || httpd -M 2>/dev/null | grep -q php); then
        available_servers+=("apache-mod-php:Apache with mod_php")
    fi
    
    # Check Apache PHP-FPM
    if (command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1) && 
       command -v php-fpm >/dev/null 2>&1; then
        available_servers+=("apache-fpm:Apache with PHP-FPM")
    fi
    
    # Check Nginx PHP-FPM
    if command -v nginx >/dev/null 2>&1 && command -v php-fpm >/dev/null 2>&1; then
        available_servers+=("nginx-fpm:Nginx with PHP-FPM")
    fi
    
    if [ ${#available_servers[@]} -eq 0 ]; then
        echo -e "  ${RED}❌ No web servers available${NC}"
        exit 1
    fi
    
    echo -e "  ${BLUE}Available deployment options:${NC}"
    for i in "${!available_servers[@]}"; do
        IFS=':' read -r server_type server_desc <<< "${available_servers[$i]}"
        echo -e "    $((i+1))) $server_desc"
    done
    echo ""
    
    while true; do
        read -p "Select deployment option (1-${#available_servers[@]}): " choice
        if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#available_servers[@]}" ]; then
            IFS=':' read -r DEPLOYMENT_TYPE DEPLOYMENT_DESC <<< "${available_servers[$((choice-1))]}"
            echo -e "  ${GREEN}✅ Selected: $DEPLOYMENT_DESC${NC}"
            break
        else
            echo -e "  ${RED}❌ Invalid choice${NC}"
        fi
    done
}

# Function to save configuration
save_configuration() {
    echo -e "\n${PURPLE}💾 Saving Configuration${NC}"
    
    cat > "$CONFIG_FILE" << EOF
# Slim Framework Application Configuration
# Generated on $(date)

APP_NAME=$APP_NAME
APP_PORT=$APP_PORT
NETWORK_INTERFACE=$NETWORK_INTERFACE
PUBLIC_IP=${PUBLIC_IP:-$NETWORK_INTERFACE}
PHP_VERSION=$SELECTED_PHP_VERSION
DEPLOYMENT_TYPE=$DEPLOYMENT_TYPE
DEPLOYMENT_DESC=$DEPLOYMENT_DESC

# Environment
APP_ENV=production
APP_DEBUG=false

# Database Configuration
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DATABASE=apm_examples
MYSQL_USERNAME=root
MYSQL_PASSWORD=rootpassword

POSTGRES_HOST=localhost
POSTGRES_PORT=5432
POSTGRES_DATABASE=apm_examples
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword

REDIS_HOST=localhost
REDIS_PORT=6379
EOF
    
    echo -e "  ${GREEN}✅ Configuration saved to $CONFIG_FILE${NC}"
}

# Function to show compilation summary
show_summary() {
    echo -e "\n${BLUE}📊 Compilation Summary${NC}"
    echo "======================"
    echo -e "${GREEN}✅ ${APP_NAME} application ready for deployment${NC}"
    echo ""
    echo -e "${YELLOW}Configuration:${NC}"
    echo -e "  App Port: ${BLUE}$APP_PORT${NC}"
    echo -e "  Network: ${BLUE}$NETWORK_INTERFACE${NC}"
    echo -e "  PHP Version: ${BLUE}$SELECTED_PHP_VERSION${NC}"
    echo -e "  Deployment: ${BLUE}$DEPLOYMENT_DESC${NC}"
    echo ""
    echo -e "${YELLOW}Next step:${NC}"
    echo -e "  Run: ${BLUE}make start${NC} - to start the application"
    echo ""
}

# Main execution
main() {
    check_app_running
    get_network_config
    get_port_config
    select_php_version
    install_dependencies
    select_web_server
    save_configuration
    show_summary
}

# Execute main function
main "$@"
