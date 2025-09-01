#!/bin/bash

# APM PHP Examples - Fix Application-Specific Details in Scripts
# Updates all copied scripts to use correct application names, ports, and paths

set -e

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m' # No Color

# Configuration
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Application configurations
declare -A APP_CONFIGS
APP_CONFIGS["simple-php"]="simple-php,8000,3307,5433,6380,simple_php_db"
APP_CONFIGS["slim-framework"]="slim-framework,8001,3309,5435,6382,slim_framework_db"
APP_CONFIGS["symfony-app"]="symfony-app,8002,3308,5434,6381,symfony_app_db"
APP_CONFIGS["codeigniter-app"]="codeigniter-app,8003,3310,5436,6383,codeigniter_app_db"
APP_CONFIGS["laravel-app"]="laravel-app,8004,3311,5437,6384,laravel_app_db"

# Scripts that need application-specific updates
readonly SCRIPTS_TO_UPDATE=(
    "check-system-hardened.sh"
    "compile-app.sh"
    "compile-app-enhanced.sh"
    "start-app.sh"
    "stop-app.sh"
    "enable-app.sh"
    "disable-app.sh"
    "migrate-config.sh"
    "network-manager.sh"
    "production-scaling.sh"
    "webserver-manager.sh"
    "php-version-manager.sh"
)

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Parse application configuration
parse_app_config() {
    local app="$1"
    local config="${APP_CONFIGS[$app]}"
    
    if [[ -z "$config" ]]; then
        log_error "Unknown application: $app"
        return 1
    fi
    
    IFS=',' read -r APP_NAME APP_PORT MYSQL_PORT POSTGRES_PORT REDIS_PORT DATABASE_NAME <<< "$config"
}

# Update script with application-specific details
update_script() {
    local app="$1"
    local script_name="$2"
    local script_path="$REPO_ROOT/$app/scripts/$script_name"
    
    if [[ ! -f "$script_path" ]]; then
        log_warning "$app: Script $script_name not found"
        return 1
    fi
    
    # Parse configuration for this app
    parse_app_config "$app"
    
    log_info "Updating $script_name for $app"
    
    # Create backup
    cp "$script_path" "$script_path.backup"
    
    # Update application-specific values
    sed -i "s/simple-php/$APP_NAME/g" "$script_path"
    sed -i "s/simple_php_db/$DATABASE_NAME/g" "$script_path"
    
    # Update ports
    sed -i "s/DEFAULT_PORT=8000/DEFAULT_PORT=$APP_PORT/g" "$script_path"
    sed -i "s/PORT_RANGE_START=8000/PORT_RANGE_START=$APP_PORT/g" "$script_path"
    sed -i "s/DB_PORT=3307/DB_PORT=$MYSQL_PORT/g" "$script_path"
    sed -i "s/MYSQL_PORT=3307/MYSQL_PORT=$MYSQL_PORT/g" "$script_path"
    sed -i "s/POSTGRES_PORT=5433/POSTGRES_PORT=$POSTGRES_PORT/g" "$script_path"
    sed -i "s/REDIS_PORT=6380/REDIS_PORT=$REDIS_PORT/g" "$script_path"
    
    # Update hardcoded port values
    sed -i "s/mysql_port=3307/mysql_port=$MYSQL_PORT/g" "$script_path"
    sed -i "s/postgres_port=5433/postgres_port=$POSTGRES_PORT/g" "$script_path"
    sed -i "s/redis_port=6380/redis_port=$REDIS_PORT/g" "$script_path"
    sed -i "s/MYSQL_PORT:-3307/MYSQL_PORT:-$MYSQL_PORT/g" "$script_path"
    sed -i "s/POSTGRES_PORT:-5433/POSTGRES_PORT:-$POSTGRES_PORT/g" "$script_path"
    sed -i "s/REDIS_PORT:-6380/REDIS_PORT:-$REDIS_PORT/g" "$script_path"
    sed -i "s/DOCKER_MYSQL_PORT=3307/DOCKER_MYSQL_PORT=$MYSQL_PORT/g" "$script_path"
    sed -i "s/DOCKER_POSTGRES_PORT=5433/DOCKER_POSTGRES_PORT=$POSTGRES_PORT/g" "$script_path"
    sed -i "s/DOCKER_REDIS_PORT=6380/DOCKER_REDIS_PORT=$REDIS_PORT/g" "$script_path"

    # Update additional port references
    sed -i "s/port=\${2:-8000}/port=\${2:-$APP_PORT}/g" "$script_path"
    sed -i "s/port=\${3:-\"8000\"}/port=\${3:-\"$APP_PORT\"}/g" "$script_path"
    sed -i "s/port=\${5:-\"8000\"}/port=\${5:-\"$APP_PORT\"}/g" "$script_path"
    sed -i "s/:8000/:$APP_PORT/g" "$script_path"
    sed -i "s/127.0.0.1:3307/127.0.0.1:$MYSQL_PORT/g" "$script_path"
    sed -i "s/127.0.0.1, 6380/127.0.0.1, $REDIS_PORT/g" "$script_path"
    sed -i "s/values\[APP_PORT\]=\"8000\"/values[APP_PORT]=\"$APP_PORT\"/g" "$script_path"
    sed -i "s/values\[MYSQL_PORT\]=\"3307\"/values[MYSQL_PORT]=\"$MYSQL_PORT\"/g" "$script_path"
    sed -i "s/values\[POSTGRES_PORT\]=\"5433\"/values[POSTGRES_PORT]=\"$POSTGRES_PORT\"/g" "$script_path"
    sed -i "s/values\[REDIS_PORT\]=\"6380\"/values[REDIS_PORT]=\"$REDIS_PORT\"/g" "$script_path"
    
    # Update directory paths
    sed -i "s|/var/www/simple-php|/var/www/$APP_NAME|g" "$script_path"
    sed -i "s|APACHE_APP_DIR=\"/var/www/simple-php\"|APACHE_APP_DIR=\"/var/www/$APP_NAME\"|g" "$script_path"
    sed -i "s|NGINX_APP_DIR=\"/var/www/simple-php\"|NGINX_APP_DIR=\"/var/www/$APP_NAME\"|g" "$script_path"
    
    # Update VHOST_NAME
    sed -i "s/VHOST_NAME=\"simple-php\"/VHOST_NAME=\"$APP_NAME\"/g" "$script_path"
    
    # Update container names
    sed -i "s/simple_php_mysql/${APP_NAME}_mysql/g" "$script_path"
    sed -i "s/simple_php_postgres/${APP_NAME}_postgres/g" "$script_path"
    sed -i "s/simple_php_redis/${APP_NAME}_redis/g" "$script_path"
    
    # Update network names
    sed -i "s/simple_php_network/${APP_NAME}_network/g" "$script_path"
    
    log_success "Updated $script_name for $app"
    return 0
}

