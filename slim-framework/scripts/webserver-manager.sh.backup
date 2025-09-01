#!/bin/bash

# Web Server Manager - PHP version-specific deployment
# APM PHP Examples - Simple PHP Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Function to detect Linux distribution
detect_distro() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        echo "$ID"
    elif [ -f /etc/redhat-release ]; then
        echo "rhel"
    elif [ -f /etc/debian_version ]; then
        echo "debian"
    else
        echo "unknown"
    fi
}

# Function to check package installation (cross-distribution)
check_package_installed() {
    local package=$1
    local distro=$(detect_distro)

    case $distro in
        ubuntu|debian)
            dpkg -l "$package" 2>/dev/null | grep -q "^ii"
            ;;
        centos|rhel|fedora)
            rpm -q "$package" >/dev/null 2>&1
            ;;
        arch)
            pacman -Q "$package" >/dev/null 2>&1
            ;;
        *)
            # Generic check - try multiple package managers
            if command -v dpkg >/dev/null 2>&1; then
                dpkg -l "$package" 2>/dev/null | grep -q "^ii"
            elif command -v rpm >/dev/null 2>&1; then
                rpm -q "$package" >/dev/null 2>&1
            elif command -v pacman >/dev/null 2>&1; then
                pacman -Q "$package" >/dev/null 2>&1
            else
                return 1
            fi
            ;;
    esac
}

# Function to get package install command
get_install_command() {
    local package=$1
    local distro=$(detect_distro)

    case $distro in
        ubuntu|debian)
            echo "sudo apt install $package"
            ;;
        centos|rhel)
            echo "sudo yum install $package"
            ;;
        fedora)
            echo "sudo dnf install $package"
            ;;
        arch)
            echo "sudo pacman -S $package"
            ;;
        *)
            echo "sudo apt install $package  # or use your distribution's package manager"
            ;;
    esac
}

# Function to disable ALL PHP mod_php versions
disable_all_mod_php() {
    echo -e "  ${BLUE}Disabling all PHP mod_php versions...${NC}"

    local disabled_any=false

    # Check all common PHP versions
    for version in 7.4 8.0 8.1 8.2 8.3 8.4; do
        if sudo a2enmod -q "php${version}" 2>/dev/null; then
            echo -e "    ${YELLOW}Disabling mod_php${version}...${NC}"
            if sudo a2dismod "php${version}" 2>/dev/null; then
                echo -e "    ${GREEN}✅ mod_php${version} disabled${NC}"
                disabled_any=true
            else
                echo -e "    ${RED}❌ Failed to disable mod_php${version}${NC}"
            fi
        fi
    done

    if [ "$disabled_any" = true ]; then
        echo -e "  ${GREEN}✅ All mod_php versions disabled${NC}"
        return 0
    else
        echo -e "  ${YELLOW}⚠️  No mod_php versions were enabled${NC}"
        return 0
    fi
}

# Function to stop ALL PHP-FPM services
stop_all_php_fpm() {
    echo -e "  ${BLUE}Stopping all PHP-FPM services...${NC}"

    local stopped_any=false

    # Check all common PHP versions
    for fpm_version in 7.4 8.0 8.1 8.2 8.3 8.4; do
        if systemctl is-active "php${fpm_version}-fpm" >/dev/null 2>&1; then
            echo -e "    ${YELLOW}Stopping PHP-FPM ${fpm_version}...${NC}"
            if sudo systemctl stop "php${fpm_version}-fpm" 2>/dev/null; then
                echo -e "    ${GREEN}✅ PHP-FPM ${fpm_version} stopped${NC}"
                stopped_any=true
            else
                echo -e "    ${RED}❌ Failed to stop PHP-FPM ${fpm_version}${NC}"
            fi
        fi
    done

    if [ "$stopped_any" = true ]; then
        echo -e "  ${GREEN}✅ All PHP-FPM services stopped${NC}"
        return 0
    else
        echo -e "  ${YELLOW}⚠️  No PHP-FPM services were running${NC}"
        return 0
    fi
}

