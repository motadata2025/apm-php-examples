#!/bin/bash

# APM PHP Examples - Multi-Server Deployment Validation System
# This script validates local server configurations before deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Configuration
CONFIG_FILE="config/deployment.env"
VALIDATION_LOG="logs/validation.log"
ROLLBACK_FILE="logs/rollback.json"

# Create logs directory
mkdir -p logs

# Initialize validation log
echo "=== APM PHP Examples - Deployment Validation ===" > "$VALIDATION_LOG"
echo "Timestamp: $(date)" >> "$VALIDATION_LOG"
echo "" >> "$VALIDATION_LOG"

# Load configuration
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    echo -e "${RED}❌ Configuration file not found: $CONFIG_FILE${NC}"
    echo "Run './scripts/configure-deployment.sh' first"
    exit 1
fi

echo -e "${BLUE}${BOLD}🔍 APM PHP Examples - Deployment Validation System${NC}"
echo "=================================================================="
echo -e "Deployment Type: ${CYAN}${DEPLOYMENT_TYPE}${NC}"
echo -e "PHP Version: ${CYAN}${PHP_VERSION}${NC}"
echo -e "Network Interface: ${CYAN}${NETWORK_INTERFACE}${NC}"
echo ""

# Global validation state
VALIDATION_PASSED=true
VALIDATION_ERRORS=()
VALIDATION_WARNINGS=()
ROLLBACK_ACTIONS=()

# Function to log validation results
log_validation() {
    local level="$1"
    local message="$2"
    echo "[$level] $(date): $message" >> "$VALIDATION_LOG"
}

