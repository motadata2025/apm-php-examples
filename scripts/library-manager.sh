#!/bin/bash

# APM PHP Library Manager
# Supports PHP version compilation and library installation
# Explicitly prevents Apache/Nginx installation

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
PHP_VERSIONS_DIR="$PROJECT_ROOT/.php-versions"
LIBRARIES_DIR="$PROJECT_ROOT/.libraries"

# Supported PHP versions
SUPPORTED_PHP_VERSIONS=("8.1" "8.2" "8.3" "8.4")

# Forbidden packages (Apache/Nginx)
FORBIDDEN_PACKAGES=("apache2" "nginx" "httpd" "apache" "nginx-full" "nginx-light" "nginx-extras")

# Create necessary directories
mkdir -p "$PHP_VERSIONS_DIR" "$LIBRARIES_DIR"

# Function to display help
show_help() {
    echo -e "${BLUE}APM PHP Library Manager${NC}"
    echo ""
    echo "Usage: $0 [COMMAND] [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  install-php VERSION     Install specific PHP version (8.1-8.4)"
    echo "  install-library LIB     Install PHP library for current application"
    echo "  list-php               List available PHP versions"
    echo "  list-libraries         List installed libraries"
    echo "  compile-app APP        Compile application with current PHP version"
    echo "  set-php-version VER    Set PHP version for application"
    echo "  check-dependencies     Check application dependencies"
    echo ""
    echo "Examples:"
    echo "  $0 install-php 8.4"
    echo "  $0 install-library guzzlehttp/guzzle"
    echo "  $0 compile-app symfony-app"
    echo "  $0 set-php-version 8.3"
}

# Function to check for forbidden packages
check_forbidden_packages() {
    local package="$1"
    for forbidden in "${FORBIDDEN_PACKAGES[@]}"; do
        if [[ "$package" == *"$forbidden"* ]]; then
            echo -e "${RED}❌ ERROR: Installation of $forbidden is not allowed!${NC}"
            echo -e "${YELLOW}This system is designed for PHP applications only.${NC}"
            echo -e "${YELLOW}Web servers (Apache/Nginx) should be managed separately.${NC}"
            exit 1
        fi
    done
}

# Function to install PHP version
install_php_version() {
    local version="$1"
    
    if [[ ! " ${SUPPORTED_PHP_VERSIONS[@]} " =~ " ${version} " ]]; then
        echo -e "${RED}❌ Unsupported PHP version: $version${NC}"
        echo -e "${YELLOW}Supported versions: ${SUPPORTED_PHP_VERSIONS[*]}${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}📦 Installing PHP $version...${NC}"
    
    # Check if already installed
    if command -v "php$version" &> /dev/null; then
        echo -e "${YELLOW}⚠️  PHP $version is already installed${NC}"
        return 0
    fi
    
    # Install PHP version using system package manager
    if command -v apt-get &> /dev/null; then
        echo -e "${BLUE}Using apt-get to install PHP $version...${NC}"
        sudo apt-get update
        sudo apt-get install -y "php$version" "php$version-cli" "php$version-fpm" \
            "php$version-mysql" "php$version-pgsql" "php$version-redis" \
            "php$version-curl" "php$version-json" "php$version-mbstring" \
            "php$version-xml" "php$version-zip" "php$version-gd" \
            "php$version-intl" "php$version-bcmath"
    elif command -v yum &> /dev/null; then
        echo -e "${BLUE}Using yum to install PHP $version...${NC}"
        sudo yum install -y "php$version" "php$version-cli" "php$version-fpm" \
            "php$version-mysql" "php$version-pgsql" "php$version-redis"
    else
        echo -e "${RED}❌ No supported package manager found${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ PHP $version installed successfully${NC}"
}

# Function to install library
install_library() {
    local library="$1"
    local app_dir="${2:-$PWD}"
    
    # Check for forbidden packages
    check_forbidden_packages "$library"
    
    echo -e "${GREEN}📚 Installing library: $library${NC}"
    
    # Navigate to application directory
    cd "$app_dir"
    
    # Check if composer.json exists
    if [[ ! -f "composer.json" ]]; then
        echo -e "${YELLOW}⚠️  No composer.json found. Creating one...${NC}"
        composer init --no-interaction --name="apm/$(basename "$app_dir")"
    fi
    
    # Install the library
    composer require "$library"
    
    # Log the installation
    echo "$(date): Installed $library in $(basename "$app_dir")" >> "$LIBRARIES_DIR/install.log"
    
    echo -e "${GREEN}✅ Library $library installed successfully${NC}"
}

