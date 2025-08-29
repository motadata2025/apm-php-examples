#!/bin/bash

# APM PHP Examples - Enhanced Application Startup
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

# Application configuration - Updated to 9000 range
declare -A APPLICATIONS=(
    ["simple-php"]="9080"
    ["laravel-app"]="9081"
    ["symfony-app"]="9082"
    ["slim-framework"]="9083"
    ["codeigniter-app"]="9084"
)

# PID file directory
PID_DIR="$PROJECT_ROOT/.pids"
mkdir -p "$PID_DIR"

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

# Function to detect PHP version
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
        for supported in "${SUPPORTED_PHP_VERSIONS[@]}"; do
            if [[ "$current_version" == "$supported" ]]; then
                echo "$current_version"
                return 0
            fi
        done
    fi
    
    return 1
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

# Function to start application with CLI
start_application_cli() {
    local app_name=$1
    local port=$2
    local php_cmd=$3
    
    cd "$PROJECT_ROOT/$app_name"
    
    # Install dependencies if needed
    if [[ -f "composer.json" && ! -d "vendor" ]]; then
        print_status "info" "Installing dependencies for $app_name..."
        composer install --no-dev --optimize-autoloader
    fi
    
    # Start application based on framework
    case $app_name in
        "laravel-app")
            # Try Laravel artisan serve first
            if $php_cmd artisan --version >/dev/null 2>&1; then
                print_status "info" "Starting Laravel with artisan serve..."
                nohup $php_cmd artisan serve --host=$NETWORK_IP --port=$port > "$PID_DIR/$app_name.log" 2>&1 &
            else
                print_status "warning" "Laravel artisan not working, using PHP built-in server..."
                nohup $php_cmd -S $NETWORK_IP:$port -t public > "$PID_DIR/$app_name.log" 2>&1 &
            fi
            ;;
        "symfony-app")
            # Try Symfony console first
            if command -v symfony >/dev/null 2>&1; then
                print_status "info" "Starting Symfony with symfony server..."
                nohup symfony server:start --host=$NETWORK_IP --port=$port --no-tls > "$PID_DIR/$app_name.log" 2>&1 &
            else
                print_status "info" "Starting Symfony with PHP built-in server..."
                nohup $php_cmd -S $NETWORK_IP:$port -t public > "$PID_DIR/$app_name.log" 2>&1 &
            fi
            ;;
        *)
            print_status "info" "Starting $app_name with PHP built-in server..."
            nohup $php_cmd -S $NETWORK_IP:$port -t public > "$PID_DIR/$app_name.log" 2>&1 &
            ;;
    esac
    
    local pid=$!
    echo $pid > "$PID_DIR/$app_name.pid"
    
    # Wait a moment and check if process is still running
    sleep 2
    if kill -0 $pid 2>/dev/null; then
        print_status "success" "$app_name started (PID: $pid) on http://$NETWORK_IP:$port"
    else
        print_status "error" "$app_name failed to start"
        if [[ -f "$PID_DIR/$app_name.log" ]]; then
            print_status "info" "Log output:"
            tail -5 "$PID_DIR/$app_name.log"
        fi
        return 1
    fi
    
    cd "$PROJECT_ROOT"
}

# Function to start application with FPM
start_application_fpm() {
    local app_name=$1
    local port=$2
    local php_cmd=$3
    local deployment_type=$4
    
    print_status "info" "$app_name: FPM deployment requires web server configuration"
    print_status "info" "Please configure your web server to serve $PROJECT_ROOT/$app_name"
    print_status "info" "For now, falling back to CLI mode..."
    
    # Fall back to CLI
    start_application_cli "$app_name" "$port" "$php_cmd"
}

# Function to stop application
stop_application() {
    local app_name=$1
    
    if [[ -f "$PID_DIR/$app_name.pid" ]]; then
        local pid=$(cat "$PID_DIR/$app_name.pid")
        if kill -0 $pid 2>/dev/null; then
            kill $pid
            print_status "success" "$app_name stopped (PID: $pid)"
        else
            print_status "warning" "$app_name was not running"
        fi
        rm -f "$PID_DIR/$app_name.pid"
    else
        print_status "warning" "No PID file found for $app_name"
    fi
}

