#!/bin/bash

# APM PHP Examples - PHP Version Management
# Supports dynamic PHP version switching (8.1-8.4)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SUPPORTED_PHP_VERSIONS=("8.1" "8.2" "8.3" "8.4")
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "pdo_pgsql" "mbstring" "gd" "zip" "redis" "intl" "curl" "json")

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

# Function to detect OS
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if command -v apt >/dev/null 2>&1; then
            echo "ubuntu"
        elif command -v yum >/dev/null 2>&1; then
            echo "centos"
        elif command -v dnf >/dev/null 2>&1; then
            echo "fedora"
        else
            echo "linux"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    else
        echo "unknown"
    fi
}

# Function to check if PHP version is installed
check_php_installed() {
    local version=$1
    
    # Check versioned command
    if command -v "php$version" >/dev/null 2>&1; then
        return 0
    fi
    
    # Check default php command
    if command -v php >/dev/null 2>&1; then
        local current_version=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)
        if [[ "$current_version" == "$version" ]]; then
            return 0
        fi
    fi
    
    return 1
}

# Function to check PHP extensions
check_php_extensions() {
    local php_cmd=$1
    local missing_extensions=()
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! $php_cmd -m | grep -iq "^$ext$"; then
            missing_extensions+=("$ext")
        fi
    done
    
    if [[ ${#missing_extensions[@]} -eq 0 ]]; then
        return 0
    else
        echo "${missing_extensions[*]}"
        return 1
    fi
}

# Function to provide installation instructions
provide_installation_instructions() {
    local version=$1
    local os=$2
    
    print_status "info" "Installation instructions for PHP $version:"
    echo
    
    case $os in
        "ubuntu")
            print_status "info" "Ubuntu/Debian:"
            echo "  sudo apt update"
            echo "  sudo apt install software-properties-common"
            echo "  sudo add-apt-repository ppa:ondrej/php"
            echo "  sudo apt update"
            echo "  sudo apt install php$version php$version-cli php$version-fpm"
            echo "  sudo apt install php$version-{mysql,pgsql,mbstring,gd,zip,redis,intl,curl}"
            ;;
        "centos"|"fedora")
            print_status "info" "CentOS/RHEL/Fedora:"
            echo "  sudo yum install epel-release"
            echo "  sudo yum install https://rpms.remirepo.net/enterprise/remi-release-8.rpm"
            echo "  sudo yum module enable php:remi-$version"
            echo "  sudo yum install php php-cli php-fpm"
            echo "  sudo yum install php-{mysqlnd,pgsql,mbstring,gd,zip,redis,intl,curl}"
            ;;
        "macos")
            print_status "info" "macOS (using Homebrew):"
            echo "  brew tap shivammathur/php"
            echo "  brew install php@$version"
            echo "  brew link php@$version --force"
            echo "  brew install redis"
            ;;
        *)
            print_status "info" "Please install PHP $version using your system's package manager"
            print_status "info" "Required extensions: ${REQUIRED_EXTENSIONS[*]}"
            ;;
    esac
    echo
}

# Function to list available PHP versions
list_php_versions() {
    print_status "header" "Available PHP Versions:"
    echo
    
    for version in "${SUPPORTED_PHP_VERSIONS[@]}"; do
        if check_php_installed "$version"; then
            local php_cmd="php$version"
            if ! command -v "$php_cmd" >/dev/null 2>&1; then
                php_cmd="php"
            fi
            
            local current_version=$($php_cmd -r 'echo PHP_VERSION;' 2>/dev/null)
            print_status "success" "PHP $version ($current_version) - INSTALLED"
            
            # Check extensions
            if missing_ext=$(check_php_extensions "$php_cmd"); then
                print_status "success" "  All required extensions available"
            else
                print_status "warning" "  Missing extensions: $missing_ext"
            fi
        else
            print_status "error" "PHP $version - NOT INSTALLED"
        fi
        echo
    done
}