# Function to start specific PHP-FPM service
start_php_fpm() {
    local php_version=$1

    if [ -z "$php_version" ]; then
        echo -e "  ${RED}❌ PHP version not specified${NC}"
        return 1
    fi

    echo -e "  ${BLUE}Starting PHP-FPM ${php_version}...${NC}"

    if systemctl is-active "php${php_version}-fpm" >/dev/null 2>&1; then
        echo -e "  ${YELLOW}⚠️  PHP-FPM ${php_version} is already running${NC}"
        return 0
    fi

    if sudo systemctl start "php${php_version}-fpm" 2>/dev/null; then
        echo -e "  ${GREEN}✅ PHP-FPM ${php_version} started${NC}"
        return 0
    else
        echo -e "  ${RED}❌ Failed to start PHP-FPM ${php_version}${NC}"
        return 1
    fi
}

# Function to enable ALL proxy modules for PHP-FPM
enable_proxy_modules() {
    echo -e "  ${BLUE}Enabling Apache proxy modules for PHP-FPM...${NC}"

    local modules=("proxy" "proxy_fcgi")
    local enabled_any=false

    for module in "${modules[@]}"; do
        if ! apache2ctl -M 2>/dev/null | grep -q "${module}_module"; then
            echo -e "    ${YELLOW}Enabling ${module}...${NC}"
            if sudo a2enmod "$module" 2>/dev/null; then
                echo -e "    ${GREEN}✅ ${module} enabled${NC}"
                enabled_any=true
            else
                echo -e "    ${RED}❌ Failed to enable ${module}${NC}"
                return 1
            fi
        else
            echo -e "    ${YELLOW}⚠️  ${module} already enabled${NC}"
        fi
    done

    if [ "$enabled_any" = true ]; then
        echo -e "  ${GREEN}✅ Proxy modules enabled${NC}"
    fi
    return 0
}

# Function to disable ALL proxy modules for mod_php
disable_proxy_modules() {
    echo -e "  ${BLUE}Disabling Apache proxy modules for mod_php...${NC}"

    local modules=("proxy_fcgi" "proxy")
    local disabled_any=false

    for module in "${modules[@]}"; do
        if apache2ctl -M 2>/dev/null | grep -q "${module}_module"; then
            echo -e "    ${YELLOW}Disabling ${module}...${NC}"
            if sudo a2dismod "$module" 2>/dev/null; then
                echo -e "    ${GREEN}✅ ${module} disabled${NC}"
                disabled_any=true
            else
                echo -e "    ${RED}❌ Failed to disable ${module}${NC}"
                return 1
            fi
        else
            echo -e "    ${YELLOW}⚠️  ${module} already disabled${NC}"
        fi
    done

    if [ "$disabled_any" = true ]; then
        echo -e "  ${GREEN}✅ Proxy modules disabled${NC}"
    fi
    return 0
}

# Function to check if ANY mod_php version is enabled
check_any_mod_php_enabled() {
    for version in 7.4 8.0 8.1 8.2 8.3 8.4; do
        if sudo a2enmod -q "php${version}" 2>/dev/null; then
            return 0  # Found an enabled mod_php
        fi
    done
    return 1  # No mod_php enabled
}

# Function to check Apache mod_php for specific PHP version (cross-distribution)
check_apache_mod_php() {
    local php_version=$1
    local distro=$(detect_distro)

    # Check if Apache is installed
    if ! (command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1); then
        echo "apache_not_installed"
        return 1
    fi

    # Determine Apache command and module package name
    local apache_cmd="apache2"
    local mod_package=""

    if command -v httpd >/dev/null 2>&1; then
        apache_cmd="httpd"
    fi

    # Package names vary by distribution
    case $distro in
        ubuntu|debian)
            mod_package="libapache2-mod-php${php_version}"
            ;;
        centos|rhel|fedora)
            mod_package="php${php_version}"
            ;;
        *)
            mod_package="libapache2-mod-php${php_version}"
            ;;
    esac

    # Check if mod_php package is installed
    if check_package_installed "$mod_package"; then
        # Check if module is enabled (Debian/Ubuntu style)
        if command -v a2enmod >/dev/null 2>&1; then
            if a2enmod -q "php${php_version}" 2>/dev/null; then
                echo "available_enabled"
                return 0
            else
                echo "available_disabled"
                return 0
            fi
        else
            # For RHEL/CentOS, check if PHP module is loaded
            if $apache_cmd -M 2>/dev/null | grep -q "php${php_version}"; then
                echo "available_enabled"
                return 0
            else
                echo "available_disabled"
                return 0
            fi
        fi
    else
        echo "not_installed"
        return 1
    fi
}

