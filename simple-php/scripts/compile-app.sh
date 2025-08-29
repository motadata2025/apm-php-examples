#!/bin/bash

# Simple PHP Application - Compilation Script
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
APP_NAME="Simple PHP"
DEFAULT_PORT=8000
PORT_RANGE_START=8000
PORT_RANGE_END=9000
CONFIG_FILE="config/app.env"

echo -e "${BLUE}🔨 ${APP_NAME} - Application Compilation${NC}"
echo "=========================================="

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

# Function to get network configuration with dynamic IP management
get_network_config() {
    echo -e "\n${PURPLE}🌐 Network Configuration${NC}"

    # Check if network manager is available
    if [ ! -f "scripts/network-manager.sh" ]; then
        echo -e "  ${RED}❌ Network manager not found${NC}"
        exit 1
    fi

    # Show network status and options
    ./scripts/network-manager.sh options

    local machine_ip=$(./scripts/network-manager.sh current-ip)
    local ip_change_status=$(./scripts/network-manager.sh check-change)

    while true; do
        read -t 30 -p "Select network option (1-4): " choice || choice="3"
        case $choice in
            1)
                NETWORK_INTERFACE="127.0.0.1"
                NETWORK_DISPLAY="localhost (127.0.0.1)"
                echo -e "  ${GREEN}✅ Selected: localhost (127.0.0.1)${NC}"
                ./scripts/network-manager.sh save "localhost" "$NETWORK_INTERFACE"
                break
                ;;
            2)
                NETWORK_INTERFACE="0.0.0.0"
                NETWORK_DISPLAY="public ($machine_ip)"
                PUBLIC_IP="$machine_ip"
                echo -e "  ${GREEN}✅ Selected: public access ($machine_ip)${NC}"
                echo -e "  ${YELLOW}⚠️  Note: IP may change on restart - consider option 3${NC}"
                ./scripts/network-manager.sh save "public" "$machine_ip"
                break
                ;;
            3)
                NETWORK_INTERFACE="0.0.0.0"
                NETWORK_DISPLAY="all interfaces (dynamic IP safe)"
                PUBLIC_IP="$machine_ip"
                echo -e "  ${GREEN}✅ Selected: all interfaces (0.0.0.0)${NC}"
                echo -e "  ${GREEN}✅ Dynamic IP safe - will work after IP changes${NC}"
                echo -e "  ${BLUE}Current public IP: $machine_ip${NC}"
                ./scripts/network-manager.sh save "all-interfaces" "0.0.0.0"
                break
                ;;
            4)
                read -p "Enter custom IP address: " custom_ip
                if [[ $custom_ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
                    NETWORK_INTERFACE="$custom_ip"
                    NETWORK_DISPLAY="custom ($custom_ip)"
                    echo -e "  ${GREEN}✅ Selected: custom IP ($custom_ip)${NC}"
                    ./scripts/network-manager.sh save "custom" "$custom_ip"
                    break
                else
                    echo -e "  ${RED}❌ Invalid IP address format${NC}"
                fi
                ;;
            *)
                echo -e "  ${RED}❌ Invalid choice. Please select 1, 2, 3, or 4${NC}"
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

# Function to select PHP version with smart management
select_php_version() {
    echo -e "\n${PURPLE}🐘 PHP Version Configuration${NC}"

    # Check if PHP version manager is available
    if [ ! -f "scripts/php-version-manager.sh" ]; then
        echo -e "  ${RED}❌ PHP version manager not found${NC}"
        exit 1
    fi

    # Show current PHP status
    ./scripts/php-version-manager.sh status

    # Get currently configured version
    local configured_version=$(./scripts/php-version-manager.sh configured)
    local recommended_version=$(./scripts/php-version-manager.sh recommend)

    # Smart decision: Skip selection if already configured and compatible
    if [ -n "$configured_version" ]; then
        echo -e "\n${BLUE}🔍 Checking current configuration...${NC}"

        # Check if current version is still available
        if command -v "php$configured_version" >/dev/null 2>&1; then
            # Check compatibility with any deployment type (we'll validate specific deployment later)
            if ./scripts/php-version-manager.sh validate "$configured_version" "" 2>/dev/null; then
                echo -e "  ${GREEN}✅ Using configured PHP $configured_version (compatible)${NC}"
                SELECTED_PHP_VERSION="$configured_version"
                return 0
            else
                echo -e "  ${YELLOW}⚠️  Configured PHP $configured_version has compatibility issues${NC}"
            fi
        else
            echo -e "  ${YELLOW}⚠️  Configured PHP $configured_version is no longer available${NC}"
        fi

        # Ask if user wants to change or keep current version
        echo -e "\n${YELLOW}Current PHP version: $configured_version${NC}"
        read -t 15 -p "Keep current PHP version? (y/n, default: y): " keep_current || keep_current="y"
        keep_current=$(echo "$keep_current" | tr -d '[:space:]')

        if [[ "$keep_current" =~ ^[Yy]$ ]] || [ -z "$keep_current" ]; then
            if command -v "php$configured_version" >/dev/null 2>&1; then
                echo -e "  ${GREEN}✅ Keeping PHP $configured_version${NC}"
                SELECTED_PHP_VERSION="$configured_version"
                return 0
            else
                echo -e "  ${RED}❌ PHP $configured_version not available, must select new version${NC}"
            fi
        else
            echo -e "  ${BLUE}📝 Selecting new PHP version...${NC}"
        fi
    else
        echo -e "\n${BLUE}📝 No PHP version configured, selecting version...${NC}"
        echo -e "  ${BLUE}💡 This determines which PHP version will:${NC}"
        echo -e "     • Run your application"
        echo -e "     • Process Composer dependencies"
        echo -e "     • Be used for web server deployment"
    fi

    # Show available versions for selection
    local available_versions=()

    # Check available PHP versions
    for version in "8.1" "8.2" "8.3" "8.4"; do
        if command -v "php$version" >/dev/null 2>&1; then
            if [ "$version" = "$configured_version" ]; then
                available_versions+=("$version (current)")
            elif [ "$version" = "$recommended_version" ]; then
                available_versions+=("$version (recommended)")
            else
                available_versions+=("$version")
            fi
        fi
    done

    # Add default PHP if available
    if command -v php >/dev/null 2>&1; then
        local default_version=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        if [[ ! " ${available_versions[@]} " =~ " ${default_version} " ]]; then
            available_versions+=("$default_version (system default)")
        fi
    fi

    if [ ${#available_versions[@]} -eq 0 ]; then
        echo -e "  ${RED}❌ No PHP versions found${NC}"
        exit 1
    fi

    echo -e "\n${BLUE}Available PHP versions:${NC}"
    for i in "${!available_versions[@]}"; do
        echo -e "    $((i+1))) PHP ${available_versions[$i]}"
    done
    echo ""

    # Default to recommended version or first available
    local default_choice=1
    if [ -n "$recommended_version" ]; then
        for i in "${!available_versions[@]}"; do
            if [[ "${available_versions[$i]}" == *"$recommended_version"* ]]; then
                default_choice=$((i+1))
                break
            fi
        done
    fi

    while true; do
        read -t 30 -p "Select PHP version (1-${#available_versions[@]}, default: $default_choice): " choice || choice="$default_choice"
        if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#available_versions[@]}" ]; then
            SELECTED_PHP_VERSION="${available_versions[$((choice-1))]}"
            # Remove suffixes like "(current)", "(recommended)", "(system default)"
            SELECTED_PHP_VERSION=$(echo "$SELECTED_PHP_VERSION" | sed 's/ (.*)$//')
            echo -e "  ${GREEN}✅ Selected: PHP $SELECTED_PHP_VERSION${NC}"

            # Validate the selection with the PHP version manager
            if ./scripts/php-version-manager.sh validate "$SELECTED_PHP_VERSION" ""; then
                break
            else
                echo -e "  ${RED}❌ PHP version validation failed${NC}"
                # Continue the loop to allow re-selection
            fi
        else
            echo -e "  ${RED}❌ Invalid choice${NC}"
        fi
    done
}

# Function to check if composer needs update
check_composer_needs_update() {
    # Check if vendor directory exists
    if [ ! -d "vendor" ]; then
        echo "install"
        return 0
    fi

    # Check if composer.lock is newer than vendor
    if [ "composer.lock" -nt "vendor" ]; then
        echo "update"
        return 0
    fi

    # Check if composer.json is newer than composer.lock
    if [ "composer.json" -nt "composer.lock" ]; then
        echo "update"
        return 0
    fi

    echo "none"
    return 0
}

# Function to install dependencies with auto-detection
install_dependencies() {
    echo -e "\n${PURPLE}📦 Managing Dependencies${NC}"

    echo -e "  ${BLUE}Checking dependencies for PHP $SELECTED_PHP_VERSION...${NC}"

    # Check if composer.json exists
    if [ ! -f "composer.json" ]; then
        echo -e "  ${RED}❌ composer.json not found${NC}"
        exit 1
    fi

    # Auto-detect what needs to be done
    local composer_action=$(check_composer_needs_update)

    case $composer_action in
        "install")
            echo -e "  ${YELLOW}📦 Dependencies need to be installed${NC}"
            ;;
        "update")
            echo -e "  ${YELLOW}🔄 Dependencies need to be updated${NC}"
            ;;
        "none")
            echo -e "  ${GREEN}✅ Dependencies are up to date${NC}"
            setup_simple_php_environment
            return 0
            ;;
    esac

    # Ask user about dependency management
    read -t 30 -p "Proceed with dependency $composer_action? (y/n): " proceed_deps || proceed_deps="y"

    # Clean up the input (remove whitespace)
    proceed_deps=$(echo "$proceed_deps" | tr -d '[:space:]')

    if [[ "$proceed_deps" =~ ^[Yy]$ ]] || [ -z "$proceed_deps" ]; then
        echo -e "  ${BLUE}Processing dependencies...${NC}"

        # Use specific PHP version if available
        local php_cmd="php"
        if command -v "php$SELECTED_PHP_VERSION" >/dev/null 2>&1; then
            php_cmd="php$SELECTED_PHP_VERSION"
            echo -e "  ${BLUE}Using PHP $SELECTED_PHP_VERSION${NC}"
        fi

        # Execute appropriate composer command
        case $composer_action in
            "install")
                if $php_cmd $(which composer) install --optimize-autoloader --no-dev; then
                    echo -e "  ${GREEN}✅ Dependencies installed successfully${NC}"
                else
                    echo -e "  ${RED}❌ Dependency installation failed${NC}"
                    exit 1
                fi
                ;;
            "update")
                if $php_cmd $(which composer) install --optimize-autoloader --no-dev; then
                    echo -e "  ${GREEN}✅ Dependencies updated successfully${NC}"
                else
                    echo -e "  ${RED}❌ Dependency update failed${NC}"
                    exit 1
                fi
                ;;
        esac

        echo -e "  ${GREEN}✅ Dependencies processed successfully${NC}"

        # Setup Simple PHP environment
        setup_simple_php_environment
    else
        echo -e "  ${RED}❌ Compilation cancelled - dependencies required${NC}"
        exit 1
    fi
}

