#!/bin/bash

# Simple PHP Application - Enhanced Interactive Configuration Wizard
# User-driven configuration with profiles, port selection, and environment choices

set -e

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common-functions.sh"

# Configuration
APP_NAME="Simple PHP"
DEFAULT_PORT=8000
RUNTIME_CONFIG="config/runtime.env"
PROFILES_DIR="config/profiles"

# Configuration profiles
PROFILE_DEV="development"
PROFILE_PROD="production"
CURRENT_PROFILE=""

# Global configuration variables
SELECTED_PHP_VERSION=""
SELECTED_DEPLOYMENT_TYPE=""
SELECTED_NETWORK_INTERFACE=""
SELECTED_PORT=""
SELECTED_ENVIRONMENT=""
SELECTED_DEBUG_MODE=""
ENABLE_HTTPS=""
CONFIGURE_FIREWALL=""

# Initialize configuration wizard
init_wizard() {
    log_info "🧙 ${APP_NAME} - Interactive Configuration Wizard"
    echo "=================================================="
    echo ""
    echo -e "${BLUE}This wizard will guide you through configuring your application.${NC}"
    echo -e "${BLUE}You can customize PHP version, deployment type, network settings, and more.${NC}"
    echo ""
    
    # Create necessary directories
    mkdir -p config logs "$PROFILES_DIR"
    
    # Check if app is running
    if check_app_running; then
        log_error "Application is currently running"
        echo -e "${YELLOW}💡 Stop the application first: make stop${NC}"
        exit $EXIT_GENERAL_ERROR
    fi
}

# Check if application is running
check_app_running() {
    if [[ -f "logs/app.pid" ]]; then
        local pid=$(cat "logs/app.pid" 2>/dev/null)
        if [[ -n "$pid" ]] && ps -p "$pid" >/dev/null 2>&1; then
            return 0
        fi
    fi
    return 1
}

# Step 1: Configuration Profile Selection
select_configuration_profile() {
    echo -e "\n${PURPLE}📋 Step 1: Configuration Profile${NC}"
    echo "=================================="
    echo ""
    echo -e "${BLUE}Choose a configuration profile:${NC}"
    echo "  1) Development - Debug enabled, localhost access, PHP built-in server"
    echo "  2) Production - Optimized settings, public access, web server deployment"
    echo "  3) Custom - Configure each setting individually"
    echo ""
    
    while true; do
        read -t 30 -p "Select profile (1-3, default: 3): " choice || choice="3"
        case $choice in
            1)
                CURRENT_PROFILE="$PROFILE_DEV"
                log_success "Development profile selected"
                load_development_defaults
                break
                ;;
            2)
                CURRENT_PROFILE="$PROFILE_PROD"
                log_success "Production profile selected"
                load_production_defaults
                break
                ;;
            3|"")
                CURRENT_PROFILE="custom"
                log_success "Custom configuration selected"
                break
                ;;
            *)
                log_warning "Invalid choice. Please select 1, 2, or 3."
                ;;
        esac
    done
}

# Load development profile defaults
load_development_defaults() {
    SELECTED_ENVIRONMENT="development"
    SELECTED_DEBUG_MODE="true"
    SELECTED_NETWORK_INTERFACE="127.0.0.1"
    SELECTED_PORT="$DEFAULT_PORT"
    ENABLE_HTTPS="false"
    CONFIGURE_FIREWALL="false"
    
    log_verbose "Development defaults loaded"
}

# Load production profile defaults
load_production_defaults() {
    SELECTED_ENVIRONMENT="production"
    SELECTED_DEBUG_MODE="false"
    SELECTED_NETWORK_INTERFACE="0.0.0.0"
    SELECTED_PORT="$DEFAULT_PORT"
    ENABLE_HTTPS="true"
    CONFIGURE_FIREWALL="true"
    
    log_verbose "Production defaults loaded"
}