# Function to list PHP versions
list_php_versions() {
    echo -e "${BLUE}Available PHP Versions:${NC}"
    for version in "${SUPPORTED_PHP_VERSIONS[@]}"; do
        if command -v "php$version" &> /dev/null; then
            echo -e "  ✅ PHP $version (installed)"
        else
            echo -e "  ❌ PHP $version (not installed)"
        fi
    done
}

# Function to list installed libraries
list_libraries() {
    echo -e "${BLUE}Installed Libraries by Application:${NC}"
    
    for app_dir in "$PROJECT_ROOT"/*-app "$PROJECT_ROOT"/simple-php; do
        if [[ -d "$app_dir" && -f "$app_dir/composer.json" ]]; then
            echo -e "\n${YELLOW}$(basename "$app_dir"):${NC}"
            cd "$app_dir"
            if [[ -f "composer.lock" ]]; then
                composer show --installed | head -10
            else
                echo "  No libraries installed"
            fi
        fi
    done
}

# Function to compile application
compile_application() {
    local app_name="$1"
    local app_dir="$PROJECT_ROOT/$app_name"
    
    if [[ ! -d "$app_dir" ]]; then
        echo -e "${RED}❌ Application directory not found: $app_dir${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}🔨 Compiling application: $app_name${NC}"
    
    cd "$app_dir"
    
    # Install dependencies
    if [[ -f "composer.json" ]]; then
        echo -e "${BLUE}Installing Composer dependencies...${NC}"
        composer install --no-dev --optimize-autoloader
    fi
    
    # Application-specific compilation
    case "$app_name" in
        "symfony-app")
            echo -e "${BLUE}Compiling Symfony application...${NC}"
            php bin/console cache:clear --env=prod
            php bin/console cache:warmup --env=prod
            ;;
        "laravel-app")
            echo -e "${BLUE}Compiling Laravel application...${NC}"
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            ;;
        "codeigniter-app")
            echo -e "${BLUE}Compiling CodeIgniter application...${NC}"
            # CodeIgniter doesn't require specific compilation steps
            ;;
    esac
    
    echo -e "${GREEN}✅ Application $app_name compiled successfully${NC}"
}

# Function to set PHP version for application
set_php_version() {
    local version="$1"
    local app_dir="${2:-$PWD}"
    
    if [[ ! " ${SUPPORTED_PHP_VERSIONS[@]} " =~ " ${version} " ]]; then
        echo -e "${RED}❌ Unsupported PHP version: $version${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}🔧 Setting PHP version $version for $(basename "$app_dir")${NC}"
    
    # Create .php-version file
    echo "$version" > "$app_dir/.php-version"
    
    # Update Dockerfile if exists
    if [[ -f "$app_dir/Dockerfile" ]]; then
        sed -i "s/FROM php:[0-9]\+\.[0-9]\+/FROM php:$version/" "$app_dir/Dockerfile"
    fi
    
    echo -e "${GREEN}✅ PHP version set to $version${NC}"
}

# Function to check dependencies
check_dependencies() {
    echo -e "${BLUE}Checking system dependencies...${NC}"
    
    # Check PHP
    if command -v php &> /dev/null; then
        echo -e "  ✅ PHP: $(php --version | head -1)"
    else
        echo -e "  ❌ PHP not found"
    fi
    
    # Check Composer
    if command -v composer &> /dev/null; then
        echo -e "  ✅ Composer: $(composer --version)"
    else
        echo -e "  ❌ Composer not found"
    fi
    
    # Check Docker
    if command -v docker &> /dev/null; then
        echo -e "  ✅ Docker: $(docker --version)"
    else
        echo -e "  ❌ Docker not found"
    fi
    
    # Check forbidden packages
    echo -e "\n${BLUE}Checking for forbidden packages...${NC}"
    for package in "${FORBIDDEN_PACKAGES[@]}"; do
        if command -v "$package" &> /dev/null; then
            echo -e "  ⚠️  $package is installed (should be managed separately)"
        fi
    done
}

# Main script logic
case "${1:-help}" in
    "install-php")
        install_php_version "$2"
        ;;
    "install-library")
        install_library "$2" "$3"
        ;;
    "list-php")
        list_php_versions
        ;;
    "list-libraries")
        list_libraries
        ;;
    "compile-app")
        compile_application "$2"
        ;;
    "set-php-version")
        set_php_version "$2" "$3"
        ;;
    "check-dependencies")
        check_dependencies
        ;;
    "help"|*)
        show_help
        ;;
esac