# Function to check PHP-FPM for specific PHP version
check_php_fpm() {
    local php_version=$1
    
    # Check if PHP-FPM service exists and is active
    if systemctl is-active --quiet "php${php_version}-fpm" 2>/dev/null; then
        echo "active"
        return 0
    elif systemctl list-unit-files "php${php_version}-fpm.service" 2>/dev/null | grep -q "php${php_version}-fpm.service"; then
        echo "inactive"
        return 0
    else
        echo "not_installed"
        return 1
    fi
}

# Function to check Nginx for PHP-FPM
check_nginx_fpm() {
    local php_version=$1
    
    # Check if Nginx is installed
    if ! command -v nginx >/dev/null 2>&1; then
        echo "nginx_not_installed"
        return 1
    fi
    
    # Check PHP-FPM status
    local fpm_status=$(check_php_fpm "$php_version")
    echo "$fpm_status"
    return $?
}

# Function to get available deployment options for PHP version
get_deployment_options() {
    local php_version=$1
    local options=()
    
    echo -e "\n${PURPLE}🌐 Web Server Deployment Options for PHP $php_version${NC}"
    echo -e "================================================================"
    
    # Always available: PHP Built-in Server
    echo -e "${BLUE}1. PHP Built-in Server${NC}"
    echo -e "   ${GREEN}✅ Always available${NC}"
    echo -e "   ${BLUE}Command: php${php_version} -S 0.0.0.0:8001${NC}"
    options+=("php-cli:PHP Built-in Server")
    
    # Check Apache mod_php
    local apache_mod_status=$(check_apache_mod_php "$php_version")
    echo -e "\n${BLUE}2. Apache with mod_php${NC}"
    case $apache_mod_status in
        "available_enabled")
            echo -e "   ${GREEN}✅ Available and enabled${NC}"
            echo -e "   ${BLUE}Module: libapache2-mod-php${php_version}${NC}"
            options+=("apache-mod-php:Apache with mod_php")
            ;;
        "available_disabled")
            echo -e "   ${YELLOW}⚠️  Available but disabled${NC}"
            echo -e "   ${YELLOW}Enable with: sudo a2enmod php${php_version}${NC}"
            options+=("apache-mod-php:Apache with mod_php (needs enabling)")
            ;;
        "not_installed")
            echo -e "   ${RED}❌ Not installed${NC}"
            local install_cmd=$(get_install_command "libapache2-mod-php${php_version}")
            echo -e "   ${RED}Install with: $install_cmd${NC}"
            ;;
        "apache_not_installed")
            echo -e "   ${RED}❌ Apache not installed${NC}"
            local apache_install_cmd=$(get_install_command "apache2")
            echo -e "   ${RED}Install with: $apache_install_cmd${NC}"
            ;;
    esac
    
    # Check Apache PHP-FPM
    local fpm_status=$(check_php_fpm "$php_version")
    echo -e "\n${BLUE}3. Apache with PHP-FPM${NC}"
    case $fpm_status in
        "active")
            if (command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1); then
                echo -e "   ${GREEN}✅ Available and running${NC}"
                echo -e "   ${BLUE}Service: php${php_version}-fpm${NC}"
                options+=("apache-fpm:Apache with PHP-FPM")
            else
                echo -e "   ${RED}❌ Apache not installed${NC}"
                echo -e "   ${RED}Install with: sudo apt install apache2${NC}"
            fi
            ;;
        "inactive")
            if (command -v apache2 >/dev/null 2>&1 || command -v httpd >/dev/null 2>&1); then
                echo -e "   ${YELLOW}⚠️  PHP-FPM installed but not running${NC}"
                echo -e "   ${YELLOW}Start with: sudo systemctl start php${php_version}-fpm${NC}"
                options+=("apache-fpm:Apache with PHP-FPM (needs starting)")
            else
                echo -e "   ${RED}❌ Apache not installed${NC}"
                echo -e "   ${RED}Install with: sudo apt install apache2${NC}"
            fi
            ;;
        "not_installed")
            echo -e "   ${RED}❌ PHP-FPM not installed${NC}"
            local fpm_install_cmd=$(get_install_command "php${php_version}-fpm")
            echo -e "   ${RED}Install with: $fpm_install_cmd${NC}"
            ;;
    esac

    # Check Nginx PHP-FPM
    local nginx_fpm_status=$(check_nginx_fpm "$php_version")
    echo -e "\n${BLUE}4. Nginx with PHP-FPM${NC}"
    case $nginx_fpm_status in
        "active")
            echo -e "   ${GREEN}✅ Available and running${NC}"
            echo -e "   ${BLUE}Service: php${php_version}-fpm${NC}"
            options+=("nginx-fpm:Nginx with PHP-FPM")
            ;;
        "inactive")
            echo -e "   ${YELLOW}⚠️  PHP-FPM installed but not running${NC}"
            echo -e "   ${YELLOW}Start with: sudo systemctl start php${php_version}-fpm${NC}"
            options+=("nginx-fpm:Nginx with PHP-FPM (needs starting)")
            ;;
        "not_installed")
            echo -e "   ${RED}❌ PHP-FPM not installed${NC}"
            local fpm_install_cmd=$(get_install_command "php${php_version}-fpm")
            echo -e "   ${RED}Install with: $fpm_install_cmd${NC}"
            ;;
        "nginx_not_installed")
            echo -e "   ${RED}❌ Nginx not installed${NC}"
            local nginx_install_cmd=$(get_install_command "nginx")
            echo -e "   ${RED}Install with: $nginx_install_cmd${NC}"
            ;;
    esac
    
    echo ""

    # Output options in a parseable format
    echo "=== AVAILABLE_OPTIONS ==="
    for option in "${options[@]}"; do
        echo "$option"
    done
    echo "=== END_OPTIONS ==="
}