# Step 2: PHP Version Selection
select_php_version() {
    echo -e "\n${PURPLE}📋 Step 2: PHP Version Selection${NC}"
    echo "=================================="
    echo ""
    
    # Get system default PHP version
    local system_default=""
    if command -v php >/dev/null 2>&1; then
        system_default=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "")
    fi
    
    # Check available PHP versions
    local available_versions=()
    local version_details=()
    
    for version in "8.1" "8.2" "8.3" "8.4"; do
        if command -v "php$version" >/dev/null 2>&1; then
            local full_version=$(php$version -r "echo PHP_VERSION;" 2>/dev/null || echo "unknown")
            available_versions+=("$version")
            
            if [[ "$version" == "$system_default" ]]; then
                version_details+=("$version ($full_version) [SYSTEM DEFAULT]")
            else
                version_details+=("$version ($full_version)")
            fi
        fi
    done
    
    if [[ ${#available_versions[@]} -eq 0 ]]; then
        fail_with_error "select_php_version" "No supported PHP versions found" $EXIT_GENERAL_ERROR \
            "Install PHP with:\n$(get_install_command "php php-cli php-fpm")"
    fi
    
    echo -e "${BLUE}Available PHP versions:${NC}"
    for i in "${!version_details[@]}"; do
        echo "  $((i+1))) ${version_details[$i]}"
    done
    echo ""
    
    # Auto-select system default or latest available
    local default_choice=1
    if [[ -n "$system_default" ]]; then
        for i in "${!available_versions[@]}"; do
            if [[ "${available_versions[$i]}" == "$system_default" ]]; then
                default_choice=$((i+1))
                break
            fi
        done
    else
        # Select latest available version
        default_choice=${#available_versions[@]}
    fi
    
    echo -e "${BLUE}💡 Recommended: Option $default_choice (${available_versions[$((default_choice-1))]}${NC}"
    echo ""
    
    while true; do
        read -t 30 -p "Select PHP version (1-${#available_versions[@]}, default: $default_choice): " choice || choice="$default_choice"
        
        if [[ -z "$choice" ]]; then
            choice="$default_choice"
        fi
        
        if [[ "$choice" =~ ^[0-9]+$ ]] && [[ "$choice" -ge 1 ]] && [[ "$choice" -le ${#available_versions[@]} ]]; then
            SELECTED_PHP_VERSION="${available_versions[$((choice-1))]}"
            log_success "PHP $SELECTED_PHP_VERSION selected"
            break
        else
            log_warning "Invalid choice. Please select a number between 1 and ${#available_versions[@]}."
        fi
    done
}

# Step 3: Application Port Selection
select_application_port() {
    if [[ "$CURRENT_PROFILE" != "custom" ]]; then
        log_verbose "Using profile default port: $SELECTED_PORT"
        return 0
    fi
    
    echo -e "\n${PURPLE}📋 Step 3: Application Port Configuration${NC}"
    echo "=========================================="
    echo ""
    echo -e "${BLUE}Current default port: $DEFAULT_PORT${NC}"
    echo ""
    
    while true; do
        read -t 30 -p "Enter application port (default: $DEFAULT_PORT): " port_input || port_input="$DEFAULT_PORT"
        
        if [[ -z "$port_input" ]]; then
            port_input="$DEFAULT_PORT"
        fi
        
        # Validate port number
        if ! [[ "$port_input" =~ ^[0-9]+$ ]] || [[ "$port_input" -lt 1 ]] || [[ "$port_input" -gt 65535 ]]; then
            log_warning "Invalid port number. Please enter a number between 1 and 65535."
            continue
        fi
        
        # Check port availability
        if ! check_port_available "$port_input"; then
            local owner=$(get_port_owner "$port_input")
            log_warning "Port $port_input is already in use by: $owner"
            echo -e "${YELLOW}💡 Suggested alternatives:${NC}"
            
            # Suggest alternative ports
            for alt_port in $((port_input + 1)) $((port_input + 10)) $((port_input + 100)); do
                if check_port_available "$alt_port"; then
                    echo "  - $alt_port (available)"
                    break
                fi
            done
            echo ""
            continue
        fi
        
        SELECTED_PORT="$port_input"
        log_success "Port $SELECTED_PORT selected and available"
        break
    done
}

# Step 4: Environment and Debug Configuration
select_environment_settings() {
    if [[ "$CURRENT_PROFILE" != "custom" ]]; then
        log_verbose "Using profile defaults: Environment=$SELECTED_ENVIRONMENT, Debug=$SELECTED_DEBUG_MODE"
        return 0
    fi
    
    echo -e "\n${PURPLE}📋 Step 4: Environment Configuration${NC}"
    echo "====================================="
    echo ""
    
    # Environment selection
    echo -e "${BLUE}Select application environment:${NC}"
    echo "  1) Development - For local development and testing"
    echo "  2) Production - For live deployment"
    echo ""
    
    while true; do
        read -t 30 -p "Select environment (1-2, default: 2): " env_choice || env_choice="2"
        case $env_choice in
            1)
                SELECTED_ENVIRONMENT="development"
                log_success "Development environment selected"
                break
                ;;
            2|"")
                SELECTED_ENVIRONMENT="production"
                log_success "Production environment selected"
                break
                ;;
            *)
                log_warning "Invalid choice. Please select 1 or 2."
                ;;
        esac
    done
    
    # Debug mode selection
    echo ""
    echo -e "${BLUE}Enable debug mode?${NC}"
    echo "  Debug mode provides detailed error messages and logging"
    echo "  Recommended: Yes for development, No for production"
    echo ""
    
    local debug_default="false"
    if [[ "$SELECTED_ENVIRONMENT" == "development" ]]; then
        debug_default="true"
    fi
    
    while true; do
        local prompt="Enable debug mode? (y/n, default: "
        if [[ "$debug_default" == "true" ]]; then
            prompt+="y): "
        else
            prompt+="n): "
        fi
        
        read -t 30 -p "$prompt" debug_choice || debug_choice=""
        
        case $debug_choice in
            y|Y|yes|YES)
                SELECTED_DEBUG_MODE="true"
                log_success "Debug mode enabled"
                break
                ;;
            n|N|no|NO)
                SELECTED_DEBUG_MODE="false"
                log_success "Debug mode disabled"
                break
                ;;
            "")
                SELECTED_DEBUG_MODE="$debug_default"
                if [[ "$debug_default" == "true" ]]; then
                    log_success "Debug mode enabled (default)"
                else
                    log_success "Debug mode disabled (default)"
                fi
                break
                ;;
            *)
                log_warning "Invalid choice. Please enter y or n."
                ;;
        esac
    done
}

