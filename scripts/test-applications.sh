#!/bin/bash

# APM PHP Examples - Application Testing & Validation
# Supports multiple deployment types and dynamic PHP version management

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
NETWORK_IP=$(ip route get 1.1.1.1 | grep -oP 'src \K\S+' 2>/dev/null || echo "127.0.0.1")

# Default configuration
DEFAULT_PHP_VERSION="8.4"
SUPPORTED_PHP_VERSIONS=("8.1" "8.2" "8.3" "8.4")
DEPLOYMENT_TYPE="${DEPLOYMENT_TYPE:-auto}"
PHP_VERSION="${PHP_VERSION:-$DEFAULT_PHP_VERSION}"

# Application configuration
declare -A APPLICATIONS=(
    ["simple-php"]="8080"
    ["laravel-app"]="8081"
    ["symfony-app"]="8082"
    ["slim-framework"]="8083"
    ["codeigniter-app"]="8084"
)

# Deployment types
declare -A DEPLOYMENT_TYPES=(
    ["cli"]="PHP CLI with built-in server"
    ["apache-fpm"]="Apache with PHP-FPM"
    ["nginx-fpm"]="Nginx with PHP-FPM"
    ["auto"]="Auto-detect best available"
)

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "info") echo -e "${BLUE}ℹ️  $message${NC}" ;;
        "success") echo -e "${GREEN}✅ $message${NC}" ;;
        "warning") echo -e "${YELLOW}⚠️  $message${NC}" ;;
        "error") echo -e "${RED}❌ $message${NC}" ;;
        "header") echo -e "${BLUE}🚀 $message${NC}" ;;
    esac
}

# Function to check if PHP version is supported
check_php_version() {
    local version=$1
    for supported in "${SUPPORTED_PHP_VERSIONS[@]}"; do
        if [[ "$version" == "$supported" ]]; then
            return 0
        fi
    done
    return 1
}

# Function to detect available PHP version
detect_php_version() {
    local requested_version=$1
    
    # Check if specific version is requested and available
    if [[ -n "$requested_version" ]]; then
        if command -v "php$requested_version" >/dev/null 2>&1; then
            echo "$requested_version"
            return 0
        elif [[ "$requested_version" == "$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)" ]]; then
            echo "$requested_version"
            return 0
        else
            print_status "error" "PHP $requested_version not found"
            return 1
        fi
    fi
    
    # Auto-detect available PHP version
    for version in "${SUPPORTED_PHP_VERSIONS[@]}"; do
        if command -v "php$version" >/dev/null 2>&1; then
            echo "$version"
            return 0
        fi
    done
    
    # Check default php command
    if command -v php >/dev/null 2>&1; then
        local current_version=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)
        if check_php_version "$current_version"; then
            echo "$current_version"
            return 0
        fi
    fi
    
    return 1
}

