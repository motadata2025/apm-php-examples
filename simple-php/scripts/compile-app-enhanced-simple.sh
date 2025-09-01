#!/bin/bash

# Simple PHP Application - Enhanced Compilation Script
# Follows user specifications: simple commands, comprehensive checks

set -e

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common-functions.sh"

# Configuration
APP_NAME="Simple PHP"
RUNTIME_CONFIG="config/runtime.env"

# Step 1: Show installed PHP versions and let user select
select_php_version() {
    log_info "🐘 PHP Version Selection"
    echo "=================================="
    echo ""
    
    # Detect installed PHP versions
    local available_versions=()
    local version_details=()
    
    echo -e "${BLUE}Scanning for installed PHP versions...${NC}"
    for version in "8.1" "8.2" "8.3" "8.4"; do
        if command -v "php$version" >/dev/null 2>&1; then
            local full_version=$(php$version -r "echo PHP_VERSION;" 2>/dev/null || echo "unknown")
            available_versions+=("$version")
            version_details+=("$version ($full_version)")
            echo "  ✅ PHP $version: $full_version"
        else
            echo "  ❌ PHP $version: Not installed"
        fi
    done
    
    if [[ ${#available_versions[@]} -eq 0 ]]; then
        fail_with_error "select_php_version" "No supported PHP versions found" $EXIT_GENERAL_ERROR \
            "Install PHP with:\n$(get_install_command "php php-cli php-fpm")"
    fi
    
    echo ""
    echo -e "${BLUE}Available PHP versions for compilation:${NC}"
    for i in "${!version_details[@]}"; do
        echo "  $((i+1))) ${version_details[$i]}"
    done
    echo ""
    
    while true; do
        read -p "Select PHP version (1-${#available_versions[@]}): " choice
        
        if [[ "$choice" =~ ^[0-9]+$ ]] && [[ "$choice" -ge 1 ]] && [[ "$choice" -le ${#available_versions[@]} ]]; then
            SELECTED_PHP_VERSION="${available_versions[$((choice-1))]}"
            log_success "PHP $SELECTED_PHP_VERSION selected"
            break
        else
            log_warning "Invalid choice. Please select a number between 1 and ${#available_versions[@]}."
        fi
    done
}

# Step 2: Check dependencies for selected PHP version
check_app_dependencies() {
    log_info "📦 Checking Application Dependencies"
    echo "=================================="
    echo ""
    
    # Required extensions for Simple PHP
    local required_extensions=("mysql" "pgsql" "redis" "mbstring" "xml" "curl" "json")
    local missing_extensions=()
    
    echo -e "${BLUE}Checking required extensions for PHP $SELECTED_PHP_VERSION...${NC}"
    
    for ext in "${required_extensions[@]}"; do
        if php$SELECTED_PHP_VERSION -m 2>/dev/null | grep -q "^$ext$"; then
            echo "  ✅ $ext: Available"
        else
            echo "  ❌ $ext: Missing"
            missing_extensions+=("php$SELECTED_PHP_VERSION-$ext")
        fi
    done
    
    if [[ ${#missing_extensions[@]} -gt 0 ]]; then
        echo ""
        echo -e "${YELLOW}⚠️  Missing dependencies detected:${NC}"
        printf '  %s\n' "${missing_extensions[@]}"
        echo ""
        echo -e "${BLUE}Install command:${NC}"
        echo "  $(get_install_command "${missing_extensions[*]}")"
        echo ""
        
        while true; do
            read -p "Install missing dependencies? (y/n): " install_choice
            case $install_choice in
                y|Y|yes|YES)
                    log_info "Installing missing dependencies..."
                    install_php_extensions "${missing_extensions[@]}"
                    break
                    ;;
                n|N|no|NO)
                    log_warning "Skipping dependency installation"
                    echo -e "${YELLOW}💡 Application may not work correctly without these dependencies${NC}"
                    break
                    ;;
                *)
                    log_warning "Invalid choice. Please enter y or n."
                    ;;
            esac
        done
    else
        log_success "All required dependencies are available"
    fi
}

# Step 3: Environment selection (prod/dev)
select_environment() {
    log_info "🌍 Environment Selection"
    echo "=================================="
    echo ""
    
    echo -e "${BLUE}Select application environment:${NC}"
    echo "  1) Production (default) - Optimized for live deployment"
    echo "  2) Development - Debug enabled, verbose logging"
    echo ""
    
    while true; do
        read -p "Select environment (1-2, default: 1): " env_choice
        case ${env_choice:-1} in
            1)
                SELECTED_ENVIRONMENT="production"
                SELECTED_DEBUG="false"
                log_success "Production environment selected"
                break
                ;;
            2)
                SELECTED_ENVIRONMENT="development"
                SELECTED_DEBUG="true"
                log_success "Development environment selected"
                break
                ;;
            *)
                log_warning "Invalid choice. Please select 1 or 2."
                ;;
        esac
    done
}

# Step 4: Web server selection with comprehensive checks
select_web_server() {
    log_info "🌐 Web Server Selection"
    echo "=================================="
    echo ""
    
    # Check web server availability and status
    local web_servers=()
    local server_details=()
    
    echo -e "${BLUE}Scanning web server availability...${NC}"
    
    # Always available: PHP CLI
    web_servers+=("php-cli")
    server_details+=("PHP Built-in Server (Always available)")
    echo "  ✅ PHP-CLI: Always available"
    
    # Check Apache
    if command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1; then
        local apache_cmd="apache2"
        command -v httpd >/dev/null 2>&1 && apache_cmd="httpd"
        
        # Check if Apache is running
        if systemctl is-active --quiet apache2 2>/dev/null || systemctl is-active --quiet httpd 2>/dev/null; then
            echo "  ✅ Apache: Installed and running"
            
            # Check mod_php availability
            if $apache_cmd -M 2>/dev/null | grep -q "php"; then
                web_servers+=("apache-mod-php")
                server_details+=("Apache with mod_php (Available and enabled)")
                echo "    ✅ mod_php: Enabled"
            else
                web_servers+=("apache-mod-php")
                server_details+=("Apache with mod_php (Available but disabled)")
                echo "    ⚠️  mod_php: Available but disabled"
            fi
            
            # Check PHP-FPM for Apache
            if systemctl is-active --quiet "php$SELECTED_PHP_VERSION-fpm" 2>/dev/null; then
                web_servers+=("apache-fpm")
                server_details+=("Apache with PHP-FPM (Available and running)")
                echo "    ✅ PHP-FPM: Running for PHP $SELECTED_PHP_VERSION"
            else
                web_servers+=("apache-fpm")
                server_details+=("Apache with PHP-FPM (Available but not running)")
                echo "    ⚠️  PHP-FPM: Available but not running"
            fi
        else
            echo "  ⚠️  Apache: Installed but not running"
            web_servers+=("apache-mod-php")
            server_details+=("Apache with mod_php (Installed but not running)")
        fi
    else
        echo "  ❌ Apache: Not installed"
    fi
    
    # Check Nginx
    if command -v nginx >/dev/null 2>&1; then
        if systemctl is-active --quiet nginx 2>/dev/null; then
            echo "  ✅ Nginx: Installed and running"
            
            # Check PHP-FPM for Nginx
            if systemctl is-active --quiet "php$SELECTED_PHP_VERSION-fpm" 2>/dev/null; then
                web_servers+=("nginx-fpm")
                server_details+=("Nginx with PHP-FPM (Available and running)")
                echo "    ✅ PHP-FPM: Running for PHP $SELECTED_PHP_VERSION"
            else
                web_servers+=("nginx-fpm")
                server_details+=("Nginx with PHP-FPM (Available but FPM not running)")
                echo "    ⚠️  PHP-FPM: Available but not running"
            fi
        else
            echo "  ⚠️  Nginx: Installed but not running"
            web_servers+=("nginx-fpm")
            server_details+=("Nginx with PHP-FPM (Installed but not running)")
        fi
    else
        echo "  ❌ Nginx: Not installed"
    fi
    
    echo ""
    echo -e "${BLUE}Available deployment options:${NC}"
    for i in "${!web_servers[@]}"; do
        echo "  $((i+1))) ${server_details[$i]}"
    done
    echo ""
    
    # Auto-select if only one option
    if [[ ${#web_servers[@]} -eq 1 ]]; then
        SELECTED_DEPLOYMENT="${web_servers[0]}"
        log_success "Auto-selected: ${server_details[0]}"
        return 0
    fi
    
    while true; do
        read -p "Select web server (1-${#web_servers[@]}): " server_choice
        
        if [[ "$server_choice" =~ ^[0-9]+$ ]] && [[ "$server_choice" -ge 1 ]] && [[ "$server_choice" -le ${#web_servers[@]} ]]; then
            SELECTED_DEPLOYMENT="${web_servers[$((server_choice-1))]}"
            log_success "Selected: ${server_details[$((server_choice-1))]}"
            break
        else
            log_warning "Invalid choice. Please select a number between 1 and ${#web_servers[@]}."
        fi
    done
}

# Step 5: Configure selected web server
configure_web_server() {
    log_info "⚙️  Configuring Web Server"
    echo "=================================="
    echo ""
    
    case "$SELECTED_DEPLOYMENT" in
        "php-cli")
            log_success "PHP-CLI selected - no additional configuration needed"
            ;;
        "apache-mod-php")
            configure_apache_mod_php
            ;;
        "apache-fpm")
            configure_apache_fpm
            ;;
        "nginx-fpm")
            configure_nginx_fpm
            ;;
    esac
}

# Configure Apache mod_php
configure_apache_mod_php() {
    log_info "Configuring Apache with mod_php..."
    
    # Disable PHP-FPM if running
    if systemctl is-active --quiet "php$SELECTED_PHP_VERSION-fpm" 2>/dev/null; then
        log_info "Disabling PHP-FPM for mod_php deployment..."
        sudo systemctl stop "php$SELECTED_PHP_VERSION-fpm" || true
        sudo systemctl disable "php$SELECTED_PHP_VERSION-fpm" || true
    fi
    
    # Disable Nginx if running
    if systemctl is-active --quiet nginx 2>/dev/null; then
        log_info "Disabling Nginx for Apache deployment..."
        sudo systemctl stop nginx || true
    fi
    
    # Enable mod_php
    log_info "Enabling mod_php for PHP $SELECTED_PHP_VERSION..."
    sudo a2enmod "php$SELECTED_PHP_VERSION" || true
    
    # Start Apache
    sudo systemctl start apache2 || sudo systemctl start httpd || true
    sudo systemctl enable apache2 || sudo systemctl enable httpd || true
    
    log_success "Apache with mod_php configured"
}

# Configure Apache PHP-FPM
configure_apache_fpm() {
    log_info "Configuring Apache with PHP-FPM..."
    
    # Disable mod_php if enabled
    for version in "8.1" "8.2" "8.3" "8.4"; do
        sudo a2dismod "php$version" 2>/dev/null || true
    done
    
    # Disable Nginx if running
    if systemctl is-active --quiet nginx 2>/dev/null; then
        log_info "Disabling Nginx for Apache deployment..."
        sudo systemctl stop nginx || true
    fi
    
    # Enable PHP-FPM
    log_info "Starting PHP-FPM for PHP $SELECTED_PHP_VERSION..."
    sudo systemctl start "php$SELECTED_PHP_VERSION-fpm" || true
    sudo systemctl enable "php$SELECTED_PHP_VERSION-fpm" || true
    
    # Enable required Apache modules
    sudo a2enmod proxy_fcgi setenvif || true
    sudo a2enconf "php$SELECTED_PHP_VERSION-fpm" || true
    
    # Start Apache
    sudo systemctl start apache2 || sudo systemctl start httpd || true
    sudo systemctl enable apache2 || sudo systemctl enable httpd || true
    sudo systemctl restart apache2 || sudo systemctl restart httpd || true
    
    log_success "Apache with PHP-FPM configured"
}

# Configure Nginx PHP-FPM
configure_nginx_fpm() {
    log_info "Configuring Nginx with PHP-FPM..."
    
    # Disable Apache if running
    if systemctl is-active --quiet apache2 2>/dev/null || systemctl is-active --quiet httpd 2>/dev/null; then
        log_info "Disabling Apache for Nginx deployment..."
        sudo systemctl stop apache2 || sudo systemctl stop httpd || true
    fi
    
    # Enable PHP-FPM
    log_info "Starting PHP-FPM for PHP $SELECTED_PHP_VERSION..."
    sudo systemctl start "php$SELECTED_PHP_VERSION-fpm" || true
    sudo systemctl enable "php$SELECTED_PHP_VERSION-fpm" || true
    
    # Start Nginx
    sudo systemctl start nginx || true
    sudo systemctl enable nginx || true
    sudo systemctl restart nginx || true
    
    log_success "Nginx with PHP-FPM configured"
}

# Main execution
main() {
    log_info "🔨 Simple PHP - Enhanced Compilation"
    echo "====================================="
    echo ""
    
    # Check if app is running
    if [[ -f "logs/app.pid" ]]; then
        local pid=$(cat "logs/app.pid" 2>/dev/null)
        if [[ -n "$pid" ]] && ps -p "$pid" >/dev/null 2>&1; then
            log_error "Application is currently running"
            echo -e "${YELLOW}💡 Stop the application first: make stop${NC}"
            exit $EXIT_GENERAL_ERROR
        fi
    fi
    
    # Execute compilation steps
    select_php_version
    check_app_dependencies
    select_environment
    select_web_server
    configure_web_server
    
    # Generate configuration
    generate_runtime_config
    
    success_with_message "compile" "Application compilation completed successfully" \
        "• Configuration saved to: $RUNTIME_CONFIG\n• Run 'make start' to deploy the application"
}

# Generate runtime configuration
generate_runtime_config() {
    log_info "💾 Generating Runtime Configuration"
    echo "=================================="
    
    mkdir -p config logs
    
    cat > "$RUNTIME_CONFIG" << EOF
# Simple PHP Application - Runtime Configuration
# Generated by Enhanced Compilation on $(date)

# Application Settings
APP_NAME="$APP_NAME"
APP_PORT=8000
PHP_VERSION="$SELECTED_PHP_VERSION"
DEPLOYMENT_TYPE="$SELECTED_DEPLOYMENT"
APP_ENV=$SELECTED_ENVIRONMENT
APP_DEBUG=$SELECTED_DEBUG

# Network Configuration
NETWORK_INTERFACE="0.0.0.0"
NETWORK_DISPLAY="0.0.0.0"

# Database Configuration
MYSQL_HOST=localhost
MYSQL_PORT=3307
MYSQL_DATABASE=simple_php_db
MYSQL_USERNAME=root
MYSQL_PASSWORD=rootpassword

POSTGRES_HOST=localhost
POSTGRES_PORT=5433
POSTGRES_DATABASE=simple_php_db
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword

REDIS_HOST=localhost
REDIS_PORT=6380

# Web Server Configuration
APACHE_APP_DIR="/var/www/simple-php"
NGINX_APP_DIR="/var/www/simple-php"
PHP_FPM_SOCKET="/run/php/php$SELECTED_PHP_VERSION-fpm.sock"

# Docker Configuration
DOCKER_COMPOSE_CMD="docker compose"
DOCKER_NETWORK="simple_php_network"
DOCKER_MYSQL_PORT=3307
DOCKER_POSTGRES_PORT=5433
DOCKER_REDIS_PORT=6380

# Configuration Metadata
CONFIG_VERSION="2.1"
CONFIG_GENERATED_BY="enhanced_compile"
CONFIG_LAST_UPDATED="$(date '+%Y-%m-%d %H:%M:%S')"
EOF

    log_success "Configuration saved to: $RUNTIME_CONFIG"
}

# Execute main function
main "$@"