# Step 5: Network Configuration
select_network_configuration() {
    if [[ "$CURRENT_PROFILE" != "custom" ]]; then
        log_verbose "Using profile default network: $SELECTED_NETWORK_INTERFACE"
        return 0
    fi
    
    echo -e "\n${PURPLE}📋 Step 5: Network Configuration${NC}"
    echo "=================================="
    echo ""
    
    # Get network options from network manager
    local network_output=$(./scripts/network-manager.sh options 2>/dev/null || echo "Network manager not available")
    echo "$network_output"
    echo ""
    
    while true; do
        read -t 30 -p "Select network option (1-3, default: 3): " net_choice || net_choice="3"
        case $net_choice in
            1)
                SELECTED_NETWORK_INTERFACE="127.0.0.1"
                log_success "Localhost access selected"
                break
                ;;
            2)
                SELECTED_NETWORK_INTERFACE=$(./scripts/network-manager.sh current-ip 2>/dev/null || echo "192.168.1.100")
                log_success "Public IP access selected ($SELECTED_NETWORK_INTERFACE)"
                log_warning "IP may change on restart - consider option 3 for production"
                break
                ;;
            3|"")
                SELECTED_NETWORK_INTERFACE="0.0.0.0"
                log_success "All interfaces selected (recommended)"
                break
                ;;
            *)
                log_warning "Invalid choice. Please select 1, 2, or 3."
                ;;
        esac
    done
}