# Function to setup Simple PHP environment
setup_simple_php_environment() {
    echo -e "  ${BLUE}Setting up Simple PHP environment...${NC}"

    # Create .env file if it doesn't exist
    if [ ! -f ".env" ]; then
        echo -e "  ${BLUE}Creating .env file...${NC}"
        cat > .env << EOF
# Simple PHP Application Configuration
APP_ENV=production
APP_DEBUG=false

# Database Configuration (Docker services)
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=apm_examples
DB_USERNAME=root
DB_PASSWORD=rootpassword

# PostgreSQL Configuration (Docker services)
POSTGRES_HOST=127.0.0.1
POSTGRES_PORT=5433
POSTGRES_DATABASE=apm_examples
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword

# Redis Configuration (Docker services)
REDIS_HOST=127.0.0.1
REDIS_PORT=6380
REDIS_PASSWORD=
EOF
        echo -e "  ${GREEN}✅ Environment file created${NC}"
    else
        echo -e "  ${GREEN}✅ Environment file exists${NC}"
    fi

    # Create logs directory
    if [ ! -d "logs" ]; then
        mkdir -p logs
        echo -e "  ${GREEN}✅ Logs directory created${NC}"
    fi

    echo -e "  ${GREEN}✅ Simple PHP environment ready${NC}"
}

