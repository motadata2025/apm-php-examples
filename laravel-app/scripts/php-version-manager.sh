#!/bin/bash

# PHP Version Manager - Smart version consistency checking
# APM PHP Examples - Laravel Application

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
VERSION_STATE_FILE="$CONFIG_DIR/php-version.state"
APP_CONFIG_FILE="$CONFIG_DIR/app.env"

# Ensure config directory exists
mkdir -p "$CONFIG_DIR"

# Function to get currently configured PHP version
get_configured_version() {
    if [ -f "$VERSION_STATE_FILE" ]; then
        grep "CONFIGURED_PHP_VERSION=" "$VERSION_STATE_FILE" 2>/dev/null | cut -d'=' -f2 || echo ""
    else
        echo ""
    fi
}

# Function to get available PHP versions
get_available_php_versions() {
    local versions=()
    for version in 8.1 8.2 8.3 8.4; do
        if command -v "php${version}" >/dev/null 2>&1; then
            versions+=("$version")
        fi
    done
    echo "${versions[@]}"
}

# Function to get available PHP-FPM services
get_available_fpm_services() {
    local fpm_versions=()
    for version in 8.1 8.2 8.3 8.4; do
        if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
            fpm_versions+=("$version")
        elif systemctl list-unit-files "php${version}-fpm.service" 2>/dev/null | grep -q "php${version}-fpm.service"; then
            fpm_versions+=("$version (inactive)")
        fi
    done
    echo "${fpm_versions[@]}"
}

# Function to check if PHP version has corresponding FPM service
check_fpm_compatibility() {
    local php_version=$1
    local deployment_type=$2
    
    if [[ "$deployment_type" == *"fpm"* ]]; then
        if systemctl is-active --quiet "php${php_version}-fpm" 2>/dev/null; then
            return 0  # Compatible and running
        elif systemctl list-unit-files "php${php_version}-fpm.service" 2>/dev/null | grep -q "php${php_version}-fpm.service"; then
            return 1  # Compatible but not running
        else
            return 2  # Not compatible
        fi
    fi
    return 0  # Not FPM deployment, no check needed
}

# Function to display PHP version status
show_php_status() {
    echo -e "\n${PURPLE}🐘 PHP Version Status${NC}"
    echo -e "========================"
    
    local configured_version=$(get_configured_version)
    local available_versions=($(get_available_php_versions))
    local fpm_services=($(get_available_fpm_services))
    
    echo -e "${BLUE}Available PHP versions:${NC}"
    for version in "${available_versions[@]}"; do
        if [ "$version" = "$configured_version" ]; then
            echo -e "  ${GREEN}✅ PHP $version (configured)${NC}"
        else
            echo -e "  ${YELLOW}📦 PHP $version${NC}"
        fi
    done
    
    echo -e "\n${BLUE}Available PHP-FPM services:${NC}"
    if [ ${#fpm_services[@]} -eq 0 ]; then
        echo -e "  ${YELLOW}⚠️  No PHP-FPM services found${NC}"
    else
        for service in "${fpm_services[@]}"; do
            echo -e "  ${GREEN}✅ PHP-FPM $service${NC}"
        done
    fi
    
    if [ -n "$configured_version" ]; then
        echo -e "\n${BLUE}Current configuration:${NC}"
        echo -e "  ${GREEN}PHP Version: $configured_version${NC}"
        
        # Check if app config exists
        if [ -f "$APP_CONFIG_FILE" ]; then
            local deployment_type=$(grep "DEPLOYMENT_TYPE=" "$APP_CONFIG_FILE" 2>/dev/null | cut -d'=' -f2 || echo "")
            if [ -n "$deployment_type" ]; then
                echo -e "  ${GREEN}Deployment: $deployment_type${NC}"
                
                # Check compatibility
                check_fpm_compatibility "$configured_version" "$deployment_type"
                local compat_result=$?
                
                case $compat_result in
                    0)
                        echo -e "  ${GREEN}✅ Configuration is compatible${NC}"
                        ;;
                    1)
                        echo -e "  ${YELLOW}⚠️  PHP-FPM service exists but not running${NC}"
                        echo -e "  ${YELLOW}    Run: sudo systemctl start php${configured_version}-fpm${NC}"
                        ;;
                    2)
                        echo -e "  ${RED}❌ PHP-FPM service not available for PHP $configured_version${NC}"
                        echo -e "  ${RED}    Install: sudo apt install php${configured_version}-fpm${NC}"
                        ;;
                esac
            fi
        fi
    else
        echo -e "\n${YELLOW}⚠️  No PHP version configured yet${NC}"
        echo -e "  ${BLUE}Run: make setup or make compile${NC}"
    fi
}