# Main execution
main() {
    init_wizard
    select_configuration_profile
    
    # Only ask for custom settings if not using a preset profile
    if [[ "$CURRENT_PROFILE" == "custom" ]] || [[ -z "$SELECTED_PHP_VERSION" ]]; then
        select_php_version
    fi
    
    select_application_port
    select_environment_settings
    select_network_configuration
    
    # Continue with remaining configuration steps
    select_web_server_deployment
    select_security_settings
    handle_dependency_management
    show_configuration_preview
    save_final_configuration

    success_with_message "compile" "Configuration wizard completed successfully" \
        "• Configuration saved to: $RUNTIME_CONFIG\n• Run 'make start' to deploy the application\n• Run 'make status' to check application health"
}

# Step 6: Web Server Deployment Selection
select_web_server_deployment() {
    echo -e "\n${PURPLE}📋 Step 6: Web Server Deployment${NC}"
    echo "=================================="
    echo ""

    # Get deployment options from webserver manager
    local webserver_output=$(./scripts/webserver-manager.sh options "$SELECTED_PHP_VERSION" 2>/dev/null || echo "Webserver manager not available")
    echo "$webserver_output"
    echo ""

    # Add performance recommendations
    echo -e "${BLUE}💡 Performance Recommendations:${NC}"
    echo "  • PHP-CLI: Best for development, easy setup"
    echo "  • Apache mod_php: Good for small to medium sites"
    echo "  • Apache/Nginx + PHP-FPM: Best for production, high performance"
    echo ""

    while true; do
        read -t 30 -p "Select deployment type (1-4, default: 1): " deploy_choice || deploy_choice="1"
        case $deploy_choice in
            1|"")
                SELECTED_DEPLOYMENT_TYPE="php-cli"
                log_success "PHP Built-in Server selected"
                break
                ;;
            2)
                SELECTED_DEPLOYMENT_TYPE="apache-mod-php"
                log_success "Apache with mod_php selected"
                break
                ;;
            3)
                SELECTED_DEPLOYMENT_TYPE="apache-fpm"
                log_success "Apache with PHP-FPM selected"
                break
                ;;
            4)
                SELECTED_DEPLOYMENT_TYPE="nginx-fpm"
                log_success "Nginx with PHP-FPM selected"
                break
                ;;
            *)
                log_warning "Invalid choice. Please select 1, 2, 3, or 4."
                ;;
        esac
    done
}

# Step 7: Security Settings
select_security_settings() {
    if [[ "$CURRENT_PROFILE" != "custom" ]]; then
        log_verbose "Using profile security defaults: HTTPS=$ENABLE_HTTPS, Firewall=$CONFIGURE_FIREWALL"
        return 0
    fi

    echo -e "\n${PURPLE}📋 Step 7: Security Configuration${NC}"
    echo "=================================="
    echo ""

    # HTTPS Configuration
    echo -e "${BLUE}Enable HTTPS/SSL configuration?${NC}"
    echo "  This will prepare SSL certificate paths and HTTPS redirects"
    echo "  Note: You'll need to provide SSL certificates separately"
    echo ""

    while true; do
        read -t 30 -p "Enable HTTPS support? (y/n, default: n): " https_choice || https_choice="n"
        case $https_choice in
            y|Y|yes|YES)
                ENABLE_HTTPS="true"
                log_success "HTTPS support enabled"
                break
                ;;
            n|N|no|NO|"")
                ENABLE_HTTPS="false"
                log_success "HTTPS support disabled"
                break
                ;;
            *)
                log_warning "Invalid choice. Please enter y or n."
                ;;
        esac
    done

    # Firewall Configuration
    echo ""
    echo -e "${BLUE}Configure firewall rules for application port?${NC}"
    echo "  This will automatically open the selected port ($SELECTED_PORT) in UFW firewall"
    echo "  Only applies if UFW is installed and active"
    echo ""

    while true; do
        read -t 30 -p "Configure firewall? (y/n, default: y): " firewall_choice || firewall_choice="y"
        case $firewall_choice in
            y|Y|yes|YES|"")
                CONFIGURE_FIREWALL="true"
                log_success "Firewall configuration enabled"
                break
                ;;
            n|N|no|NO)
                CONFIGURE_FIREWALL="false"
                log_success "Firewall configuration disabled"
                break
                ;;
            *)
                log_warning "Invalid choice. Please enter y or n."
                ;;
        esac
    done
}