# Function to select web server with PHP version-specific validation
select_web_server() {
    echo -e "\n${PURPLE}🌐 Web Server Selection${NC}"

    # Check if webserver manager is available
    if [ ! -f "scripts/webserver-manager.sh" ]; then
        echo -e "  ${RED}❌ Web server manager not found${NC}"
        exit 1
    fi

    # Get deployment options for the selected PHP version
    local options_output=$(./scripts/webserver-manager.sh options "$SELECTED_PHP_VERSION")
    echo "$options_output"

    # Extract available options from the structured output
    local available_servers=()
    local in_options=false

    while IFS= read -r line; do
        if [[ "$line" == "=== AVAILABLE_OPTIONS ===" ]]; then
            in_options=true
        elif [[ "$line" == "=== END_OPTIONS ===" ]]; then
            in_options=false
        elif [ "$in_options" = true ] && [ -n "$line" ]; then
            available_servers+=("$line")
        fi
    done <<< "$options_output"

    if [ ${#available_servers[@]} -eq 0 ]; then
        echo -e "  ${RED}❌ No web servers available for PHP $SELECTED_PHP_VERSION${NC}"
        exit 1
    fi

    echo -e "\n${BLUE}Available deployment options:${NC}"
    for i in "${!available_servers[@]}"; do
        IFS=':' read -r server_type server_desc <<< "${available_servers[$i]}"
        echo -e "    $((i+1))) $server_desc"
    done
    echo ""

    while true; do
        read -t 30 -p "Select deployment option (1-${#available_servers[@]}): " choice || choice="1"
        if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#available_servers[@]}" ]; then
            IFS=':' read -r DEPLOYMENT_TYPE DEPLOYMENT_DESC <<< "${available_servers[$((choice-1))]}"
            echo -e "  ${GREEN}✅ Selected: $DEPLOYMENT_DESC${NC}"

            # Validate deployment configuration for the specific PHP version
            echo -e "\n${PURPLE}🔍 Validating Configuration${NC}"
            if ./scripts/webserver-manager.sh validate "$SELECTED_PHP_VERSION" "$DEPLOYMENT_TYPE"; then
                # Validate PHP version compatibility with deployment type
                if ./scripts/php-version-manager.sh validate "$SELECTED_PHP_VERSION" "$DEPLOYMENT_TYPE"; then
                    # Save PHP version state
                    ./scripts/php-version-manager.sh save "$SELECTED_PHP_VERSION" "$DEPLOYMENT_TYPE"
                    echo -e "  ${GREEN}✅ Configuration validated and saved${NC}"
                    break
                else
                    echo -e "  ${RED}❌ PHP version validation failed${NC}"
                    # Continue the loop to allow re-selection
                fi
            else
                echo -e "  ${RED}❌ Deployment validation failed${NC}"
                echo -e "  ${YELLOW}Please select a different deployment option${NC}"
                # Continue the loop to allow re-selection
            fi
        else
            echo -e "  ${RED}❌ Invalid choice${NC}"
        fi
    done
}

# Function to save configuration
save_configuration() {
    echo -e "\n${PURPLE}💾 Saving Configuration${NC}"
    
    cat > "$CONFIG_FILE" << EOF
# Simple PHP Application Configuration
# Generated on $(date)

APP_NAME="$APP_NAME"
APP_PORT=$APP_PORT
NETWORK_INTERFACE="$NETWORK_INTERFACE"
NETWORK_DISPLAY="${NETWORK_DISPLAY:-$NETWORK_INTERFACE}"
PUBLIC_IP="${PUBLIC_IP:-$NETWORK_INTERFACE}"
PHP_VERSION="$SELECTED_PHP_VERSION"
DEPLOYMENT_TYPE="$DEPLOYMENT_TYPE"
DEPLOYMENT_DESC="$DEPLOYMENT_DESC"

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
    echo -e "  Network: ${BLUE}${NETWORK_DISPLAY:-$NETWORK_INTERFACE}${NC}"
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