# Function to check application status
check_application_status() {
    local app_name=$1
    local port=$2
    
    if [[ -f "$PID_DIR/$app_name.pid" ]]; then
        local pid=$(cat "$PID_DIR/$app_name.pid")
        if kill -0 $pid 2>/dev/null; then
            # Check HTTP response
            local http_code=$(curl -s -w "%{http_code}" -o /dev/null --connect-timeout 5 "http://$NETWORK_IP:$port/" 2>/dev/null || echo "000")
            if [[ "$http_code" =~ ^[2-3][0-9][0-9]$ ]]; then
                print_status "success" "$app_name is running (PID: $pid, HTTP: $http_code)"
            else
                print_status "warning" "$app_name is running but not responding (PID: $pid, HTTP: $http_code)"
            fi
        else
            print_status "error" "$app_name is not running (stale PID file)"
            rm -f "$PID_DIR/$app_name.pid"
        fi
    else
        print_status "error" "$app_name is not running"
    fi
}

# Function to start all applications
start_all_applications() {
    local php_cmd=$1
    local deployment_type=$2
    
    print_status "header" "Starting all APM PHP applications..."
    print_status "info" "Using PHP $PHP_VERSION with $deployment_type deployment"
    echo
    
    # Check supporting services
    if ! docker compose ps | grep -q "Up"; then
        print_status "info" "Starting supporting services..."
        make start-services
        sleep 5
    fi
    
    # Start each application
    for app_name in "${!APPLICATIONS[@]}"; do
        local port=${APPLICATIONS[$app_name]}
        
        case $deployment_type in
            "cli")
                start_application_cli "$app_name" "$port" "$php_cmd"
                ;;
            "apache-fpm"|"nginx-fpm")
                start_application_fpm "$app_name" "$port" "$php_cmd" "$deployment_type"
                ;;
        esac
        echo
    done
    
    print_status "success" "All applications started!"
    echo
    print_status "info" "Access your applications at:"
    for app_name in "${!APPLICATIONS[@]}"; do
        local port=${APPLICATIONS[$app_name]}
        echo "  $app_name: http://$NETWORK_IP:$port"
    done
    echo
    print_status "info" "To stop all applications, run: $0 stop"
}

# Function to stop all applications
stop_all_applications() {
    print_status "header" "Stopping all APM PHP applications..."
    
    for app_name in "${!APPLICATIONS[@]}"; do
        stop_application "$app_name"
    done
    
    print_status "success" "All applications stopped!"
}

# Function to show status of all applications
show_status() {
    print_status "header" "APM PHP Applications Status"
    echo
    
    for app_name in "${!APPLICATIONS[@]}"; do
        local port=${APPLICATIONS[$app_name]}
        check_application_status "$app_name" "$port"
    done
}

# Main function
main() {
    local action=${1:-"start"}
    
    case $action in
        "start")
            # Detect PHP version
            if ! DETECTED_PHP_VERSION=$(detect_php_version "$PHP_VERSION"); then
                print_status "error" "No supported PHP version found (8.1-8.4)"
                print_status "info" "Use: ./scripts/manage-php-version.sh list"
                exit 1
            fi
            
            PHP_VERSION="$DETECTED_PHP_VERSION"
            PHP_CMD="php$PHP_VERSION"
            
            # Fallback to default php if versioned command doesn't exist
            if ! command -v "$PHP_CMD" >/dev/null 2>&1; then
                PHP_CMD="php"
            fi
            
            # Detect deployment type
            DETECTED_DEPLOYMENT_TYPE=$(detect_deployment_type)
            
            start_all_applications "$PHP_CMD" "$DETECTED_DEPLOYMENT_TYPE"
            ;;
        "stop")
            stop_all_applications
            ;;
        "status")
            show_status
            ;;
        "restart")
            stop_all_applications
            sleep 2
            main "start"
            ;;
        "help"|"--help")
            echo "Usage: $0 [COMMAND]"
            echo
            echo "Commands:"
            echo "  start      Start all applications (default)"
            echo "  stop       Stop all applications"
            echo "  status     Show application status"
            echo "  restart    Restart all applications"
            echo "  help       Show this help message"
            echo
            echo "Environment variables:"
            echo "  PHP_VERSION      PHP version to use (8.1-8.4, default: $DEFAULT_PHP_VERSION)"
            echo "  DEPLOYMENT_TYPE  Deployment type (cli|apache-fpm|nginx-fpm|auto, default: auto)"
            echo
            echo "Examples:"
            echo "  $0 start"
            echo "  PHP_VERSION=8.3 $0 start"
            echo "  DEPLOYMENT_TYPE=cli $0 start"
            ;;
        *)
            print_status "error" "Unknown command: $action"
            print_status "info" "Use '$0 help' for usage information"
            exit 1
            ;;
    esac
}

# Parse command line arguments for PHP version and deployment type
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
        *)
            # Pass through other arguments to main function
            break
            ;;
    esac
done

# Run main function
main "$@"