# Function to validate PHP version selection
validate_php_selection() {
    local new_version=$1
    local deployment_type=$2
    local configured_version=$(get_configured_version)
    
    echo -e "\n${PURPLE}🔍 PHP Version Validation${NC}"
    
    # Check if version is changing
    if [ -n "$configured_version" ] && [ "$configured_version" != "$new_version" ]; then
        echo -e "${YELLOW}⚠️  WARNING: Changing PHP version${NC}"
        echo -e "  ${YELLOW}Previous: PHP $configured_version${NC}"
        echo -e "  ${YELLOW}New: PHP $new_version${NC}"
        echo -e ""
        
        # Check if there are compatibility issues
        if [ -n "$deployment_type" ]; then
            check_fpm_compatibility "$new_version" "$deployment_type"
            local compat_result=$?
            
            case $compat_result in
                1)
                    echo -e "  ${YELLOW}⚠️  PHP-FPM service for $new_version exists but not running${NC}"
                    echo -e "  ${YELLOW}    You may need to start: sudo systemctl start php${new_version}-fpm${NC}"
                    ;;
                2)
                    echo -e "  ${RED}❌ CRITICAL: No PHP-FPM service for $new_version${NC}"
                    echo -e "  ${RED}    Install required: sudo apt install php${new_version}-fpm${NC}"
                    echo -e "  ${RED}    Or choose a different deployment type${NC}"
                    return 1
                    ;;
            esac
        fi
        
        read -t 10 -p "Continue with PHP $new_version? (y/n): " confirm || confirm="y"
        if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
            echo -e "  ${RED}❌ PHP version change cancelled${NC}"
            return 1
        fi
    fi
    
    return 0
}

# Function to save PHP version state
save_php_version() {
    local version=$1
    local deployment_type=${2:-""}
    
    cat > "$VERSION_STATE_FILE" << EOF
# PHP Version State - Generated on $(date)
CONFIGURED_PHP_VERSION=$version
LAST_UPDATED=$(date '+%Y-%m-%d %H:%M:%S')
DEPLOYMENT_TYPE=$deployment_type
EOF
    
    echo -e "  ${GREEN}✅ PHP version $version saved to state${NC}"
}

# Function to get recommended PHP version
get_recommended_version() {
    local available_versions=($(get_available_php_versions))
    local fpm_services=($(get_available_fpm_services))
    
    # Find the highest version that has both PHP and FPM available
    for version in 8.4 8.3 8.2 8.1; do
        if [[ " ${available_versions[@]} " =~ " ${version} " ]]; then
            # Check if FPM is available for this version
            if systemctl is-active --quiet "php${version}-fpm" 2>/dev/null; then
                echo "$version"
                return 0
            fi
        fi
    done
    
    # Fallback to highest available PHP version
    if [ ${#available_versions[@]} -gt 0 ]; then
        echo "${available_versions[-1]}"
    else
        echo "8.4"  # Default fallback
    fi
}

# Main execution
main() {
    local action=${1:-"status"}
    local version=${2:-""}
    local deployment_type=${3:-""}
    
    case $action in
        "status")
            show_php_status
            ;;
        "validate")
            validate_php_selection "$version" "$deployment_type"
            ;;
        "save")
            save_php_version "$version" "$deployment_type"
            ;;
        "recommend")
            get_recommended_version
            ;;
        "configured")
            get_configured_version
            ;;
        *)
            echo -e "${YELLOW}Usage: $0 {status|validate|save|recommend|configured} [version] [deployment_type]${NC}"
            echo -e ""
            echo -e "Commands:"
            echo -e "  status                    - Show PHP version status"
            echo -e "  validate <version> <type> - Validate PHP version selection"
            echo -e "  save <version> <type>     - Save PHP version state"
            echo -e "  recommend                 - Get recommended PHP version"
            echo -e "  configured                - Get currently configured version"
            ;;
    esac
}

# Execute main function
main "$@"