# Function to add rollback action
add_rollback_action() {
    local action="$1"
    ROLLBACK_ACTIONS+=("$action")
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check if service is running
service_running() {
    local service="$1"
    if command_exists systemctl; then
        systemctl is-active --quiet "$service" 2>/dev/null
    elif command_exists service; then
        service "$service" status >/dev/null 2>&1
    else
        # Fallback: check if process is running
        pgrep -x "$service" >/dev/null 2>&1
    fi
}

# Function to check PHP version
validate_php_version() {
    echo -e "\n${YELLOW}🐘 Validating PHP Installation${NC}"
    echo "================================"
    
    if ! command_exists php; then
        VALIDATION_ERRORS+=("PHP is not installed")
        log_validation "ERROR" "PHP is not installed"
        echo -e "${RED}❌ PHP is not installed${NC}"
        return 1
    fi
    
    local current_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
    local required_version="${PHP_VERSION}"
    
    if [ "$current_version" != "$required_version" ]; then
        VALIDATION_ERRORS+=("PHP version mismatch: found $current_version, required $required_version")
        log_validation "ERROR" "PHP version mismatch: found $current_version, required $required_version"
        echo -e "${RED}❌ PHP version mismatch: found $current_version, required $required_version${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ PHP $current_version is installed and matches requirements${NC}"
    log_validation "SUCCESS" "PHP $current_version validation passed"
    return 0
}

# Function to check PHP extensions
validate_php_extensions() {
    echo -e "\n${YELLOW}🔧 Validating PHP Extensions${NC}"
    echo "================================"
    
    local required_extensions=("PDO" "pdo_mysql" "pdo_pgsql" "mbstring" "gd" "zip" "redis" "intl" "curl" "json" "openssl")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo -e "${GREEN}✅ $ext${NC}"
            log_validation "SUCCESS" "PHP extension $ext is available"
        else
            echo -e "${RED}❌ $ext${NC}"
            missing_extensions+=("$ext")
            log_validation "ERROR" "PHP extension $ext is missing"
        fi
    done
    
    if [ ${#missing_extensions[@]} -gt 0 ]; then
        VALIDATION_ERRORS+=("Missing PHP extensions: ${missing_extensions[*]}")
        echo -e "\n${RED}Missing extensions: ${missing_extensions[*]}${NC}"
        echo -e "${YELLOW}Install with: sudo apt install $(printf "php${PHP_VERSION}-%s " "${missing_extensions[@]}")${NC}"
        return 1
    fi
    
    echo -e "\n${GREEN}✅ All required PHP extensions are available${NC}"
    return 0
}

# Function to validate Apache mod_php
validate_apache_mod_php() {
    echo -e "\n${YELLOW}🌐 Validating Apache mod_php Configuration${NC}"
    echo "============================================="
    
    # Check if Apache is installed
    if ! command_exists apache2 && ! command_exists httpd; then
        VALIDATION_ERRORS+=("Apache is not installed")
        echo -e "${RED}❌ Apache is not installed${NC}"
        echo -e "${YELLOW}Install with: sudo apt install apache2${NC}"
        return 1
    fi
    
    # Check if Apache is running
    if ! service_running apache2 && ! service_running httpd; then
        VALIDATION_WARNINGS+=("Apache is not running")
        echo -e "${YELLOW}⚠️  Apache is not running${NC}"
        echo -e "${YELLOW}Start with: sudo systemctl start apache2${NC}"
        add_rollback_action "sudo systemctl stop apache2"
    else
        echo -e "${GREEN}✅ Apache is running${NC}"
    fi
    
    # Check PHP module
    local apache_modules=""
    if command_exists apache2ctl; then
        apache_modules=$(apache2ctl -M 2>/dev/null || true)
    elif command_exists httpd; then
        apache_modules=$(httpd -M 2>/dev/null || true)
    fi
    
    if echo "$apache_modules" | grep -q "php${PHP_VERSION//./_}_module"; then
        echo -e "${GREEN}✅ PHP ${PHP_VERSION} module is loaded${NC}"
    elif echo "$apache_modules" | grep -q "php.*_module"; then
        VALIDATION_WARNINGS+=("Different PHP version module loaded in Apache")
        echo -e "${YELLOW}⚠️  Different PHP version module loaded${NC}"
    else
        VALIDATION_ERRORS+=("PHP module not loaded in Apache")
        echo -e "${RED}❌ PHP module not loaded in Apache${NC}"
        echo -e "${YELLOW}Enable with: sudo a2enmod php${PHP_VERSION}${NC}"
        return 1
    fi
    
    # Check required Apache modules
    local required_modules=("rewrite" "headers" "ssl")
    for mod in "${required_modules[@]}"; do
        if echo "$apache_modules" | grep -q "${mod}_module"; then
            echo -e "${GREEN}✅ mod_$mod is enabled${NC}"
        else
            VALIDATION_WARNINGS+=("Apache module mod_$mod is not enabled")
            echo -e "${YELLOW}⚠️  mod_$mod is not enabled${NC}"
            echo -e "${YELLOW}Enable with: sudo a2enmod $mod${NC}"
        fi
    done
    
    return 0
}

# Function to validate Apache PHP-FPM
validate_apache_php_fpm() {
    echo -e "\n${YELLOW}🌐 Validating Apache PHP-FPM Configuration${NC}"
    echo "=============================================="
    
    # Check if Apache is installed
    if ! command_exists apache2 && ! command_exists httpd; then
        VALIDATION_ERRORS+=("Apache is not installed")
        echo -e "${RED}❌ Apache is not installed${NC}"
        return 1
    fi
    
    # Check if PHP-FPM is installed
    if ! command_exists "php-fpm${PHP_VERSION}" && ! command_exists "php${PHP_VERSION}-fpm"; then
        VALIDATION_ERRORS+=("PHP-FPM ${PHP_VERSION} is not installed")
        echo -e "${RED}❌ PHP-FPM ${PHP_VERSION} is not installed${NC}"
        echo -e "${YELLOW}Install with: sudo apt install php${PHP_VERSION}-fpm${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ PHP-FPM ${PHP_VERSION} is installed${NC}"
    
    # Check if PHP-FPM is running
    if service_running "php${PHP_VERSION}-fpm"; then
        echo -e "${GREEN}✅ PHP-FPM ${PHP_VERSION} is running${NC}"
    else
        VALIDATION_WARNINGS+=("PHP-FPM ${PHP_VERSION} is not running")
        echo -e "${YELLOW}⚠️  PHP-FPM ${PHP_VERSION} is not running${NC}"
        echo -e "${YELLOW}Start with: sudo systemctl start php${PHP_VERSION}-fpm${NC}"
        add_rollback_action "sudo systemctl stop php${PHP_VERSION}-fpm"
    fi
    
    # Check Apache modules for PHP-FPM
    local apache_modules=""
    if command_exists apache2ctl; then
        apache_modules=$(apache2ctl -M 2>/dev/null || true)
    fi
    
    local required_fpm_modules=("proxy" "proxy_fcgi" "setenvif" "rewrite")
    for mod in "${required_fpm_modules[@]}"; do
        if echo "$apache_modules" | grep -q "${mod}_module"; then
            echo -e "${GREEN}✅ mod_$mod is enabled${NC}"
        else
            VALIDATION_ERRORS+=("Required Apache module mod_$mod is not enabled")
            echo -e "${RED}❌ mod_$mod is not enabled${NC}"
            echo -e "${YELLOW}Enable with: sudo a2enmod $mod${NC}"
        fi
    done
    
    # Check PHP-FPM socket/port
    local fpm_socket="/run/php/php${PHP_VERSION}-fpm.sock"
    if [ -S "$fpm_socket" ]; then
        echo -e "${GREEN}✅ PHP-FPM socket is available: $fpm_socket${NC}"
    else
        VALIDATION_WARNINGS+=("PHP-FPM socket not found: $fpm_socket")
        echo -e "${YELLOW}⚠️  PHP-FPM socket not found: $fpm_socket${NC}"
    fi
    
    return 0
}

# Function to validate Nginx PHP-FPM
validate_nginx_php_fpm() {
    echo -e "\n${YELLOW}🌐 Validating Nginx PHP-FPM Configuration${NC}"
    echo "==========================================="
    
    # Check if Nginx is installed
    if ! command_exists nginx; then
        VALIDATION_ERRORS+=("Nginx is not installed")
        echo -e "${RED}❌ Nginx is not installed${NC}"
        echo -e "${YELLOW}Install with: sudo apt install nginx${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Nginx is installed${NC}"
    
    # Check if Nginx is running
    if service_running nginx; then
        echo -e "${GREEN}✅ Nginx is running${NC}"
    else
        VALIDATION_WARNINGS+=("Nginx is not running")
        echo -e "${YELLOW}⚠️  Nginx is not running${NC}"
        echo -e "${YELLOW}Start with: sudo systemctl start nginx${NC}"
        add_rollback_action "sudo systemctl stop nginx"
    fi
    
    # Check if PHP-FPM is installed
    if ! command_exists "php-fpm${PHP_VERSION}" && ! command_exists "php${PHP_VERSION}-fpm"; then
        VALIDATION_ERRORS+=("PHP-FPM ${PHP_VERSION} is not installed")
        echo -e "${RED}❌ PHP-FPM ${PHP_VERSION} is not installed${NC}"
        echo -e "${YELLOW}Install with: sudo apt install php${PHP_VERSION}-fpm${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ PHP-FPM ${PHP_VERSION} is installed${NC}"
    
    # Check if PHP-FPM is running
    if service_running "php${PHP_VERSION}-fpm"; then
        echo -e "${GREEN}✅ PHP-FPM ${PHP_VERSION} is running${NC}"
    else
        VALIDATION_WARNINGS+=("PHP-FPM ${PHP_VERSION} is not running")
        echo -e "${YELLOW}⚠️  PHP-FPM ${PHP_VERSION} is not running${NC}"
        echo -e "${YELLOW}Start with: sudo systemctl start php${PHP_VERSION}-fpm${NC}"
        add_rollback_action "sudo systemctl stop php${PHP_VERSION}-fpm"
    fi
    
    # Check PHP-FPM socket
    local fpm_socket="/run/php/php${PHP_VERSION}-fpm.sock"
    if [ -S "$fpm_socket" ]; then
        echo -e "${GREEN}✅ PHP-FPM socket is available: $fpm_socket${NC}"
    else
        VALIDATION_WARNINGS+=("PHP-FPM socket not found: $fpm_socket")
        echo -e "${YELLOW}⚠️  PHP-FPM socket not found: $fpm_socket${NC}"
    fi
    
    # Test Nginx configuration
    if nginx -t >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    else
        VALIDATION_ERRORS+=("Nginx configuration has errors")
        echo -e "${RED}❌ Nginx configuration has errors${NC}"
        echo -e "${YELLOW}Check with: sudo nginx -t${NC}"
    fi

    return 0
}

# Function to validate PHP CLI deployment
validate_php_cli() {
    echo -e "\n${YELLOW}🐘 Validating PHP CLI Configuration${NC}"
    echo "====================================="

    # PHP CLI is already validated in validate_php_version
    echo -e "${GREEN}✅ PHP CLI deployment uses built-in server${NC}"
    echo -e "${GREEN}✅ No additional web server configuration required${NC}"

    # Check if ports are available
    local ports=(8080 8081 8082 8083 8084)
    for port in "${ports[@]}"; do
        if netstat -tuln 2>/dev/null | grep -q ":$port " || ss -tuln 2>/dev/null | grep -q ":$port "; then
            VALIDATION_WARNINGS+=("Port $port is already in use")
            echo -e "${YELLOW}⚠️  Port $port is already in use${NC}"
        else
            echo -e "${GREEN}✅ Port $port is available${NC}"
        fi
    done

    return 0
}

# Function to validate Composer
validate_composer() {
    echo -e "\n${YELLOW}📦 Validating Composer${NC}"
    echo "======================="

    if ! command_exists composer; then
        VALIDATION_ERRORS+=("Composer is not installed")
        echo -e "${RED}❌ Composer is not installed${NC}"
        echo -e "${YELLOW}Install from: https://getcomposer.org/${NC}"
        return 1
    fi

    echo -e "${GREEN}✅ Composer is installed${NC}"
    composer --version

    # Check if composer is up to date
    local composer_version=$(composer --version --no-ansi | grep -oP '\d+\.\d+\.\d+' | head -1)
    echo -e "${GREEN}✅ Composer version: $composer_version${NC}"

    return 0
}

# Function to validate supporting services
validate_supporting_services() {
    echo -e "\n${YELLOW}🐳 Validating Supporting Services${NC}"
    echo "=================================="

    # Check Docker
    if ! command_exists docker; then
        VALIDATION_ERRORS+=("Docker is not installed")
        echo -e "${RED}❌ Docker is not installed${NC}"
        return 1
    fi

    echo -e "${GREEN}✅ Docker is installed${NC}"

    # Check Docker Compose
    if ! docker compose version >/dev/null 2>&1 && ! command_exists docker-compose; then
        VALIDATION_ERRORS+=("Docker Compose is not installed")
        echo -e "${RED}❌ Docker Compose is not installed${NC}"
        return 1
    fi

    echo -e "${GREEN}✅ Docker Compose is available${NC}"

    # Check if Docker daemon is running
    if ! docker info >/dev/null 2>&1; then
        VALIDATION_ERRORS+=("Docker daemon is not running")
        echo -e "${RED}❌ Docker daemon is not running${NC}"
        echo -e "${YELLOW}Start with: sudo systemctl start docker${NC}"
        return 1
    fi

    echo -e "${GREEN}✅ Docker daemon is running${NC}"

    # Check if services are running
    if [ -f "docker-compose.services.yml" ]; then
        local services_status=$(docker compose -f docker-compose.services.yml ps --format "table {{.Service}}\t{{.State}}" 2>/dev/null || echo "")
        if [ -n "$services_status" ]; then
            echo -e "${GREEN}✅ Supporting services configuration found${NC}"
            echo "$services_status"
        else
            echo -e "${YELLOW}⚠️  Supporting services not running${NC}"
            echo -e "${YELLOW}Start with: make start-services${NC}"
        fi
    fi

    return 0
}

# Function to create rollback script
create_rollback_script() {
    if [ ${#ROLLBACK_ACTIONS[@]} -gt 0 ]; then
        echo -e "\n${YELLOW}📝 Creating rollback script${NC}"

        cat > "scripts/rollback-deployment.sh" << 'EOF'
#!/bin/bash

# APM PHP Examples - Deployment Rollback Script
# This script rolls back changes made during deployment validation

set -e

echo "🔄 Rolling back deployment changes..."

EOF

        for action in "${ROLLBACK_ACTIONS[@]}"; do
            echo "echo \"Executing: $action\"" >> "scripts/rollback-deployment.sh"
            echo "$action" >> "scripts/rollback-deployment.sh"
        done

        echo 'echo "✅ Rollback completed"' >> "scripts/rollback-deployment.sh"
        chmod +x "scripts/rollback-deployment.sh"

        echo -e "${GREEN}✅ Rollback script created: scripts/rollback-deployment.sh${NC}"
    fi
}

# Function to save validation results
save_validation_results() {
    local results_file="logs/validation-results.json"

    cat > "$results_file" << EOF
{
    "timestamp": "$(date -Iseconds)",
    "deployment_type": "$DEPLOYMENT_TYPE",
    "php_version": "$PHP_VERSION",
    "network_interface": "$NETWORK_INTERFACE",
    "validation_passed": $([[ "$VALIDATION_PASSED" == "true" ]] && echo "true" || echo "false"),
    "errors": [$(printf '"%s",' "${VALIDATION_ERRORS[@]}" | sed 's/,$//')],
    "warnings": [$(printf '"%s",' "${VALIDATION_WARNINGS[@]}" | sed 's/,$//')],
    "rollback_actions": [$(printf '"%s",' "${ROLLBACK_ACTIONS[@]}" | sed 's/,$//')]
}
EOF

    echo -e "${GREEN}✅ Validation results saved: $results_file${NC}"
}

# Main validation logic
main() {
    echo -e "${BLUE}Starting deployment validation...${NC}"

    # Always validate PHP and Composer
    validate_php_version || VALIDATION_PASSED=false
    validate_php_extensions || VALIDATION_PASSED=false
    validate_composer || VALIDATION_PASSED=false
    validate_supporting_services || VALIDATION_PASSED=false

    # Validate based on deployment type
    case "$DEPLOYMENT_TYPE" in
        "local-apache-mod-php")
            validate_apache_mod_php || VALIDATION_PASSED=false
            ;;
        "local-apache-fpm")
            validate_apache_php_fpm || VALIDATION_PASSED=false
            ;;
        "local-nginx-fpm")
            validate_nginx_php_fpm || VALIDATION_PASSED=false
            ;;
        "local-php-cli")
            validate_php_cli || VALIDATION_PASSED=false
            ;;
        *)
            VALIDATION_ERRORS+=("Unknown deployment type: $DEPLOYMENT_TYPE")
            VALIDATION_PASSED=false
            ;;
    esac

    # Create rollback script if needed
    create_rollback_script

    # Save validation results
    save_validation_results

    # Display final results
    echo -e "\n${BLUE}${BOLD}📊 Validation Summary${NC}"
    echo "======================"

    if [ ${#VALIDATION_ERRORS[@]} -gt 0 ]; then
        echo -e "\n${RED}❌ Validation Errors (${#VALIDATION_ERRORS[@]}):${NC}"
        for error in "${VALIDATION_ERRORS[@]}"; do
            echo -e "${RED}  • $error${NC}"
        done
    fi

    if [ ${#VALIDATION_WARNINGS[@]} -gt 0 ]; then
        echo -e "\n${YELLOW}⚠️  Validation Warnings (${#VALIDATION_WARNINGS[@]}):${NC}"
        for warning in "${VALIDATION_WARNINGS[@]}"; do
            echo -e "${YELLOW}  • $warning${NC}"
        done
    fi

    echo ""
    if [ "$VALIDATION_PASSED" == "true" ]; then
        echo -e "${GREEN}${BOLD}🎉 Deployment validation PASSED!${NC}"
        echo -e "${GREEN}✅ Your system is ready for deployment${NC}"
        echo ""
        echo -e "${BLUE}Next steps:${NC}"
        echo "1. Start supporting services: make start-services"
        echo "2. Deploy applications: make deploy"
        echo "3. Test deployment: make test"

        log_validation "SUCCESS" "Deployment validation passed"
        exit 0
    else
        echo -e "${RED}${BOLD}❌ Deployment validation FAILED!${NC}"
        echo -e "${RED}✗ Please fix the errors above before proceeding${NC}"
        echo ""
        echo -e "${BLUE}Troubleshooting:${NC}"
        echo "• Check the validation log: $VALIDATION_LOG"
        echo "• Review error messages and install missing components"
        echo "• Run validation again after fixing issues"

        if [ ${#ROLLBACK_ACTIONS[@]} -gt 0 ]; then
            echo "• Use rollback script if needed: ./scripts/rollback-deployment.sh"
        fi

        log_validation "FAILURE" "Deployment validation failed with ${#VALIDATION_ERRORS[@]} errors"
        exit 1
    fi
}

# Run main function
main "$@"