# Step 8: Dependency Management
handle_dependency_management() {
    echo -e "\n${PURPLE}📋 Step 8: Dependency Management${NC}"
    echo "=================================="
    echo ""

    # Check if composer is available
    if ! command -v composer >/dev/null 2>&1; then
        log_warning "Composer not found"
        echo -e "${YELLOW}💡 Install Composer:${NC}"
        echo "  curl -sS https://getcomposer.org/installer | php"
        echo "  sudo mv composer.phar /usr/local/bin/composer"
        echo ""
        echo -e "${YELLOW}⚠️  Skipping dependency management${NC}"
        return 0
    fi

    # Check current dependencies
    if [[ -f "composer.json" ]]; then
        echo -e "${BLUE}Checking current dependencies...${NC}"

        # Show missing dependencies
        local composer_check_output
        if composer_check_output=$(composer check-platform-reqs 2>&1); then
            log_success "All platform requirements satisfied"
        else
            log_warning "Missing platform requirements detected:"
            echo "$composer_check_output" | grep -E "(requires|missing)" || true
            echo ""
            echo -e "${YELLOW}💡 Install missing extensions with:${NC}"
            echo "  make setup  # Installs required PHP extensions"
            echo ""
        fi

        # Check if vendor directory exists
        if [[ ! -d "vendor" ]]; then
            echo -e "${YELLOW}⚠️  Dependencies not installed${NC}"
            echo -e "${BLUE}💡 Install dependencies with:${NC}"
            echo "  composer install --optimize-autoloader --no-dev"
        else
            echo -e "${GREEN}✅ Dependencies already installed${NC}"
        fi
    else
        log_warning "No composer.json found - skipping dependency management"
    fi
}

# Step 9: Configuration Preview
show_configuration_preview() {
    echo -e "\n${PURPLE}📋 Step 9: Configuration Preview${NC}"
    echo "=================================="
    echo ""
    echo -e "${BLUE}📋 Final Configuration Summary:${NC}"
    echo "=================================="
    printf "%-20s %s\n" "Profile:" "$CURRENT_PROFILE"
    printf "%-20s %s\n" "Application:" "$APP_NAME"
    printf "%-20s %s\n" "PHP Version:" "$SELECTED_PHP_VERSION"
    printf "%-20s %s\n" "Port:" "$SELECTED_PORT"
    printf "%-20s %s\n" "Environment:" "$SELECTED_ENVIRONMENT"
    printf "%-20s %s\n" "Debug Mode:" "$SELECTED_DEBUG_MODE"
    printf "%-20s %s\n" "Network:" "$SELECTED_NETWORK_INTERFACE"
    printf "%-20s %s\n" "Deployment:" "$SELECTED_DEPLOYMENT_TYPE"
    printf "%-20s %s\n" "HTTPS:" "$ENABLE_HTTPS"
    printf "%-20s %s\n" "Firewall:" "$CONFIGURE_FIREWALL"
    echo ""

    # Confirm configuration
    while true; do
        read -t 30 -p "Apply this configuration? (y/n, default: y): " confirm || confirm="y"
        case $confirm in
            y|Y|yes|YES|"")
                log_success "Configuration confirmed"
                break
                ;;
            n|N|no|NO)
                log_info "Configuration cancelled by user"
                echo -e "${YELLOW}💡 Run 'make compile' again to reconfigure${NC}"
                exit 0
                ;;
            *)
                log_warning "Invalid choice. Please enter y or n."
                ;;
        esac
    done
}

