#!/bin/bash

# APM PHP Examples - Complete Standardization Script
# Completes the standardization for all applications by copying enhanced scripts and updating configurations

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

# Applications to standardize (excluding simple-php which is already complete)
readonly APPLICATIONS=("slim-framework" "symfony-app" "codeigniter-app" "laravel-app")

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

# Copy enhanced scripts from simple-php to target application
copy_enhanced_scripts() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    local source_dir="$REPO_ROOT/simple-php/scripts"
    
    log_info "Copying enhanced scripts to $app"
    
    # Ensure scripts directory exists
    mkdir -p "$app_dir/scripts"
    
    # Copy enhanced scripts
    local scripts_to_copy=(
        "check-system-hardened.sh"
        "common-functions.sh"
        "compile-app-enhanced.sh"
        "migrate-config.sh"
        "network-manager.sh"
        "php-version-manager.sh"
        "webserver-manager.sh"
    )
    
    for script in "${scripts_to_copy[@]}"; do
        if [[ -f "$source_dir/$script" ]]; then
            cp "$source_dir/$script" "$app_dir/scripts/"
            chmod +x "$app_dir/scripts/$script"
            log_success "Copied $script to $app"
        else
            log_warning "Script $script not found in simple-php"
        fi
    done
}

# Copy enhanced configuration template
copy_enhanced_config() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    local source_config="$REPO_ROOT/simple-php/config"
    
    log_info "Copying enhanced configuration to $app"
    
    # Ensure config directory exists
    mkdir -p "$app_dir/config"
    
    # Copy configuration files if they exist in simple-php
    if [[ -f "$source_config/app.env.example" ]]; then
        # Update port numbers for the specific app
        case "$app" in
            "slim-framework")
                sed 's/APP_PORT=8000/APP_PORT=8001/g; s/MYSQL_PORT=3307/MYSQL_PORT=3309/g; s/POSTGRES_PORT=5433/POSTGRES_PORT=5435/g; s/REDIS_PORT=6380/REDIS_PORT=6382/g; s/simple_php_db/slim_framework_db/g' "$source_config/app.env.example" > "$app_dir/config/app.env.example"
                ;;
            "symfony-app")
                sed 's/APP_PORT=8000/APP_PORT=8002/g; s/MYSQL_PORT=3307/MYSQL_PORT=3308/g; s/POSTGRES_PORT=5433/POSTGRES_PORT=5434/g; s/REDIS_PORT=6380/REDIS_PORT=6381/g; s/simple_php_db/symfony_app_db/g' "$source_config/app.env.example" > "$app_dir/config/app.env.example"
                ;;
            "codeigniter-app")
                sed 's/APP_PORT=8000/APP_PORT=8003/g; s/MYSQL_PORT=3307/MYSQL_PORT=3310/g; s/POSTGRES_PORT=5433/POSTGRES_PORT=5436/g; s/REDIS_PORT=6380/REDIS_PORT=6383/g; s/simple_php_db/codeigniter_app_db/g' "$source_config/app.env.example" > "$app_dir/config/app.env.example"
                ;;
            "laravel-app")
                sed 's/APP_PORT=8000/APP_PORT=8004/g; s/MYSQL_PORT=3307/MYSQL_PORT=3311/g; s/POSTGRES_PORT=5433/POSTGRES_PORT=5437/g; s/REDIS_PORT=6380/REDIS_PORT=6384/g; s/simple_php_db/laravel_app_db/g' "$source_config/app.env.example" > "$app_dir/config/app.env.example"
                ;;
        esac
        log_success "Updated configuration template for $app"
    fi
    
    # Copy other config files if they exist
    if [[ -d "$source_config/profiles" ]]; then
        cp -r "$source_config/profiles" "$app_dir/config/" 2>/dev/null || true
        log_success "Copied configuration profiles to $app"
    fi
}

# Update Docker Compose files to use correct ports
update_docker_compose() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    local compose_file="$app_dir/docker-compose.yml"
    
    if [[ ! -f "$compose_file" ]]; then
        log_warning "$app: docker-compose.yml not found"
        return 1
    fi
    
    log_info "Updating Docker Compose ports for $app"
    
    # Update ports based on application
    case "$app" in
        "slim-framework")
            sed -i 's/"3307:3306"/"3309:3306"/g; s/"5433:5432"/"5435:5432"/g; s/"6380:6379"/"6382:6379"/g' "$compose_file"
            ;;
        "symfony-app")
            sed -i 's/"3307:3306"/"3308:3306"/g; s/"5433:5432"/"5434:5432"/g; s/"6380:6379"/"6381:6379"/g' "$compose_file"
            ;;
        "codeigniter-app")
            sed -i 's/"3307:3306"/"3310:3306"/g; s/"5433:5432"/"5436:5432"/g; s/"6380:6379"/"6383:6379"/g' "$compose_file"
            ;;
        "laravel-app")
            sed -i 's/"3307:3306"/"3311:3306"/g; s/"5433:5432"/"5437:5432"/g; s/"6380:6379"/"6384:6379"/g' "$compose_file"
            ;;
    esac
    
    log_success "Updated Docker Compose ports for $app"
}

# Create logs directory
create_logs_directory() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    mkdir -p "$app_dir/logs"
    touch "$app_dir/logs/.gitkeep"
    log_success "Created logs directory for $app"
}

# Standardize single application
standardize_application() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    if [[ ! -d "$app_dir" ]]; then
        log_error "Application directory not found: $app_dir"
        return 1
    fi
    
    log_info "Completing standardization for: $app"
    
    # Copy enhanced scripts and configurations
    copy_enhanced_scripts "$app"
    copy_enhanced_config "$app"
    update_docker_compose "$app"
    create_logs_directory "$app"
    
    log_success "Standardization completed for $app"
    return 0
}

# Test application Makefile
test_application() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    cd "$app_dir"
    
    if make help >/dev/null 2>&1; then
        log_success "$app: Makefile test passed"
        return 0
    else
        log_error "$app: Makefile test failed"
        return 1
    fi
}

# Main execution
main() {
    log_info "APM PHP Examples - Complete Standardization"
    echo "============================================="
    echo ""
    
    local success_count=0
    local total_errors=0
    
    # Standardize all applications
    for app in "${APPLICATIONS[@]}"; do
        echo ""
        if standardize_application "$app"; then
            if test_application "$app"; then
                ((success_count++))
            else
                ((total_errors++))
            fi
        else
            ((total_errors++))
        fi
    done
    
    echo ""
    log_info "Standardization Summary"
    echo "======================="
    echo "Applications processed: ${#APPLICATIONS[@]}"
    echo "Successfully standardized: $success_count"
    echo "Failed: $total_errors"
    
    if [[ $total_errors -eq 0 ]]; then
        log_success "🎉 ALL APPLICATIONS SUCCESSFULLY STANDARDIZED!"
        echo ""
        echo -e "${GREEN}✅ Enhanced scripts copied to all applications${NC}"
        echo -e "${GREEN}✅ Configuration templates updated${NC}"
        echo -e "${GREEN}✅ Docker Compose ports corrected${NC}"
        echo -e "${GREEN}✅ Makefile commands tested${NC}"
        echo ""
        echo -e "${BLUE}🚀 All applications now have complete standardization!${NC}"
        echo ""
        echo "Next steps:"
        echo "1. Test each application: cd <app> && make help"
        echo "2. Run setup: make setup"
        echo "3. Configure: make compile"
        echo "4. Start: make start"
    else
        log_warning "Some applications require attention"
        echo "Check the output above for details"
    fi
    
    return $total_errors
}

# Execute main function
main "$@"
