#!/bin/bash

# APM PHP Examples - PHP Version Selection Script
# This script allows users to select and configure specific PHP versions

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration file path
CONFIG_FILE="config/deployment.env"

echo -e "${BLUE}🐘 PHP Version Selection${NC}"
echo "========================"

# Available PHP versions
PHP_VERSIONS=("8.1" "8.2" "8.3" "8.4")

# Function to display PHP version information
show_php_version_info() {
    local version="$1"
    
    case "$version" in
        "8.1")
            echo -e "${YELLOW}PHP 8.1${NC} - Stable LTS version"
            echo "  • Released: November 2021"
            echo "  • Support until: November 2024"
            echo "  • Features: Enums, Fibers, readonly properties"
            ;;
        "8.2")
            echo -e "${YELLOW}PHP 8.2${NC} - Current stable version"
            echo "  • Released: December 2022"
            echo "  • Support until: December 2025"
            echo "  • Features: readonly classes, DNF types, new random extension"
            ;;
        "8.3")
            echo -e "${YELLOW}PHP 8.3${NC} - Latest stable version"
            echo "  • Released: November 2023"
            echo "  • Support until: November 2026"
            echo "  • Features: typed class constants, #[Override] attribute"
            ;;
        "8.4")
            echo -e "${YELLOW}PHP 8.4${NC} - Latest version (recommended)"
            echo "  • Released: November 2024"
            echo "  • Support until: November 2027"
            echo "  • Features: property hooks, asymmetric visibility"
            ;;
    esac
    echo ""
}

# Display available PHP versions
echo -e "${BLUE}Available PHP Versions:${NC}"
echo "======================="
for version in "${PHP_VERSIONS[@]}"; do
    show_php_version_info "$version"
done

# Get user selection
echo -e "${BLUE}Select PHP Version:${NC}"
echo "1. PHP 8.1 (LTS)"
echo "2. PHP 8.2 (Stable)"
echo "3. PHP 8.3 (Latest Stable)"
echo "4. PHP 8.4 (Latest - Recommended)"
echo ""

while true; do
    read -p "Enter your choice (1-4): " choice
    case $choice in
        1) SELECTED_PHP_VERSION="8.1"; break;;
        2) SELECTED_PHP_VERSION="8.2"; break;;
        3) SELECTED_PHP_VERSION="8.3"; break;;
        4) SELECTED_PHP_VERSION="8.4"; break;;
        *) echo -e "${RED}Invalid choice. Please enter 1-4.${NC}";;
    esac
done

echo -e "\n${GREEN}Selected PHP Version: $SELECTED_PHP_VERSION${NC}"

# Check if configuration file exists
if [ ! -f "$CONFIG_FILE" ]; then
    echo -e "${YELLOW}Configuration file not found. Creating default configuration...${NC}"
    mkdir -p config
    cat > "$CONFIG_FILE" << EOF
# APM PHP Examples - Deployment Configuration
# Generated on $(date)

# PHP Version Configuration
PHP_VERSION=$SELECTED_PHP_VERSION

# Network Configuration
NETWORK_INTERFACE=0.0.0.0

# Port Configuration
SIMPLE_PHP_PORT=8080
LARAVEL_PORT=8081
SYMFONY_PORT=8082
SLIM_PORT=8083
CODEIGNITER_PORT=8084

# Deployment Type Configuration
DEPLOYMENT_TYPE=apache-mod-php

# Environment Configuration
APP_ENV=production
APP_DEBUG=false
EOF
else
    # Update existing configuration
    echo -e "${YELLOW}Updating PHP version in existing configuration...${NC}"
    if grep -q "^PHP_VERSION=" "$CONFIG_FILE"; then
        sed -i "s/^PHP_VERSION=.*/PHP_VERSION=$SELECTED_PHP_VERSION/" "$CONFIG_FILE"
    else
        echo "PHP_VERSION=$SELECTED_PHP_VERSION" >> "$CONFIG_FILE"
    fi
fi

echo -e "${GREEN}✅ PHP version updated in configuration${NC}"

# Check for compatibility issues
echo -e "\n${BLUE}Checking compatibility...${NC}"

case "$SELECTED_PHP_VERSION" in
    "8.1")
        echo -e "${YELLOW}⚠️  PHP 8.1 Notes:${NC}"
        echo "  • Some newer features may not be available"
        echo "  • Ensure all dependencies support PHP 8.1"
        ;;
    "8.2")
        echo -e "${GREEN}✅ PHP 8.2 is well supported${NC}"
        echo "  • Good balance of features and stability"
        ;;
    "8.3")
        echo -e "${GREEN}✅ PHP 8.3 is recommended${NC}"
        echo "  • Latest stable features"
        echo "  • Excellent performance"
        ;;
    "8.4")
        echo -e "${GREEN}✅ PHP 8.4 is the latest version${NC}"
        echo "  • Cutting-edge features"
        echo "  • Best performance"
        echo -e "${YELLOW}⚠️  Note: Some packages may not yet support PHP 8.4${NC}"
        ;;
esac

# Offer to rebuild containers
echo -e "\n${BLUE}Next Steps:${NC}"
echo "==========="
echo "1. Your PHP version has been set to $SELECTED_PHP_VERSION"
echo "2. All applications will use this PHP version when built"
echo "3. Run the following commands to apply changes:"
echo ""
echo -e "${YELLOW}  ./scripts/configure-deployment.sh${NC}  # Configure deployment settings"
echo -e "${YELLOW}  make clean${NC}                        # Clean existing containers"
echo -e "${YELLOW}  make setup${NC}                        # Build with new PHP version"
echo -e "${YELLOW}  make start${NC}                        # Start applications"
echo ""

# Ask if user wants to rebuild now
read -p "Would you like to rebuild all containers with PHP $SELECTED_PHP_VERSION now? (y/N): " rebuild
case $rebuild in
    [Yy]*)
        echo -e "\n${YELLOW}Rebuilding containers with PHP $SELECTED_PHP_VERSION...${NC}"
        
        # Clean existing containers
        echo -e "${BLUE}Cleaning existing containers...${NC}"
        make clean 2>/dev/null || true
        
        # Rebuild with new PHP version
        echo -e "${BLUE}Building with PHP $SELECTED_PHP_VERSION...${NC}"
        make setup
        
        echo -e "\n${GREEN}🎉 All containers rebuilt with PHP $SELECTED_PHP_VERSION!${NC}"
        echo -e "${BLUE}You can now start the applications with:${NC} make start"
        ;;
    *)
        echo -e "\n${YELLOW}Containers not rebuilt. Run 'make clean && make setup' when ready.${NC}"
        ;;
esac

# Display final summary
echo -e "\n${GREEN}📋 Configuration Summary:${NC}"
echo "========================="
echo "PHP Version: $SELECTED_PHP_VERSION"
if [ -f "$CONFIG_FILE" ]; then
    echo "Configuration file: $CONFIG_FILE"
    echo ""
    echo "Current settings:"
    grep -E "^(PHP_VERSION|DEPLOYMENT_TYPE|NETWORK_INTERFACE)" "$CONFIG_FILE" | sed 's/^/  /'
fi

echo -e "\n${BLUE}🔧 Available Commands:${NC}"
echo "====================="
echo "  ./scripts/select-php-version.sh     # Change PHP version"
echo "  ./scripts/configure-deployment.sh   # Configure deployment"
echo "  make setup                          # Build applications"
echo "  make start                          # Start applications"
echo "  make test                           # Run tests"
echo "  make logs                           # View logs"