# Function to check PHP extensions
check_php_extensions() {
    local php_cmd=$1
    local required_extensions=("pdo" "pdo_mysql" "pdo_pgsql" "mbstring" "gd" "zip" "redis" "intl" "curl" "json")
    local missing_extensions=()
    
    print_status "info" "Checking PHP extensions for $php_cmd..."
    
    for ext in "${required_extensions[@]}"; do
        if ! $php_cmd -m | grep -iq "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done
    
    if [[ ${#missing_extensions[@]} -eq 0 ]]; then
        print_status "success" "All required PHP extensions are available"
        return 0
    else
        print_status "warning" "Missing PHP extensions: ${missing_extensions[*]}"
        print_status "info" "Please install missing extensions:"
        print_status "info" "  Ubuntu/Debian: sudo apt install php$PHP_VERSION-{${missing_extensions[*]// /,}}"
        print_status "info" "  CentOS/RHEL: sudo yum install php$PHP_VERSION-{${missing_extensions[*]// /,}}"
        print_status "info" "  macOS: brew install php@$PHP_VERSION && brew install php-{${missing_extensions[*]// /,}}"
        return 1
    fi
}

# Function to detect deployment type
detect_deployment_type() {
    if [[ "$DEPLOYMENT_TYPE" != "auto" ]]; then
        echo "$DEPLOYMENT_TYPE"
        return 0
    fi
    
    # Check for Apache with PHP-FPM
    if command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1; then
        if command -v php-fpm >/dev/null 2>&1 || systemctl is-active --quiet php*-fpm 2>/dev/null; then
            echo "apache-fpm"
            return 0
        fi
    fi
    
    # Check for Nginx with PHP-FPM
    if command -v nginx >/dev/null 2>&1; then
        if command -v php-fpm >/dev/null 2>&1 || systemctl is-active --quiet php*-fpm 2>/dev/null; then
            echo "nginx-fpm"
            return 0
        fi
    fi
    
    # Default to CLI
    echo "cli"
    return 0
}

# Function to check supporting services
check_supporting_services() {
    print_status "info" "Checking supporting services..."
    
    local services_ok=true
    
    # Check MySQL
    if docker compose ps mysql 2>/dev/null | grep -q "Up"; then
        print_status "success" "MySQL service is running"
    else
        print_status "error" "MySQL service is not running"
        services_ok=false
    fi
    
    # Check PostgreSQL
    if docker compose ps postgres 2>/dev/null | grep -q "Up"; then
        print_status "success" "PostgreSQL service is running"
    else
        print_status "error" "PostgreSQL service is not running"
        services_ok=false
    fi
    
    # Check Redis
    if docker compose ps redis 2>/dev/null | grep -q "Up"; then
        print_status "success" "Redis service is running"
    else
        print_status "error" "Redis service is not running"
        services_ok=false
    fi
    
    if [[ "$services_ok" == "false" ]]; then
        print_status "info" "Starting supporting services..."
        make start-services
        sleep 10
    fi
    
    return 0
}

# Function to test database connections
test_database_connections() {
    local php_cmd=$1
    print_status "info" "Testing database connections..."
    
    # Test using shared utilities
    if [[ -f "$PROJECT_ROOT/shared/utils/DatabaseConnection.php" ]]; then
        local test_result=$($php_cmd -r "
            require_once '$PROJECT_ROOT/shared/utils/DatabaseConnection.php';
            try {
                \$results = \Shared\Utils\DatabaseConnection::testConnections();
                foreach (\$results as \$db => \$status) {
                    echo \"\$db: \$status\n\";
                }
            } catch (Exception \$e) {
                echo 'Error: ' . \$e->getMessage() . \"\n\";
            }
        " 2>/dev/null)
        
        if echo "$test_result" | grep -q "Connected"; then
            print_status "success" "Database connections working"
            echo "$test_result" | while read line; do
                if [[ -n "$line" ]]; then
                    print_status "info" "  $line"
                fi
            done
        else
            print_status "warning" "Some database connections failed"
            echo "$test_result"
        fi
    fi
}

# Main function
main() {
    print_status "header" "APM PHP Examples - Application Testing & Validation"
    echo
    
    # Detect PHP version
    print_status "info" "Detecting PHP version..."
    if ! DETECTED_PHP_VERSION=$(detect_php_version "$PHP_VERSION"); then
        print_status "error" "No supported PHP version found (8.1-8.4)"
        print_status "info" "Please install PHP 8.1, 8.2, 8.3, or 8.4"
        exit 1
    fi
    
    PHP_VERSION="$DETECTED_PHP_VERSION"
    PHP_CMD="php$PHP_VERSION"
    
    # Fallback to default php if versioned command doesn't exist
    if ! command -v "$PHP_CMD" >/dev/null 2>&1; then
        PHP_CMD="php"
    fi
    
    print_status "success" "Using PHP $PHP_VERSION ($PHP_CMD)"
    
    # Check PHP extensions
    if ! check_php_extensions "$PHP_CMD"; then
        print_status "error" "Required PHP extensions are missing"
        exit 1
    fi
    
    # Detect deployment type
    DETECTED_DEPLOYMENT_TYPE=$(detect_deployment_type)
    print_status "info" "Deployment type: ${DEPLOYMENT_TYPES[$DETECTED_DEPLOYMENT_TYPE]}"
    
    # Check supporting services
    check_supporting_services
    
    # Test database connections
    test_database_connections "$PHP_CMD"
    
    print_status "success" "Environment validation completed successfully!"
    print_status "info" "Ready to test applications with PHP $PHP_VERSION using $DETECTED_DEPLOYMENT_TYPE deployment"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --php-version)
            PHP_VERSION="$2"
            shift 2
            ;;
        --deployment-type)
            DEPLOYMENT_TYPE="$2"
            shift 2
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo "Options:"
            echo "  --php-version VERSION    Use specific PHP version (8.1-8.4)"
            echo "  --deployment-type TYPE   Use specific deployment type (cli|apache-fpm|nginx-fpm|auto)"
            echo "  --help                   Show this help message"
            echo
            echo "Environment variables:"
            echo "  PHP_VERSION              PHP version to use (default: $DEFAULT_PHP_VERSION)"
            echo "  DEPLOYMENT_TYPE          Deployment type (default: auto)"
            exit 0
            ;;
        *)
            print_status "error" "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Function to test individual application
test_application() {
    local app_name=$1
    local port=$2
    local php_cmd=$3
    local deployment_type=$4

    print_status "info" "Testing $app_name on port $port..."

    # Check if application directory exists
    if [[ ! -d "$PROJECT_ROOT/$app_name" ]]; then
        print_status "error" "Application directory not found: $app_name"
        return 1
    fi

    # Install dependencies if needed
    if [[ -f "$PROJECT_ROOT/$app_name/composer.json" && ! -d "$PROJECT_ROOT/$app_name/vendor" ]]; then
        print_status "info" "Installing dependencies for $app_name..."
        cd "$PROJECT_ROOT/$app_name"
        composer install --no-dev --optimize-autoloader
        cd "$PROJECT_ROOT"
    fi

    # Start application based on deployment type
    case $deployment_type in
        "cli")
            test_application_cli "$app_name" "$port" "$php_cmd"
            ;;
        "apache-fpm"|"nginx-fpm")
            test_application_fpm "$app_name" "$port" "$php_cmd" "$deployment_type"
            ;;
        *)
            print_status "error" "Unsupported deployment type: $deployment_type"
            return 1
            ;;
    esac
}

