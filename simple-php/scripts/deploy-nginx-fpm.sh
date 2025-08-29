#!/bin/bash

# Nginx PHP-FPM Deployment Script
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

# Application info
APP_NAME="simple-php"
VHOST_NAME="simple-php"

# Function to load configuration
load_configuration() {
    if [ ! -f "$APP_CONFIG_FILE" ]; then
        echo -e "${RED}❌ Configuration file not found${NC}"
        echo -e "${YELLOW}Run: make compile - to configure the application${NC}"
        exit 1
    fi
    
    source "$APP_CONFIG_FILE"
}

# Function to stop Apache if running
stop_apache_if_running() {
    echo -e "\n${PURPLE}🔄 Checking Apache Status${NC}"
    
    if systemctl is-active apache2 >/dev/null 2>&1; then
        echo -e "  ${YELLOW}⚠️  Apache is currently running${NC}"
        echo -e "  ${BLUE}Nginx and Apache cannot run simultaneously on the same ports${NC}"
        
        read -t 15 -p "Stop Apache to start Nginx? (y/n): " stop_apache || stop_apache="n"
        
        if [[ "$stop_apache" =~ ^[Yy]$ ]]; then
            echo -e "  ${BLUE}Stopping Apache...${NC}"
            if sudo systemctl stop apache2; then
                echo -e "  ${GREEN}✅ Apache stopped${NC}"
            else
                echo -e "  ${RED}❌ Failed to stop Apache${NC}"
                return 1
            fi
        else
            echo -e "  ${RED}❌ Cannot proceed with Nginx while Apache is running${NC}"
            return 1
        fi
    else
        echo -e "  ${GREEN}✅ Apache is not running${NC}"
    fi
}

