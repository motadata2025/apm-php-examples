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
APP_NAME="Simple PHP"
REQUIRED_PHP_VERSIONS=("8.1" "8.2" "8.3" "8.4")
SUPPORTED_SERVERS=("php-cli" "apache-mod-php" "apache-fpm" "nginx-fpm")

echo -e "${BLUE}🔍 ${APP_NAME} - System Requirements Check${NC}"
echo "=============================================="

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
    echo -e "\n${PURPLE}🚀 Starting Docker Services...${NC}"

    # Use the Docker helper script for proper version detection
    if [ -f "../scripts/docker-helper.sh" ]; then
        echo -e "  ${BLUE}Using Docker helper for service management...${NC}"

        # Check if shared services are needed
        if [ -f "../docker-compose.services.yml" ]; then
            echo -e "  ${BLUE}Starting shared services...${NC}"
            ../scripts/docker-helper.sh services-up
            echo -e "  ${GREEN}✅ Shared services started${NC}"
        fi

        # Note: Application runs natively, only shared services needed
        echo -e "  ${BLUE}Application will run natively with local PHP/Apache${NC}"

        return 0
    else
        echo -e "  ${YELLOW}⚠️  Docker helper not found - skipping Docker services${NC}"
        return 0
    fi
}

# Function to show summary
show_summary() {
    echo -e "\n${BLUE}📊 System Check Summary${NC}"
    echo "======================="
    
    echo -e "${GREEN}✅ System is ready for ${APP_NAME} deployment${NC}"
    echo ""

    # Ask about production scaling configuration
    echo -e "${PURPLE}🚀 Production Scaling Configuration${NC}"
    echo -e "Would you like to apply production scaling optimizations?"
    echo -e "  ${BLUE}• Optimizes for 200+ concurrent users${NC}"
    echo -e "  ${BLUE}• Configures PHP-FPM, Apache, and database settings${NC}"
    echo -e "  ${BLUE}• Adds monitoring endpoints${NC}"
    echo ""

    read -t 15 -p "Apply production scaling configuration? (y/n): " apply_scaling || apply_scaling="n"

    if [[ "$apply_scaling" =~ ^[Yy]$ ]]; then
        echo -e "\n${BLUE}Applying production scaling configuration...${NC}"
        if [ -f "scripts/production-scaling.sh" ]; then
            ./scripts/production-scaling.sh
        else
            echo -e "  ${RED}❌ Production scaling script not found${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  Production scaling skipped${NC}"
        echo -e "  ${BLUE}You can apply it later with: ./scripts/production-scaling.sh${NC}"
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