# Function to validate deployment choice
validate_deployment() {
    local php_version=$1
    local deployment_type=$2
    
    echo -e "\n${PURPLE}🔍 Validating Deployment Configuration${NC}"
    echo -e "========================================"
    
    case $deployment_type in
        "php-cli")
            if command -v "php${php_version}" >/dev/null 2>&1; then
                echo -e "  ${GREEN}✅ PHP $php_version is available${NC}"
                return 0
            else
                echo -e "  ${RED}❌ PHP $php_version not found${NC}"
                echo -e "  ${RED}Install with: sudo apt install php${php_version}-cli${NC}"
                return 1
            fi
            ;;
        "apache-mod-php")
            local status=$(check_apache_mod_php "$php_version")
            case $status in
                "available_enabled")
                    echo -e "  ${GREEN}✅ Apache mod_php${php_version} is ready${NC}"
                    return 0
                    ;;
                "available_disabled")
                    echo -e "  ${YELLOW}⚠️  Apache mod_php${php_version} is installed but disabled${NC}"
                    echo -e "  ${BLUE}This deployment requires mod_php to be enabled${NC}"

                    # Check if PHP-FPM is running and warn about conflicts
                    if systemctl is-active "php${php_version}-fpm" >/dev/null 2>&1; then
                        echo -e "  ${RED}⚠️  CONFLICT DETECTED: PHP-FPM ${php_version} is currently running${NC}"
                        echo -e "  ${YELLOW}To use mod_php, the following changes will be made:${NC}"
                        echo -e "    ${BLUE}• Stop PHP-FPM ${php_version} service${NC}"
                        echo -e "    ${BLUE}• Disable Apache proxy modules (proxy, proxy_fcgi)${NC}"
                        echo -e "    ${BLUE}• Enable Apache mod_php${php_version}${NC}"
                        echo -e "    ${BLUE}• Restart Apache with new configuration${NC}"
                        echo ""
                        read -t 20 -p "Continue with mod_php setup? This will stop PHP-FPM (y/n): " enable_mod_php || enable_mod_php="n"
                    else
                        read -t 15 -p "Enable Apache mod_php${php_version} now? (y/n): " enable_mod_php || enable_mod_php="n"
                    fi

                    if [[ "$enable_mod_php" =~ ^[Yy]$ ]]; then
                        # Stop ALL PHP-FPM services
                        stop_all_php_fpm

                        # Disable ALL proxy modules
                        disable_proxy_modules

                        # Disable ALL other mod_php versions first
                        disable_all_mod_php

                        # Enable the specific mod_php version
                        echo -e "  ${BLUE}Enabling Apache mod_php${php_version}...${NC}"
                        if sudo a2enmod "php${php_version}" 2>/dev/null; then
                            echo -e "  ${GREEN}✅ Apache mod_php${php_version} enabled${NC}"

                            # Restart Apache to apply all changes
                            echo -e "  ${BLUE}Restarting Apache to apply changes...${NC}"
                            if sudo systemctl restart apache2 2>/dev/null; then
                                echo -e "  ${GREEN}✅ Apache restarted with mod_php configuration${NC}"
                            else
                                echo -e "  ${RED}❌ Failed to restart Apache${NC}"
                                return 1
                            fi
                            return 0
                        else
                            echo -e "  ${RED}❌ Failed to enable mod_php${php_version}${NC}"
                            return 1
                        fi
                    else
                        echo -e "  ${RED}❌ mod_php${php_version} is required for this deployment${NC}"
                        echo -e "  ${BLUE}You can enable it manually with: sudo a2enmod php${php_version}${NC}"
                        return 1
                    fi
                    ;;
                *)
                    echo -e "  ${RED}❌ Apache mod_php${php_version} not available${NC}"
                    return 1
                    ;;
            esac
            ;;
        "apache-fpm")
            # Check if Nginx is running and warn about conflicts
            if systemctl is-active nginx >/dev/null 2>&1; then
                echo -e "  ${RED}⚠️  CONFLICT DETECTED: Nginx is currently running${NC}"
                echo -e "  ${YELLOW}To use Apache PHP-FPM, the following changes will be made:${NC}"
                echo -e "    ${BLUE}• Stop Nginx service${NC}"
                echo -e "    ${BLUE}• Disable ALL Apache mod_php versions${NC}"
                echo -e "    ${BLUE}• Enable Apache proxy modules (proxy, proxy_fcgi)${NC}"
                echo -e "    ${BLUE}• Start Apache with PHP-FPM configuration${NC}"
                echo -e "    ${BLUE}• Start PHP-FPM ${php_version} service${NC}"
                echo ""
                read -t 20 -p "Continue with Apache PHP-FPM setup? This will stop Nginx (y/n): " enable_apache_fpm || enable_apache_fpm="n"

                if [[ "$enable_apache_fpm" =~ ^[Yy]$ ]]; then
                    # Stop Nginx
                    echo -e "  ${BLUE}Stopping Nginx...${NC}"
                    if sudo systemctl stop nginx 2>/dev/null; then
                        echo -e "  ${GREEN}✅ Nginx stopped${NC}"
                    else
                        echo -e "  ${RED}❌ Failed to stop Nginx${NC}"
                        return 1
                    fi

                    # Disable ALL mod_php versions
                    disable_all_mod_php

                    # Enable proxy modules for Apache
                    enable_proxy_modules

                    # Start Apache
                    echo -e "  ${BLUE}Starting Apache...${NC}"
                    if sudo systemctl start apache2 2>/dev/null; then
                        echo -e "  ${GREEN}✅ Apache started with PHP-FPM configuration${NC}"

                        # Start the specific PHP-FPM service
                        start_php_fpm "$php_version"
                    else
                        echo -e "  ${RED}❌ Failed to start Apache${NC}"
                        return 1
                    fi
                else
                    echo -e "  ${RED}❌ Apache PHP-FPM deployment requires stopping Nginx${NC}"
                    return 1
                fi
            # Check if ANY mod_php version is active and warn about conflicts
            elif check_any_mod_php_enabled; then
                echo -e "  ${RED}⚠️  CONFLICT DETECTED: One or more Apache mod_php versions are currently enabled${NC}"
                echo -e "  ${YELLOW}To use Apache PHP-FPM, the following changes will be made:${NC}"
                echo -e "    ${BLUE}• Disable ALL Apache mod_php versions${NC}"
                echo -e "    ${BLUE}• Enable Apache proxy modules (proxy, proxy_fcgi)${NC}"
                echo -e "    ${BLUE}• Start PHP-FPM ${php_version} service${NC}"
                echo -e "    ${BLUE}• Restart Apache with new configuration${NC}"
                echo ""
                read -t 20 -p "Continue with Apache PHP-FPM setup? This will disable all mod_php (y/n): " enable_apache_fpm || enable_apache_fpm="n"

                if [[ "$enable_apache_fpm" =~ ^[Yy]$ ]]; then
                    # Disable ALL mod_php versions
                    disable_all_mod_php

                    # Enable proxy modules for Apache
                    enable_proxy_modules

                    # Restart Apache
                    echo -e "  ${BLUE}Restarting Apache to apply changes...${NC}"
                    if sudo systemctl restart apache2 2>/dev/null; then
                        echo -e "  ${GREEN}✅ Apache restarted with PHP-FPM configuration${NC}"

                        # Start the specific PHP-FPM service
                        start_php_fpm "$php_version"
                    else
                        echo -e "  ${RED}❌ Failed to restart Apache${NC}"
                        return 1
                    fi
                else
                    echo -e "  ${RED}❌ Apache PHP-FPM deployment requires disabling all mod_php${NC}"
                    return 1
                fi
            fi
            ;;
        "nginx-fpm")
            # Check if Apache is running and warn about conflicts
            if systemctl is-active apache2 >/dev/null 2>&1; then
                echo -e "  ${RED}⚠️  CONFLICT DETECTED: Apache is currently running${NC}"
                echo -e "  ${YELLOW}To use Nginx PHP-FPM, the following changes will be made:${NC}"
                echo -e "    ${BLUE}• Stop Apache service${NC}"
                echo -e "    ${BLUE}• Disable ALL Apache mod_php versions${NC}"
                echo -e "    ${BLUE}• Start Nginx with PHP-FPM configuration${NC}"
                echo -e "    ${BLUE}• Start PHP-FPM ${php_version} service${NC}"
                echo ""
                read -t 20 -p "Continue with Nginx PHP-FPM setup? This will stop Apache (y/n): " enable_nginx_fpm || enable_nginx_fpm="n"

                if [[ "$enable_nginx_fpm" =~ ^[Yy]$ ]]; then
                    # Stop Apache
                    echo -e "  ${BLUE}Stopping Apache...${NC}"
                    if sudo systemctl stop apache2 2>/dev/null; then
                        echo -e "  ${GREEN}✅ Apache stopped${NC}"
                    else
                        echo -e "  ${RED}❌ Failed to stop Apache${NC}"
                        return 1
                    fi

                    # Disable ALL mod_php versions
                    disable_all_mod_php

                    # Start Nginx if not running
                    if ! systemctl is-active nginx >/dev/null 2>&1; then
                        echo -e "  ${BLUE}Starting Nginx...${NC}"
                        if sudo systemctl start nginx 2>/dev/null; then
                            echo -e "  ${GREEN}✅ Nginx started with PHP-FPM configuration${NC}"

                            # Start the specific PHP-FPM service
                            start_php_fpm "$php_version"
                        else
                            echo -e "  ${RED}❌ Failed to start Nginx${NC}"
                            return 1
                        fi
                    fi
                else
                    echo -e "  ${RED}❌ Nginx PHP-FPM deployment requires stopping Apache${NC}"
                    return 1
                fi
            # Check if ANY mod_php version is active and warn about conflicts
            elif check_any_mod_php_enabled; then
                echo -e "  ${RED}⚠️  CONFLICT DETECTED: One or more Apache mod_php versions are currently enabled${NC}"
                echo -e "  ${YELLOW}To use Nginx PHP-FPM, the following changes will be made:${NC}"
                echo -e "    ${BLUE}• Disable ALL Apache mod_php versions${NC}"
                echo -e "    ${BLUE}• Start Nginx with PHP-FPM configuration${NC}"
                echo -e "    ${BLUE}• Start PHP-FPM ${php_version} service${NC}"
                echo ""
                read -t 20 -p "Continue with Nginx PHP-FPM setup? This will disable all mod_php (y/n): " enable_nginx_fpm || enable_nginx_fmp="n"

                if [[ "$enable_nginx_fpm" =~ ^[Yy]$ ]]; then
                    # Disable ALL mod_php versions
                    disable_all_mod_php

                    # Start Nginx if not running
                    if ! systemctl is-active nginx >/dev/null 2>&1; then
                        echo -e "  ${BLUE}Starting Nginx...${NC}"
                        if sudo systemctl start nginx 2>/dev/null; then
                            echo -e "  ${GREEN}✅ Nginx started with PHP-FPM configuration${NC}"

                            # Start the specific PHP-FPM service
                            start_php_fpm "$php_version"
                        else
                            echo -e "  ${RED}❌ Failed to start Nginx${NC}"
                            return 1
                        fi
                    fi
                else
                    echo -e "  ${RED}❌ Nginx PHP-FPM deployment requires disabling all mod_php${NC}"
                    return 1
                fi
            fi

            local fpm_status=$(check_php_fpm "$php_version")
            case $fpm_status in
                "active")
                    echo -e "  ${GREEN}✅ PHP-FPM ${php_version} is running${NC}"
                    return 0
                    ;;
                "inactive")
                    start_php_fpm "$php_version"
                    return $?
                    ;;
                *)
                    echo -e "  ${RED}❌ PHP-FPM ${php_version} not available${NC}"
                    return 1
                    ;;
            esac
            ;;
        *)
            echo -e "  ${RED}❌ Unknown deployment type: $deployment_type${NC}"
            return 1
            ;;
    esac
}