# Function to copy application to Nginx directory
copy_application_to_nginx() {
    echo -e "\n${PURPLE}📁 Copying Application to Nginx Directory${NC}"
    
    local source_dir=$(pwd)
    local nginx_dir="/var/www/${VHOST_NAME}"
    
    echo -e "  ${BLUE}Source: $source_dir${NC}"
    echo -e "  ${BLUE}Destination: $nginx_dir${NC}"
    
    # Remove existing copy if it exists
    if [ -d "$nginx_dir" ]; then
        echo -e "  ${YELLOW}Removing existing copy...${NC}"
        sudo rm -rf "$nginx_dir"
    fi
    
    # Create Nginx directory
    sudo mkdir -p "$nginx_dir"
    
    # Copy application files
    echo -e "  ${BLUE}Copying application files...${NC}"
    sudo cp -r . "$nginx_dir/"
    
    # Ensure .env file is copied and readable
    if [ -f ".env" ]; then
        sudo cp .env "$nginx_dir/"
        echo -e "  ${GREEN}✅ Environment file copied${NC}"
    fi
    
    # Set proper ownership and permissions
    sudo chown -R www-data:www-data "$nginx_dir"
    sudo chmod -R 755 "$nginx_dir"
    sudo chmod -R 644 "$nginx_dir"/*.php "$nginx_dir"/public/*.php 2>/dev/null || true
    sudo chmod 644 "$nginx_dir/.env" 2>/dev/null || true
    
    # Ensure public directory has correct permissions
    sudo chmod 755 "$nginx_dir/public"
    sudo chmod 644 "$nginx_dir/public/index.php"
    
    echo -e "  ${GREEN}✅ Application copied successfully${NC}"
    
    # Store the nginx directory path for cleanup
    echo "NGINX_APP_DIR=\"$nginx_dir\"" >> "$CONFIG_DIR/app.env"
}

# Function to create Nginx virtual host configuration
create_nginx_vhost() {
    echo -e "\n${PURPLE}🌐 Creating Nginx Virtual Host Configuration${NC}"
    
    local nginx_dir="/var/www/${VHOST_NAME}"
    local document_root="$nginx_dir/public"
    local server_name="simple-php.local"
    local vhost_file="/etc/nginx/sites-available/${VHOST_NAME}"
    
    echo -e "  ${BLUE}Document Root: $document_root${NC}"
    echo -e "  ${BLUE}Server Name: $server_name${NC}"
    echo -e "  ${BLUE}PHP Version: $PHP_VERSION${NC}"
    echo -e "  ${BLUE}Port: $APP_PORT${NC}"
    
    # Create virtual host configuration for Nginx with PHP-FPM
    sudo tee "$vhost_file" > /dev/null << EOF
server {
    listen $APP_PORT;
    server_name $server_name localhost;
    root $document_root;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options DENY always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Main location block
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
        
        # Timeouts
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;
    }

    # Security: Deny access to sensitive files
    location ~ /\.env {
        deny all;
        return 404;
    }

    location ~ /(composer\.(json|lock)|package\.json) {
        deny all;
        return 404;
    }

    location ~ /vendor/ {
        deny all;
        return 404;
    }

    # Static files caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Logging
    access_log /var/log/nginx/${VHOST_NAME}_access.log;
    error_log /var/log/nginx/${VHOST_NAME}_error.log;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
EOF
    
    echo -e "  ${GREEN}✅ Nginx virtual host configuration created${NC}"
    return 0
}

# Function to enable the Nginx site
enable_nginx_site() {
    echo -e "\n${PURPLE}🌐 Enabling Nginx Site${NC}"
    
    # Create symlink to enable site
    if [ ! -L "/etc/nginx/sites-enabled/${VHOST_NAME}" ]; then
        sudo ln -s "/etc/nginx/sites-available/${VHOST_NAME}" "/etc/nginx/sites-enabled/${VHOST_NAME}"
        echo -e "  ${GREEN}✅ Site ${VHOST_NAME} enabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Site ${VHOST_NAME} already enabled${NC}"
    fi
}

# Function to test Nginx configuration
test_nginx_config() {
    echo -e "\n${PURPLE}🔍 Testing Nginx Configuration${NC}"
    
    if sudo nginx -t 2>/dev/null; then
        echo -e "  ${GREEN}✅ Nginx configuration is valid${NC}"
        return 0
    else
        echo -e "  ${RED}❌ Nginx configuration has errors${NC}"
        return 1
    fi
}

# Function to start Nginx
start_nginx() {
    echo -e "\n${PURPLE}🚀 Starting Nginx${NC}"
    
    if sudo systemctl start nginx; then
        echo -e "  ${GREEN}✅ Nginx started successfully${NC}"
    else
        echo -e "  ${RED}❌ Failed to start Nginx${NC}"
        return 1
    fi
}

# Function to reload Nginx
reload_nginx() {
    echo -e "\n${PURPLE}🔄 Reloading Nginx${NC}"
    
    if sudo systemctl reload nginx; then
        echo -e "  ${GREEN}✅ Nginx reloaded successfully${NC}"
    else
        echo -e "  ${RED}❌ Failed to reload Nginx${NC}"
        return 1
    fi
}

# Function to verify PHP-FPM is working
verify_php_fpm() {
    echo -e "\n${PURPLE}🐘 Verifying PHP-FPM${NC}"
    
    if systemctl is-active "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then
        echo -e "  ${GREEN}✅ PHP-FPM ${PHP_VERSION} is running${NC}"
    else
        echo -e "  ${RED}❌ PHP-FPM ${PHP_VERSION} is not running${NC}"
        return 1
    fi
}

# Function to show access information
show_access_info() {
    echo -e "\n${PURPLE}🌐 Nginx PHP-FPM Deployment Complete${NC}"
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
    echo -e "  ${GREEN}Web Server: Nginx with PHP-FPM${NC}"
    echo -e "  ${GREEN}PHP Version: $PHP_VERSION${NC}"
    echo -e "  ${GREEN}Document Root: /var/www/${VHOST_NAME}/public${NC}"
    echo -e "  ${GREEN}Virtual Host: /etc/nginx/sites-available/${VHOST_NAME}${NC}"
    
    echo -e "\n${BLUE}Management Commands:${NC}"
    echo -e "  ${YELLOW}make stop${NC}     - Stop the application"
    echo -e "  ${YELLOW}make disable${NC}  - Disable the Nginx site"
    echo -e "  ${YELLOW}make status${NC}   - Check application status"
    
    echo -e "\n${BLUE}Log Files:${NC}"
    echo -e "  ${YELLOW}Error Log: /var/log/nginx/${VHOST_NAME}_error.log${NC}"
    echo -e "  ${YELLOW}Access Log: /var/log/nginx/${VHOST_NAME}_access.log${NC}"
}

# Main execution
main() {
    echo -e "${BLUE}🚀 Nginx PHP-FPM Deployment${NC}"
    echo -e "============================="
    
    load_configuration
    stop_apache_if_running
    verify_php_fpm
    copy_application_to_nginx
    create_nginx_vhost
    enable_nginx_site
    
    if test_nginx_config; then
        if systemctl is-active nginx >/dev/null 2>&1; then
            reload_nginx
        else
            start_nginx
        fi
        show_access_info
    else
        echo -e "${RED}❌ Deployment failed due to Nginx configuration errors${NC}"
        exit 1
    fi
}

# Execute main function
main "$@"