# Function to test application with CLI
test_application_cli() {
    local app_name=$1
    local port=$2
    local php_cmd=$3

    cd "$PROJECT_ROOT/$app_name"

    # Start application based on framework
    case $app_name in
        "laravel-app")
            # Test Laravel artisan command first
            if $php_cmd artisan --version >/dev/null 2>&1; then
                print_status "info" "Starting Laravel with artisan serve..."
                timeout 5s $php_cmd artisan serve --host=$NETWORK_IP --port=$port >/dev/null 2>&1 &
            else
                print_status "warning" "Laravel artisan not working, using PHP built-in server..."
                timeout 5s $php_cmd -S $NETWORK_IP:$port -t public >/dev/null 2>&1 &
            fi
            ;;
        "symfony-app")
            # Test Symfony console first
            if command -v symfony >/dev/null 2>&1; then
                print_status "info" "Starting Symfony with symfony server..."
                timeout 5s symfony server:start --host=$NETWORK_IP --port=$port --no-tls >/dev/null 2>&1 &
            else
                print_status "info" "Starting Symfony with PHP built-in server..."
                timeout 5s $php_cmd -S $NETWORK_IP:$port -t public >/dev/null 2>&1 &
            fi
            ;;
        *)
            print_status "info" "Starting $app_name with PHP built-in server..."
            timeout 5s $php_cmd -S $NETWORK_IP:$port -t public >/dev/null 2>&1 &
            ;;
    esac

    local pid=$!
    sleep 2

    # Test HTTP response
    local http_code=$(curl -s -w "%{http_code}" -o /dev/null --connect-timeout 5 "http://$NETWORK_IP:$port/" 2>/dev/null || echo "000")

    if [[ "$http_code" =~ ^[2-3][0-9][0-9]$ ]]; then
        print_status "success" "$app_name is responding (HTTP $http_code)"
    else
        print_status "warning" "$app_name returned HTTP $http_code"
    fi

    # Kill the test process
    kill $pid 2>/dev/null || true
    cd "$PROJECT_ROOT"
}

# Function to test application with FPM
test_application_fpm() {
    local app_name=$1
    local port=$2
    local php_cmd=$3
    local deployment_type=$4

    print_status "info" "$app_name: FPM testing requires web server configuration"
    print_status "info" "Please configure your web server to serve $PROJECT_ROOT/$app_name"

    # For now, fall back to CLI testing
    test_application_cli "$app_name" "$port" "$php_cmd"
}

# Function to run comprehensive tests
run_comprehensive_tests() {
    local php_cmd=$1
    local deployment_type=$2

    print_status "header" "Running comprehensive application tests..."
    echo

    # Test each application
    for app_name in "${!APPLICATIONS[@]}"; do
        local port=${APPLICATIONS[$app_name]}
        test_application "$app_name" "$port" "$php_cmd" "$deployment_type"
        echo
    done

    print_status "success" "Application testing completed!"
}

# Update main function to include comprehensive testing
main() {
    print_status "header" "APM PHP Examples - Application Testing & Validation"
    echo

    # Detect PHP version
    print_status "info" "Detecting PHP version..."
    if ! DETECTED_PHP_VERSION=$(detect_php_version "$PHP_VERSION"); then
        print_status "error" "No supported PHP version found (8.1-8.4)"
        print_status "info" "Please install PHP 8.1, 8.2, 8.3, or 8.4"
        exit 1
    fi

    PHP_VERSION="$DETECTED_PHP_VERSION"
    PHP_CMD="php$PHP_VERSION"

    # Fallback to default php if versioned command doesn't exist
    if ! command -v "$PHP_CMD" >/dev/null 2>&1; then
        PHP_CMD="php"
    fi

    print_status "success" "Using PHP $PHP_VERSION ($PHP_CMD)"

    # Check PHP extensions
    if ! check_php_extensions "$PHP_CMD"; then
        print_status "error" "Required PHP extensions are missing"
        exit 1
    fi

    # Detect deployment type
    DETECTED_DEPLOYMENT_TYPE=$(detect_deployment_type)
    print_status "info" "Deployment type: ${DEPLOYMENT_TYPES[$DETECTED_DEPLOYMENT_TYPE]}"

    # Check supporting services
    check_supporting_services

    # Test database connections
    test_database_connections "$PHP_CMD"

    echo
    print_status "success" "Environment validation completed successfully!"

    # Run comprehensive application tests
    run_comprehensive_tests "$PHP_CMD" "$DETECTED_DEPLOYMENT_TYPE"
}

# Run main function
main
