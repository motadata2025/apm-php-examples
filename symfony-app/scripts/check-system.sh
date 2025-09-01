#!/bin/bash

# Simple PHP Application - System Requirements Check
# APM PHP Examples - Independent Application

set -e

# Auto-fix script permissions (for git clone compatibility)
fix_script_permissions() {
    local script_dir="$(dirname "$0")"
    echo -e "${BLUE}🔧 Ensuring script permissions...${NC}"

    # Make all .sh files executable
    find "$script_dir" -name "*.sh" -type f ! -executable -exec chmod +x {} \; 2>/dev/null || true

    # Specific critical scripts
    local critical_scripts=(
        "$script_dir/check-system.sh"
        "$script_dir/compile-app.sh"
        "$script_dir/start-app.sh"
        "$script_dir/stop-app.sh"
        "$script_dir/php-version-manager.sh"
        "$script_dir/network-manager.sh"
        "$script_dir/webserver-manager.sh"
    )

    for script in "${critical_scripts[@]}"; do
        if [ -f "$script" ] && [ ! -x "$script" ]; then
            chmod +x "$script" 2>/dev/null || true
        fi
    done

    echo -e "${GREEN}✅ Script permissions fixed${NC}"
}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="Symfony"
REQUIRED_PHP_VERSIONS=("8.1" "8.2" "8.3" "8.4")
SUPPORTED_SERVERS=("php-cli" "apache-mod-php" "apache-fpm" "nginx-fpm")

echo -e "${BLUE}🔍 ${APP_NAME} - System Requirements Check${NC}"
echo "=============================================="

