#!/bin/bash

# Apache PHP-FPM Deployment Script
# APM PHP Examples - Simple PHP Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration files
CONFIG_DIR="config"
APP_CONFIG_FILE="$CONFIG_DIR/app.env"

# Application info - Auto-detect from directory name
CURRENT_DIR=$(basename "$(pwd)")
APP_NAME="$CURRENT_DIR"
VHOST_NAME="$CURRENT_DIR"

# Function to load configuration
load_configuration() {
    if [ ! -f "$APP_CONFIG_FILE" ]; then
        echo -e "${RED}❌ Configuration file not found${NC}"
        echo -e "${YELLOW}Run: make compile - to configure the application${NC}"
        exit 1
    fi
    
    source "$APP_CONFIG_FILE"
}

# Function to copy application to Apache directory
copy_application_to_apache() {
    echo -e "\n${PURPLE}📁 Copying Application to Apache Directory${NC}"

    local source_dir=$(pwd)
    local apache_dir="/var/www/${VHOST_NAME}"

    echo -e "  ${BLUE}Source: $source_dir${NC}"
    echo -e "  ${BLUE}Destination: $apache_dir${NC}"

    # Remove existing copy if it exists
    if [ -d "$apache_dir" ]; then
        echo -e "  ${YELLOW}Removing existing copy...${NC}"
        sudo rm -rf "$apache_dir"
    fi

    # Create Apache directory
    sudo mkdir -p "$apache_dir"

    # Copy application files
    echo -e "  ${BLUE}Copying application files...${NC}"
    sudo cp -r . "$apache_dir/"

    # Ensure .env file is copied and readable
    if [ -f ".env" ]; then
        sudo cp .env "$apache_dir/"
        echo -e "  ${GREEN}✅ Environment file copied${NC}"
    fi

    # Ensure .htaccess file is copied for URL rewriting
    if [ -f "public/.htaccess" ]; then
        sudo cp public/.htaccess "$apache_dir/public/"
        echo -e "  ${GREEN}✅ .htaccess file copied${NC}"
    fi

    # Set proper ownership and permissions
    sudo chown -R www-data:www-data "$apache_dir"
    sudo chmod -R 755 "$apache_dir"
    sudo chmod -R 644 "$apache_dir"/*.php "$apache_dir"/public/*.php 2>/dev/null || true
    sudo chmod 644 "$apache_dir/.env" 2>/dev/null || true

    # Ensure public directory has correct permissions
    sudo chmod 755 "$apache_dir/public"
    sudo chmod 644 "$apache_dir/public/index.php"

    echo -e "  ${GREEN}✅ Application copied successfully${NC}"

    # Store the apache directory path for cleanup
    echo "APACHE_APP_DIR=\"$apache_dir\"" >> "$CONFIG_DIR/app.env"
}

# Function to create Apache virtual host configuration
create_apache_vhost() {
    echo -e "\n${PURPLE}🌐 Creating Apache Virtual Host Configuration${NC}"

    local apache_dir="/var/www/${VHOST_NAME}"
    local document_root="$apache_dir/public"
    local server_name="${VHOST_NAME}.local"
    local vhost_file="/etc/apache2/sites-available/${VHOST_NAME}.conf"
    
    echo -e "  ${BLUE}Document Root: $document_root${NC}"
    echo -e "  ${BLUE}Server Name: $server_name${NC}"
    echo -e "  ${BLUE}PHP Version: $PHP_VERSION${NC}"
    echo -e "  ${BLUE}Port: $APP_PORT${NC}"
    
    # Create virtual host configuration
    sudo tee "$vhost_file" > /dev/null << EOF
<VirtualHost *:$APP_PORT>
    ServerName $server_name
    DocumentRoot $document_root

    # PHP-FPM Configuration
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php/php${PHP_VERSION}-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    # Directory Configuration - Main document root
    <Directory $document_root>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"

        # PHP Configuration
        DirectoryIndex index.php index.html

        # Ensure PHP files are processed
        <Files "*.php">
            Require all granted
        </Files>
    </Directory>

    # Allow access to application root directory
    <Directory $apache_dir>
        Options -Indexes +FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/${VHOST_NAME}_error.log
    CustomLog \${APACHE_LOG_DIR}/${VHOST_NAME}_access.log combined
    
    # Security: Hide sensitive files
    <Files ".env">
        Require all denied
    </Files>
    
    <Files "composer.json">
        Require all denied
    </Files>
    
    <Files "composer.lock">
        Require all denied
    </Files>
    
    <DirectoryMatch "/vendor/">
        Require all denied
    </DirectoryMatch>
</VirtualHost>
EOF
    
    echo -e "  ${GREEN}✅ Virtual host configuration created${NC}"
    return 0
}

# Function to configure Apache for the specific port
configure_apache_port() {
    echo -e "\n${PURPLE}🔧 Configuring Apache Port${NC}"
    
    # Check if port is already configured
    if grep -q "Listen $APP_PORT" /etc/apache2/ports.conf; then
        echo -e "  ${GREEN}✅ Port $APP_PORT already configured${NC}"
    else
        echo -e "  ${BLUE}Adding port $APP_PORT to Apache configuration${NC}"
        echo "Listen $APP_PORT" | sudo tee -a /etc/apache2/ports.conf > /dev/null
        echo -e "  ${GREEN}✅ Port $APP_PORT added to Apache${NC}"
    fi
}

# Function to enable required Apache modules
enable_apache_modules() {
    echo -e "\n${PURPLE}🔌 Enabling Required Apache Modules${NC}"
    
    local modules=("rewrite" "headers" "proxy" "proxy_fcgi")
    
    for module in "${modules[@]}"; do
        if sudo a2enmod "$module" 2>/dev/null; then
            echo -e "  ${GREEN}✅ Module $module enabled${NC}"
        else
            echo -e "  ${YELLOW}⚠️  Module $module already enabled${NC}"
        fi
    done
}

# Function to enable the site
enable_site() {
    echo -e "\n${PURPLE}🌐 Enabling Apache Site${NC}"
    
    if sudo a2ensite "${VHOST_NAME}.conf" 2>/dev/null; then
        echo -e "  ${GREEN}✅ Site ${VHOST_NAME} enabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Site ${VHOST_NAME} already enabled${NC}"
    fi
}

# Function to test Apache configuration
test_apache_config() {
    echo -e "\n${PURPLE}🔍 Testing Apache Configuration${NC}"
    
    if sudo apache2ctl configtest 2>/dev/null; then
        echo -e "  ${GREEN}✅ Apache configuration is valid${NC}"
        return 0
    else
        echo -e "  ${RED}❌ Apache configuration has errors${NC}"
        return 1
    fi
}

# Function to reload Apache
reload_apache() {
    echo -e "\n${PURPLE}🔄 Reloading Apache${NC}"
    
    if sudo systemctl reload apache2; then
        echo -e "  ${GREEN}✅ Apache reloaded successfully${NC}"
    else
        echo -e "  ${RED}❌ Failed to reload Apache${NC}"
        return 1
    fi
}

# Function to verify PHP-FPM is running
verify_php_fpm() {
    echo -e "\n${PURPLE}🐘 Verifying PHP-FPM${NC}"
    
    if systemctl is-active --quiet "php${PHP_VERSION}-fpm"; then
        echo -e "  ${GREEN}✅ PHP-FPM ${PHP_VERSION} is running${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Starting PHP-FPM ${PHP_VERSION}...${NC}"
        if sudo systemctl start "php${PHP_VERSION}-fpm"; then
            echo -e "  ${GREEN}✅ PHP-FPM ${PHP_VERSION} started${NC}"
        else
            echo -e "  ${RED}❌ Failed to start PHP-FPM ${PHP_VERSION}${NC}"
            return 1
        fi
    fi
}

# Function to show access information
show_access_info() {
    echo -e "\n${PURPLE}🌐 Apache PHP-FPM Deployment Complete${NC}"
    echo -e "========================================"
    
    local current_ip=$(./scripts/network-manager.sh current-ip 2>/dev/null || echo "localhost")
    
    echo -e "\n${GREEN}✅ Application deployed successfully!${NC}"
    echo -e "\n${BLUE}Access Information:${NC}"
    
    if [ "$NETWORK_INTERFACE" = "127.0.0.1" ]; then
        echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
    elif [ "$NETWORK_INTERFACE" = "0.0.0.0" ]; then
        echo -e "  ${BLUE}Local access: http://127.0.0.1:$APP_PORT${NC}"
        echo -e "  ${BLUE}Network access: http://$current_ip:$APP_PORT${NC}"
        echo -e "  ${GREEN}✅ Accessible from any IP (dynamic IP safe)${NC}"
    else
        echo -e "  ${BLUE}Access: http://$NETWORK_INTERFACE:$APP_PORT${NC}"
    fi
    
    echo -e "\n${BLUE}Server Configuration:${NC}"
    echo -e "  ${GREEN}Web Server: Apache with PHP-FPM${NC}"
    echo -e "  ${GREEN}PHP Version: $PHP_VERSION${NC}"
    echo -e "  ${GREEN}Document Root: /var/www/${VHOST_NAME}/public${NC}"
    echo -e "  ${GREEN}Virtual Host: /etc/apache2/sites-available/${VHOST_NAME}.conf${NC}"
    
    echo -e "\n${BLUE}Management Commands:${NC}"
    echo -e "  ${YELLOW}make stop${NC}     - Stop the application"
    echo -e "  ${YELLOW}make disable${NC}  - Disable the Apache site"
    echo -e "  ${YELLOW}make status${NC}   - Check application status"
    
    echo -e "\n${BLUE}Log Files:${NC}"
    echo -e "  ${YELLOW}Error Log: /var/log/apache2/${VHOST_NAME}_error.log${NC}"
    echo -e "  ${YELLOW}Access Log: /var/log/apache2/${VHOST_NAME}_access.log${NC}"
}

# Main execution
main() {
    echo -e "${BLUE}🚀 Apache PHP-FPM Deployment${NC}"
    echo -e "============================="
    
    load_configuration
    verify_php_fpm
    copy_application_to_apache
    enable_apache_modules
    configure_apache_port
    create_apache_vhost
    enable_site
    
    if test_apache_config; then
        reload_apache
        show_access_info
    else
        echo -e "${RED}❌ Deployment failed due to Apache configuration errors${NC}"
        exit 1
    fi
}

# Execute main function
main "$@"