# Function to switch PHP version
switch_php_version() {
    local target_version=$1
    
    if ! check_php_installed "$target_version"; then
        print_status "error" "PHP $target_version is not installed"
        provide_installation_instructions "$target_version" "$(detect_os)"
        return 1
    fi
    
    local php_cmd="php$target_version"
    if ! command -v "$php_cmd" >/dev/null 2>&1; then
        php_cmd="php"
    fi
    
    # Check extensions
    if missing_ext=$(check_php_extensions "$php_cmd"); then
        print_status "success" "PHP $target_version is ready to use"
        print_status "info" "Use: export PHP_VERSION=$target_version"
        print_status "info" "Or run scripts with: PHP_VERSION=$target_version ./script.sh"
    else
        print_status "warning" "PHP $target_version is installed but missing extensions: $missing_ext"
        provide_installation_instructions "$target_version" "$(detect_os)"
        return 1
    fi
}

# Function to install missing extensions
install_extensions() {
    local version=$1
    local os=$(detect_os)
    
    print_status "info" "Installing missing extensions for PHP $version..."
    
    case $os in
        "ubuntu")
            sudo apt update
            sudo apt install -y php$version-{mysql,pgsql,mbstring,gd,zip,redis,intl,curl}
            ;;
        "centos"|"fedora")
            sudo yum install -y php-{mysqlnd,pgsql,mbstring,gd,zip,redis,intl,curl}
            ;;
        "macos")
            print_status "info" "Extensions should be included with Homebrew PHP installation"
            print_status "info" "If Redis extension is missing, install with: pecl install redis"
            ;;
        *)
            print_status "error" "Automatic extension installation not supported for this OS"
            provide_installation_instructions "$version" "$os"
            return 1
            ;;
    esac
    
    print_status "success" "Extension installation completed"
}

# Function to validate PHP setup
validate_php_setup() {
    local version=$1
    
    if ! check_php_installed "$version"; then
        print_status "error" "PHP $version is not installed"
        return 1
    fi
    
    local php_cmd="php$version"
    if ! command -v "$php_cmd" >/dev/null 2>&1; then
        php_cmd="php"
    fi
    
    print_status "info" "Validating PHP $version setup..."
    
    # Check PHP version
    local actual_version=$($php_cmd -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)
    if [[ "$actual_version" == "$version" ]]; then
        print_status "success" "PHP version: $actual_version"
    else
        print_status "warning" "Expected PHP $version, found $actual_version"
    fi
    
    # Check extensions
    if missing_ext=$(check_php_extensions "$php_cmd"); then
        print_status "success" "All required extensions are available"
    else
        print_status "error" "Missing extensions: $missing_ext"
        return 1
    fi
    
    # Check Composer
    if command -v composer >/dev/null 2>&1; then
        print_status "success" "Composer is available"
    else
        print_status "warning" "Composer is not installed"
        print_status "info" "Install Composer: curl -sS https://getcomposer.org/installer | php"
    fi
    
    print_status "success" "PHP $version setup is valid"
    return 0
}

# Main function
main() {
    local action=${1:-"list"}
    local version=${2:-""}
    
    print_status "header" "APM PHP Examples - PHP Version Management"
    echo
    
    case $action in
        "list"|"ls")
            list_php_versions
            ;;
        "switch"|"use")
            if [[ -z "$version" ]]; then
                print_status "error" "Please specify a PHP version (8.1-8.4)"
                exit 1
            fi
            switch_php_version "$version"
            ;;
        "install-extensions")
            if [[ -z "$version" ]]; then
                print_status "error" "Please specify a PHP version (8.1-8.4)"
                exit 1
            fi
            install_extensions "$version"
            ;;
        "validate")
            if [[ -z "$version" ]]; then
                version="8.4"  # Default version
            fi
            validate_php_setup "$version"
            ;;
        "help"|"--help")
            echo "Usage: $0 [COMMAND] [VERSION]"
            echo
            echo "Commands:"
            echo "  list                     List all available PHP versions"
            echo "  switch VERSION           Switch to specific PHP version"
            echo "  install-extensions VER   Install missing extensions for PHP version"
            echo "  validate [VERSION]       Validate PHP setup (default: 8.4)"
            echo "  help                     Show this help message"
            echo
            echo "Supported PHP versions: ${SUPPORTED_PHP_VERSIONS[*]}"
            echo
            echo "Examples:"
            echo "  $0 list"
            echo "  $0 switch 8.3"
            echo "  $0 validate 8.2"
            echo "  $0 install-extensions 8.4"
            ;;
        *)
            print_status "error" "Unknown command: $action"
            print_status "info" "Use '$0 help' for usage information"
            exit 1
            ;;
    esac
}

# Run main function
main "$@"
