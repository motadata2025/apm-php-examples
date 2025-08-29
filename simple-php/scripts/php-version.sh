#!/bin/bash

# Simple PHP Application - PHP Version Management
# Supports switching between PHP 8.1, 8.2, 8.3, 8.4

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
SUPPORTED_VERSIONS=("8.1" "8.2" "8.3" "8.4")
CONFIG_FILE="config/app.env"

echo -e "${BLUE}🐘 Simple PHP - PHP Version Management${NC}"
echo "======================================"

# Function to get current PHP version
get_current_version() {
    if [ -f "$CONFIG_FILE" ]; then
        grep "^PHP_VERSION=" "$CONFIG_FILE" | cut -d'=' -f2 || echo "8.4"
    else
        echo "8.4"
    fi
}

# Function to check if version is supported
is_version_supported() {
    local version=$1
    for supported in "${SUPPORTED_VERSIONS[@]}"; do
        if [[ "$supported" == "$version" ]]; then
            return 0
        fi
    done
    return 1
}

# Function to check if PHP version is available
check_php_available() {
    local version=$1
    
    # Check system PHP
    if command -v "php$version" >/dev/null 2>&1; then
        echo "system"
        return 0
    fi
    
    # Check Docker
    if command -v docker >/dev/null 2>&1; then
        if docker image inspect "php:$version-fpm-alpine" >/dev/null 2>&1; then
            echo "docker"
            return 0
        fi
    fi
    
    return 1
}

# Function to switch PHP version
switch_php_version() {
    local new_version=$1
    local current_version=$(get_current_version)
    
    echo -e "\n${PURPLE}🔄 Switching PHP Version${NC}"
    echo -e "  Current: ${BLUE}PHP $current_version${NC}"
    echo -e "  Target:  ${BLUE}PHP $new_version${NC}"
    
    if [[ "$current_version" == "$new_version" ]]; then
        echo -e "  ${GREEN}✅ Already using PHP $new_version${NC}"
        return 0
    fi
    
    # Check if version is supported
    if ! is_version_supported "$new_version"; then
        echo -e "  ${RED}❌ PHP $new_version is not supported${NC}"
        echo -e "  ${YELLOW}Supported versions: ${SUPPORTED_VERSIONS[*]}${NC}"
        return 1
    fi
    
    # Check availability
    local availability=$(check_php_available "$new_version")
    if [[ $? -ne 0 ]]; then
        echo -e "  ${RED}❌ PHP $new_version is not available${NC}"
        echo -e "  ${YELLOW}Please install PHP $new_version or ensure Docker is available${NC}"
        return 1
    fi
    
    echo -e "  ${GREEN}✅ PHP $new_version available via $availability${NC}"
    
    # Update configuration
    mkdir -p config
    if [ -f "$CONFIG_FILE" ]; then
        sed -i "s/^PHP_VERSION=.*/PHP_VERSION=$new_version/" "$CONFIG_FILE"
    else
        echo "PHP_VERSION=$new_version" >> "$CONFIG_FILE"
    fi
    
    # Update Docker environment
    echo "PHP_VERSION=$new_version" > .env
    
    # Rebuild dependencies for new PHP version
    echo -e "\n${PURPLE}📦 Rebuilding Dependencies for PHP $new_version${NC}"
    
    if [[ "$availability" == "system" ]]; then
        # Use system PHP
        if command -v "php$new_version" >/dev/null 2>&1; then
            "php$new_version" $(which composer) install --optimize-autoloader
        else
            php -v | head -1
            composer install --optimize-autoloader
        fi
    else
        # Use Docker
        echo -e "  ${BLUE}Using Docker for PHP $new_version${NC}"
        docker-compose build --build-arg PHP_VERSION="$new_version"
    fi
    
    echo -e "  ${GREEN}✅ Dependencies rebuilt for PHP $new_version${NC}"
    echo -e "\n${GREEN}🎉 Successfully switched to PHP $new_version${NC}"
    
    return 0
}

# Function to show available versions
show_available_versions() {
    echo -e "\n${PURPLE}📋 Available PHP Versions${NC}"
    
    local current_version=$(get_current_version)
    
    for version in "${SUPPORTED_VERSIONS[@]}"; do
        local availability=$(check_php_available "$version")
        local status_icon="❌"
        local status_text="Not Available"
        
        if [[ $? -eq 0 ]]; then
            status_icon="✅"
            status_text="Available ($availability)"
        fi
        
        local current_marker=""
        if [[ "$version" == "$current_version" ]]; then
            current_marker=" ${GREEN}(current)${NC}"
        fi
        
        echo -e "  $status_icon PHP $version - $status_text$current_marker"
    done
}

# Function to auto-detect and recommend version
auto_detect_version() {
    echo -e "\n${PURPLE}🔍 Auto-detecting Best PHP Version${NC}"
    
    # Check what's available and recommend the highest
    for version in "${SUPPORTED_VERSIONS[@]}"; do
        local availability=$(check_php_available "$version")
        if [[ $? -eq 0 ]]; then
            echo -e "  ${GREEN}✅ Recommended: PHP $version ($availability)${NC}"
            return 0
        fi
    done
    
    echo -e "  ${RED}❌ No supported PHP versions found${NC}"
    return 1
}

# Main execution
main() {
    local action=${1:-"show"}
    local version=${2:-""}
    
    case $action in
        "switch")
            if [[ -z "$version" ]]; then
                echo -e "${RED}❌ Please specify a PHP version${NC}"
                echo -e "${YELLOW}Usage: $0 switch <version>${NC}"
                echo -e "${YELLOW}Example: $0 switch 8.4${NC}"
                exit 1
            fi
            switch_php_version "$version"
            ;;
        "show"|"list")
            show_available_versions
            ;;
        "auto")
            auto_detect_version
            ;;
        "current")
            local current=$(get_current_version)
            echo -e "${GREEN}Current PHP version: $current${NC}"
            ;;
        *)
            echo -e "${YELLOW}Usage: $0 {switch|show|auto|current} [version]${NC}"
            echo -e ""
            echo -e "Commands:"
            echo -e "  switch <version>  - Switch to specified PHP version"
            echo -e "  show             - Show all available versions"
            echo -e "  auto             - Auto-detect best version"
            echo -e "  current          - Show current version"
            echo -e ""
            echo -e "Examples:"
            echo -e "  $0 switch 8.4"
            echo -e "  $0 show"
            echo -e "  $0 auto"
            ;;
    esac
}

# Execute main function
main "$@"