# Step 10: Save Final Configuration
save_final_configuration() {
    echo -e "\n${PURPLE}📋 Step 10: Saving Configuration${NC}"
    echo "=================================="
    echo ""

    log_info "Generating runtime configuration..."

    # Generate runtime.env with all selected settings
    cat > "$RUNTIME_CONFIG" << EOF
# Simple PHP Application - Runtime Configuration
# Generated by Enhanced Configuration Wizard on $(date)

# =============================================================================
# CONFIGURATION METADATA
# =============================================================================
CONFIG_VERSION="2.1"
CONFIG_GENERATED_BY="enhanced_wizard"
CONFIG_LAST_UPDATED="$(date '+%Y-%m-%d %H:%M:%S')"
CONFIG_PROFILE="$CURRENT_PROFILE"

# =============================================================================
# APPLICATION SETTINGS
# =============================================================================
APP_NAME="$APP_NAME"
APP_PORT=$SELECTED_PORT
NETWORK_INTERFACE="$SELECTED_NETWORK_INTERFACE"
NETWORK_DISPLAY="$SELECTED_NETWORK_INTERFACE"
PHP_VERSION="$SELECTED_PHP_VERSION"
DEPLOYMENT_TYPE="$SELECTED_DEPLOYMENT_TYPE"

# =============================================================================
# ENVIRONMENT CONFIGURATION
# =============================================================================
APP_ENV=$SELECTED_ENVIRONMENT
APP_DEBUG=$SELECTED_DEBUG_MODE

# =============================================================================
# SECURITY CONFIGURATION
# =============================================================================
ENABLE_HTTPS=$ENABLE_HTTPS
CONFIGURE_FIREWALL=$CONFIGURE_FIREWALL
SECURE_HEADERS=true
CSRF_PROTECTION=true
XSS_PROTECTION=true

# =============================================================================
# DATABASE CONFIGURATION (Application-Specific Ports)
# =============================================================================
# MySQL Configuration
MYSQL_HOST=localhost
MYSQL_PORT=3307
MYSQL_DATABASE=simple_php_db
MYSQL_USERNAME=root
MYSQL_PASSWORD=rootpassword

# PostgreSQL Configuration
POSTGRES_HOST=localhost
POSTGRES_PORT=5433
POSTGRES_DATABASE=simple_php_db
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6380

# =============================================================================
# WEB SERVER CONFIGURATION
# =============================================================================
APACHE_APP_DIR="/var/www/simple-php"
NGINX_APP_DIR="/var/www/simple-php"
PHP_FPM_SOCKET="/run/php/php$SELECTED_PHP_VERSION-fpm.sock"

# =============================================================================
# DOCKER CONFIGURATION
# =============================================================================
DOCKER_COMPOSE_CMD="docker compose"
DOCKER_NETWORK="simple_php_network"
DOCKER_MYSQL_PORT=3307
DOCKER_POSTGRES_PORT=5433
DOCKER_REDIS_PORT=6380

# =============================================================================
# RESOURCE LIMITS
# =============================================================================
PHP_MEMORY_LIMIT="256M"
PHP_MAX_EXECUTION_TIME=30
COMPOSER_MEMORY_LIMIT="2G"

# =============================================================================
# TIMEOUT SETTINGS
# =============================================================================
DOCKER_HEALTH_TIMEOUT=120
CONTAINER_START_TIMEOUT=60
APPLICATION_START_TIMEOUT=30

# =============================================================================
# LOGGING CONFIGURATION
# =============================================================================
LOG_LEVEL=info
LOG_CHANNEL=stack
MONITORING_ENABLED=true
LOG_FILE="logs/application.log"
EOF

    # Save profile configuration if using a preset
    if [[ "$CURRENT_PROFILE" != "custom" ]]; then
        local profile_file="$PROFILES_DIR/$CURRENT_PROFILE.env"
        cp "$RUNTIME_CONFIG" "$profile_file"
        log_success "Profile saved to: $profile_file"
    fi

    # Configure firewall if requested
    if [[ "$CONFIGURE_FIREWALL" == "true" ]] && command -v ufw >/dev/null 2>&1; then
        log_info "Configuring firewall for port $SELECTED_PORT..."
        if sudo ufw allow "$SELECTED_PORT" >/dev/null 2>&1; then
            log_success "Firewall rule added for port $SELECTED_PORT"
        else
            log_warning "Could not configure firewall (may require manual setup)"
        fi
    fi

    log_success "Configuration saved to: $RUNTIME_CONFIG"
}

# Execute main function
main "$@"