# Function to generate deployment command
get_deployment_command() {
    local php_version=$1
    local deployment_type=$2
    local network_interface=$3
    local port=$4
    
    case $deployment_type in
        "php-cli")
            echo "php${php_version} -S ${network_interface}:${port} -t public"
            ;;
        "apache-mod-php")
            echo "# Apache with mod_php${php_version} - configure virtual host"
            ;;
        "apache-fpm")
            echo "# Apache with PHP-FPM ${php_version} - configure virtual host"
            ;;
        "nginx-fpm")
            echo "# Nginx with PHP-FPM ${php_version} - configure server block"
            ;;
        *)
            echo "# Unknown deployment type"
            ;;
    esac
}

# Main execution
main() {
    local action=${1:-"options"}
    local php_version=${2:-"8.4"}
    local deployment_type=${3:-""}
    local network_interface=${4:-"127.0.0.1"}
    local port=${5:-"8001"}
    
    case $action in
        "options")
            get_deployment_options "$php_version"
            ;;
        "validate")
            validate_deployment "$php_version" "$deployment_type"
            ;;
        "command")
            get_deployment_command "$php_version" "$deployment_type" "$network_interface" "$port"
            ;;
        "check-apache-mod")
            check_apache_mod_php "$php_version"
            ;;
        "check-fpm")
            check_php_fpm "$php_version"
            ;;
        *)
            echo -e "${YELLOW}Usage: $0 {options|validate|command|check-apache-mod|check-fpm} [php_version] [deployment_type] [network] [port]${NC}"
            echo -e ""
            echo -e "Commands:"
            echo -e "  options           - Show deployment options for PHP version"
            echo -e "  validate          - Validate deployment configuration"
            echo -e "  command           - Get deployment command"
            echo -e "  check-apache-mod  - Check Apache mod_php status"
            echo -e "  check-fpm         - Check PHP-FPM status"
            ;;
    esac
}

# Execute main function
main "$@"
