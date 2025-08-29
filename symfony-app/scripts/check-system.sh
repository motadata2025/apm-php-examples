#!/bin/bash

# Symfony Application - System Requirements Check
# APM PHP Examples - Independent Application

set -e

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

# Function to check Symfony requirements
check_symfony_requirements() {
    echo -e "\n${PURPLE}🎼 Checking Symfony Requirements...${NC}"
    
    # Check Composer
    if command -v composer >/dev/null 2>&1; then
        echo -e "  ${GREEN}✅ Composer: Available${NC}"
        local composer_version=$(composer --version 2>/dev/null | head -1)
        echo -e "    ${BLUE}Version: $composer_version${NC}"
    else
        echo -e "  ${RED}❌ Composer: Not installed${NC}"
        echo -e "  ${YELLOW}Please install Composer${NC}"
        exit 1
    fi
    
    # Check required PHP extensions
    local required_extensions=("ctype" "iconv" "json" "pcre" "session" "simplexml" "tokenizer")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo -e "  ${GREEN}✅ PHP Extension $ext: Available${NC}"
        else
            echo -e "  ${RED}❌ PHP Extension $ext: Missing${NC}"
            missing_extensions+=("$ext")
        fi
    done
    
    if [ ${#missing_extensions[@]} -gt 0 ]; then
        echo -e "  ${RED}❌ Missing required PHP extensions: ${missing_extensions[*]}${NC}"
        echo -e "  ${YELLOW}Please install missing extensions${NC}"
        exit 1
    fi
    
    # Check if .env file exists
    if [ -f ".env" ]; then
        echo -e "  ${GREEN}✅ Environment file: Found${NC}"
    else
        if [ -f ".env.example" ]; then
            echo -e "  ${YELLOW}⚠️  Environment file: Missing, but .env.example found${NC}"
            echo -e "  ${BLUE}Will copy .env.example to .env during setup${NC}"
        else
            echo -e "  ${YELLOW}⚠️  Environment file: Missing${NC}"
            echo -e "  ${BLUE}Will create during compilation${NC}"
        fi
    fi
    
    # Check if vendor directory exists
    if [ -d "vendor" ]; then
        echo -e "  ${GREEN}✅ Dependencies: Installed${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Dependencies: Not installed${NC}"
        echo -e "  ${BLUE}Will install during compilation${NC}"
    fi
    
    # Check Symfony Console
    if [ -f "bin/console" ]; then
        echo -e "  ${GREEN}✅ Symfony Console: Available${NC}"
        if php bin/console --version >/dev/null 2>&1; then
            local symfony_version=$(php bin/console --version 2>/dev/null | head -1)
            echo -e "    ${BLUE}$symfony_version${NC}"
        fi
    else
        echo -e "  ${RED}❌ Symfony Console: Missing${NC}"
    fi
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
            
            # Check PHP-FPM
            if command -v php-fpm >/dev/null 2>&1; then
                echo -e "    ${GREEN}✅ PHP-FPM: Available${NC}"
                if systemctl is-active --quiet php*-fpm 2>/dev/null; then
                    echo -e "    ${GREEN}✅ PHP-FPM: Running${NC}"
                    available_servers+=("apache-fpm")
                else
                    echo -e "    ${YELLOW}⚠️  PHP-FPM: Not running${NC}"
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
            
            # Check PHP-FPM for Nginx
            if command -v php-fpm >/dev/null 2>&1; then
                echo -e "    ${GREEN}✅ PHP-FPM: Available${NC}"
                if systemctl is-active --quiet php*-fpm 2>/dev/null; then
                    echo -e "    ${GREEN}✅ PHP-FPM: Running${NC}"
                    available_servers+=("nginx-fpm")
                else
                    echo -e "    ${YELLOW}⚠️  PHP-FPM: Not running${NC}"
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
    
    # Check if docker-compose.yml exists
    if [ -f "docker-compose.yml" ]; then
        echo -e "  ${BLUE}Starting application-specific services...${NC}"
        docker-compose up -d
    elif [ -f "../docker-compose.services.yml" ]; then
        echo -e "  ${BLUE}Starting shared services...${NC}"
        cd .. && docker-compose -f docker-compose.services.yml up -d
        cd - >/dev/null
    else
        echo -e "  ${YELLOW}⚠️  No Docker Compose file found${NC}"
        return 1
    fi
    
    echo -e "  ${GREEN}✅ Docker services started${NC}"
    return 0
}

# Function to show summary
show_summary() {
    echo -e "\n${BLUE}📊 System Check Summary${NC}"
    echo "======================="
    
    echo -e "${GREEN}✅ System is ready for ${APP_NAME} deployment${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo -e "  1. Run: ${BLUE}make compile${NC} - to compile the application"
    echo -e "  2. Run: ${BLUE}make start${NC} - to start the application"
    echo ""
}

# Main execution
main() {
    check_php_versions
    check_symfony_requirements
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