# Function to install missing PHP extensions with user choice
install_php_extensions() {
    echo -e "\n${PURPLE}🔧 PHP Extensions Check${NC}"

    # Ask user if they want to install extensions
    echo -e "${YELLOW}Do you want to check and install missing PHP extensions? (y/n, default: n):${NC}"
    read -t 15 -p "> " install_extensions || install_extensions="n"
    install_extensions=$(echo "$install_extensions" | tr -d '[:space:]')

    if [[ ! "$install_extensions" =~ ^[Yy]$ ]]; then
        echo -e "  ${BLUE}⏭️  Skipping PHP extension installation${NC}"
        echo -e "  ${YELLOW}💡 You can install extensions manually if needed:${NC}"
        echo -e "     ${BLUE}sudo apt install php<version>-redis php<version>-mysql php<version>-pgsql${NC}"
        return 0
    fi

    # Show available PHP versions
    echo -e "\n${BLUE}Available PHP versions:${NC}"
    local available_versions=()
    for version in "${REQUIRED_PHP_VERSIONS[@]}"; do
        if command -v "php$version" >/dev/null 2>&1; then
            echo -e "  ${GREEN}✅ PHP $version${NC}"
            available_versions+=("$version")
        else
            echo -e "  ${YELLOW}⚠️  PHP $version (not installed)${NC}"
        fi
    done

    if [ ${#available_versions[@]} -eq 0 ]; then
        echo -e "  ${RED}❌ No PHP versions found${NC}"
        return 1
    fi

    # Ask which versions to install extensions for
    echo -e "\n${YELLOW}Install extensions for which PHP versions?${NC}"
    echo -e "${BLUE}Options:${NC}"
    echo -e "  ${BLUE}1) All available versions (${available_versions[*]})${NC}"
    echo -e "  ${BLUE}2) Select specific versions${NC}"
    echo -e "  ${BLUE}3) Skip extension installation${NC}"

    read -t 30 -p "Choose option (1/2/3, default: 3): " version_choice || version_choice="3"
    version_choice=$(echo "$version_choice" | tr -d '[:space:]')

    local target_versions=()
    case "$version_choice" in
        "1")
            target_versions=("${available_versions[@]}")
            echo -e "  ${BLUE}Installing for all versions: ${target_versions[*]}${NC}"
            ;;
        "2")
            echo -e "\n${YELLOW}Select PHP versions (space-separated, e.g., 8.3 8.4):${NC}"
            read -t 30 -p "> " selected_versions || selected_versions=""

            if [ -n "$selected_versions" ]; then
                for version in $selected_versions; do
                    if [[ " ${available_versions[*]} " =~ " ${version} " ]]; then
                        target_versions+=("$version")
                    else
                        echo -e "  ${YELLOW}⚠️  PHP $version not available, skipping${NC}"
                    fi
                done
            fi

            if [ ${#target_versions[@]} -eq 0 ]; then
                echo -e "  ${YELLOW}No valid versions selected, skipping${NC}"
                return 0
            fi
            ;;
        *)
            echo -e "  ${BLUE}⏭️  Skipping extension installation${NC}"
            return 0
            ;;
    esac

    # Required extensions for the application
    local required_extensions=("redis" "mysql" "pgsql" "curl" "zip" "mbstring" "xml" "gd")
    local missing_extensions=()

    # Check selected PHP versions and install missing extensions
    for version in "${target_versions[@]}"; do
        echo -e "\n  ${BLUE}Checking PHP $version extensions...${NC}"

        for ext in "${required_extensions[@]}"; do
            # Check if extension is loaded
            if ! php$version -m | grep -q "^$ext$" 2>/dev/null; then
                local package_name="php$version-$ext"

                # Special case for some extensions
                case $ext in
                    "mysql") package_name="php$version-mysql" ;;
                    "pgsql") package_name="php$version-pgsql" ;;
                    "redis") package_name="php$version-redis" ;;
                esac

                echo -e "    ${YELLOW}⚠️  Missing: $ext${NC}"
                missing_extensions+=("$package_name")
            else
                echo -e "    ${GREEN}✅ $ext${NC}"
            fi
        done
    done

    # Install missing extensions
    if [ ${#missing_extensions[@]} -gt 0 ]; then
        echo -e "\n  ${BLUE}Installing missing extensions...${NC}"
        echo -e "  ${YELLOW}Extensions to install: ${missing_extensions[*]}${NC}"

        # Remove duplicates
        local unique_extensions=($(printf "%s\n" "${missing_extensions[@]}" | sort -u))

        if sudo apt update && sudo apt install -y "${unique_extensions[@]}"; then
            echo -e "  ${GREEN}✅ PHP extensions installed successfully${NC}"

            # Restart PHP-FPM services if they're running
            for version in "${target_versions[@]}"; do
                if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
                    echo -e "  ${BLUE}Restarting PHP-FPM $version...${NC}"
                    sudo systemctl restart "php${version}-fpm"
                fi
            done

            echo -e "  ${GREEN}✅ PHP-FPM services restarted${NC}"
        else
            echo -e "  ${RED}❌ Failed to install some PHP extensions${NC}"
            echo -e "  ${YELLOW}💡 You may need to install them manually${NC}"
        fi
    else
        echo -e "  ${GREEN}✅ All required PHP extensions are already installed${NC}"
    fi
}

# Function to check PHP versions
check_php_versions() {
    echo -e "\n${PURPLE}📋 Checking PHP Versions...${NC}"
    
    local found_versions=()
    local php_cli_available=false
    
    # Check default PHP
    if command -v php >/dev/null 2>&1; then
        local default_version=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        echo -e "  ${GREEN}✅ Default PHP: $default_version${NC}"
        found_versions+=("$default_version")
        php_cli_available=true
    fi
    
    # Check specific PHP versions
    for version in "${REQUIRED_PHP_VERSIONS[@]}"; do
        if command -v "php$version" >/dev/null 2>&1; then
            echo -e "  ${GREEN}✅ PHP $version: Available${NC}"
            found_versions+=("$version")
        else
            echo -e "  ${YELLOW}⚠️  PHP $version: Not installed${NC}"
        fi
    done
    
    if [ ${#found_versions[@]} -eq 0 ]; then
        echo -e "  ${RED}❌ ERROR: No supported PHP versions found!${NC}"
        echo -e "  ${YELLOW}Please install PHP 8.1, 8.2, 8.3, or 8.4${NC}"
        exit 1
    fi
    
    echo -e "  ${GREEN}✅ Found ${#found_versions[@]} PHP version(s)${NC}"
    return 0
}

# Function to check web servers
check_web_servers() {
    echo -e "\n${PURPLE}🌐 Checking Web Servers...${NC}"
    
    local available_servers=()
    
    # Check PHP CLI
    if command -v php >/dev/null 2>&1; then
        echo -e "  ${GREEN}✅ PHP-CLI: Available${NC}"
        available_servers+=("php-cli")
    else
        echo -e "  ${RED}❌ PHP-CLI: Not available${NC}"
    fi
    
    # Check Apache
    if command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1; then
        echo -e "  ${GREEN}✅ Apache: Installed${NC}"
        
        # Check if Apache is running
        if systemctl is-active --quiet apache2 2>/dev/null || systemctl is-active --quiet httpd 2>/dev/null; then
            echo -e "    ${GREEN}✅ Apache: Running${NC}"
            
            # Check mod_php
            if apache2ctl -M 2>/dev/null | grep -q php || httpd -M 2>/dev/null | grep -q php; then
                echo -e "    ${GREEN}✅ Apache mod_php: Enabled${NC}"
                available_servers+=("apache-mod-php")
            else
                echo -e "    ${YELLOW}⚠️  Apache mod_php: Not enabled${NC}"
            fi
            
            # Check PHP-FPM (check for version-specific FPM)
            local fpm_found=false
            local fpm_running=false
            local running_versions=()

            for version in 8.1 8.2 8.3 8.4; do
                # Check if systemd service exists and is active
                if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
                    fpm_found=true
                    fpm_running=true
                    running_versions+=("${version}")
                elif systemctl list-unit-files "php${version}-fpm.service" 2>/dev/null | grep -q "php${version}-fpm.service"; then
                    fpm_found=true
                fi
            done

            if [ "$fpm_found" = true ]; then
                if [ "$fpm_running" = true ]; then
                    echo -e "    ${GREEN}✅ PHP-FPM: Available (versions: ${running_versions[*]})${NC}"
                    echo -e "    ${GREEN}✅ PHP-FPM: Running${NC}"
                    available_servers+=("apache-fpm")
                else
                    echo -e "    ${YELLOW}⚠️  PHP-FPM: Installed but not running${NC}"
                fi
            else
                echo -e "    ${YELLOW}⚠️  PHP-FPM: Not installed${NC}"
            fi
        else
            echo -e "    ${YELLOW}⚠️  Apache: Not running${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  Apache: Not installed${NC}"
    fi
    
    # Check Nginx
    if command -v nginx >/dev/null 2>&1; then
        echo -e "  ${GREEN}✅ Nginx: Installed${NC}"
        
        if systemctl is-active --quiet nginx 2>/dev/null; then
            echo -e "    ${GREEN}✅ Nginx: Running${NC}"
            
            # Check PHP-FPM for Nginx (same logic as Apache)
            local nginx_fpm_found=false
            local nginx_fpm_running=false
            local nginx_running_versions=()

            for version in 8.1 8.2 8.3 8.4; do
                if command -v "php${version}-fpm" >/dev/null 2>&1; then
                    nginx_fpm_found=true
                    if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
                        nginx_fpm_running=true
                        nginx_running_versions+=("${version}")
                    fi
                fi
            done

            if [ "$nginx_fpm_found" = true ]; then
                if [ "$nginx_fpm_running" = true ]; then
                    echo -e "    ${GREEN}✅ PHP-FPM: Available (versions: ${nginx_running_versions[*]})${NC}"
                    echo -e "    ${GREEN}✅ PHP-FPM: Running${NC}"
                    available_servers+=("nginx-fpm")
                else
                    echo -e "    ${YELLOW}⚠️  PHP-FPM: Installed but not running${NC}"
                fi
            else
                echo -e "    ${YELLOW}⚠️  PHP-FPM: Not installed${NC}"
            fi
        else
            echo -e "    ${YELLOW}⚠️  Nginx: Not running${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  Nginx: Not installed${NC}"
    fi
    
    if [ ${#available_servers[@]} -eq 0 ]; then
        echo -e "  ${RED}❌ ERROR: No web servers available!${NC}"
        echo -e "  ${YELLOW}Please install and configure at least one web server${NC}"
        exit 1
    fi
    
    echo -e "  ${GREEN}✅ Found ${#available_servers[@]} available deployment option(s)${NC}"
    return 0
}

# Function to check Docker
check_docker() {
    echo -e "\n${PURPLE}🐳 Checking Docker...${NC}"
    
    if command -v docker >/dev/null 2>&1; then
        echo -e "  ${GREEN}✅ Docker: Installed${NC}"
        
        if docker info >/dev/null 2>&1; then
            echo -e "  ${GREEN}✅ Docker: Running${NC}"
            
            # Check Docker Compose
            if command -v docker-compose >/dev/null 2>&1 || docker compose version >/dev/null 2>&1; then
                echo -e "  ${GREEN}✅ Docker Compose: Available${NC}"
                return 0
            else
                echo -e "  ${YELLOW}⚠️  Docker Compose: Not available${NC}"
                return 1
            fi
        else
            echo -e "  ${RED}❌ Docker: Not running${NC}"
            echo -e "  ${YELLOW}Please start Docker service${NC}"
            return 1
        fi
    else
        echo -e "  ${RED}❌ Docker: Not installed${NC}"
        echo -e "  ${YELLOW}Please install Docker${NC}"
        return 1
    fi
}

# Function to start Docker services
start_docker_services() {
    echo -e "\n${PURPLE}🚀 Starting Application Docker Containers...${NC}"

    # Check if application has its own docker-compose.yml for isolated containers
    if [ -f "docker-compose.yml" ]; then
        echo -e "  ${BLUE}Starting application containers (MySQL, PostgreSQL, Redis)...${NC}"
        if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
            # Use docker-helper.sh for proper version detection
            if [ -f "scripts/docker-helper.sh" ]; then
                ./scripts/docker-helper.sh app-up 2>/dev/null || true
                echo -e "  ${GREEN}✅ Application containers started${NC}"

                # Show container status
                echo -e "  ${BLUE}Container status:${NC}"
                ./scripts/docker-helper.sh app-status 2>/dev/null || true
            else
                # Fallback to direct docker compose commands
                if command -v "docker" >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
                    docker compose up -d 2>/dev/null || true
                elif command -v "docker-compose" >/dev/null 2>&1; then
                    docker-compose up -d 2>/dev/null || true
                fi
                echo -e "  ${GREEN}✅ Application containers started${NC}"
            fi
        else
            echo -e "  ${YELLOW}⚠️  Docker not available - application will use localhost services${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  No docker-compose.yml found - application will use localhost services${NC}"
    fi

    return 0
}

# Function to show summary
show_summary() {
    echo -e "\n${BLUE}📊 System Check Summary${NC}"
    echo "======================="
    
    echo -e "${GREEN}✅ System is ready for ${APP_NAME} deployment${NC}"

    # Apply production scaling silently (no user prompts)
    if [ -f "scripts/production-scaling.sh" ]; then
        ./scripts/production-scaling.sh >/dev/null 2>&1 || true
    fi

    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo -e "  1. Run: ${BLUE}make compile${NC} - to compile the application"
    echo -e "  2. Run: ${BLUE}make start${NC} - to start the application"
    echo ""
}

# Main execution
main() {
    # Fix script permissions first (for git clone compatibility)
    fix_script_permissions

    check_php_versions
    install_php_extensions
    check_web_servers
    
    if check_docker; then
        start_docker_services
    else
        echo -e "\n${YELLOW}⚠️  Docker not available - some features may be limited${NC}"
    fi
    
    show_summary
}

# Execute main function
main "$@"