# Update all scripts for an application
update_application_scripts() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    if [[ ! -d "$app_dir" ]]; then
        log_error "Application directory not found: $app_dir"
        return 1
    fi
    
    log_info "Updating scripts for application: $app"
    
    local updated_count=0
    local total_scripts=${#SCRIPTS_TO_UPDATE[@]}
    
    for script in "${SCRIPTS_TO_UPDATE[@]}"; do
        if update_script "$app" "$script"; then
            ((updated_count++))
        fi
    done
    
    log_success "$app: Updated $updated_count/$total_scripts scripts"
    return 0
}

# Verify script updates
verify_script_updates() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    log_info "Verifying script updates for $app"
    
    # Parse configuration for this app
    parse_app_config "$app"
    
    local errors=0
    
    # Check for remaining simple-php references
    if grep -r "simple-php" "$app_dir/scripts/" 2>/dev/null | grep -v ".backup" | grep -q .; then
        log_error "$app: Still contains 'simple-php' references"
        ((errors++))
    fi
    
    # Check for wrong port references
    if grep -r "8000\|3307\|5433\|6380" "$app_dir/scripts/" 2>/dev/null | grep -v ".backup" | grep -q .; then
        if [[ "$app" != "simple-php" ]]; then
            log_error "$app: Still contains simple-php port references"
            ((errors++))
        fi
    fi
    
    if [[ $errors -eq 0 ]]; then
        log_success "$app: Script verification passed"
        return 0
    else
        log_error "$app: Script verification failed ($errors errors)"
        return 1
    fi
}

# Main execution
main() {
    log_info "APM PHP Examples - Fix Application-Specific Details"
    echo "===================================================="
    echo ""
    
    # Applications to fix (excluding simple-php which is the source)
    local applications=("slim-framework" "symfony-app" "codeigniter-app" "laravel-app")
    local success_count=0
    local total_errors=0
    
    for app in "${applications[@]}"; do
        echo ""
        if update_application_scripts "$app"; then
            if verify_script_updates "$app"; then
                ((success_count++))
            else
                ((total_errors++))
            fi
        else
            ((total_errors++))
        fi
    done
    
    echo ""
    log_info "Application-Specific Updates Summary"
    echo "===================================="
    echo "Applications processed: ${#applications[@]}"
    echo "Successfully updated: $success_count"
    echo "Failed: $total_errors"
    
    if [[ $total_errors -eq 0 ]]; then
        log_success "🎉 ALL APPLICATION-SPECIFIC DETAILS FIXED!"
        echo ""
        echo -e "${GREEN}✅ All scripts now use correct application names${NC}"
        echo -e "${GREEN}✅ All scripts now use correct port numbers${NC}"
        echo -e "${GREEN}✅ All scripts now use correct directory paths${NC}"
        echo -e "${GREEN}✅ All scripts now use correct database names${NC}"
        echo ""
        echo -e "${BLUE}🚀 Applications are now properly isolated and configured!${NC}"
        echo ""
        echo "Next steps:"
        echo "1. Test each application: cd <app> && make compile"
        echo "2. Verify configuration: check config/app.env files"
        echo "3. Test deployment: make setup && make start"
    else
        log_warning "Some applications require attention"
        echo "Check the output above for details"
        echo ""
        echo "To see what still needs fixing:"
        echo "grep -r 'simple-php' */scripts/ | grep -v backup"
        echo "grep -r '8000\\|3307\\|5433\\|6380' */scripts/ | grep -v backup"
    fi
    
    return $total_errors
}

# Execute main function
main "$@"
