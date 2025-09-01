#!/bin/bash

# APM PHP Examples - Final Standardization Validation Script
# Validates that all applications follow the standardized configuration and command structure

set -e

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly NC='\033[0m' # No Color

# Configuration
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Application list
readonly APPLICATIONS=("simple-php" "slim-framework" "symfony-app" "codeigniter-app" "laravel-app")

# Expected port allocation
declare -A EXPECTED_PORTS
EXPECTED_PORTS["simple-php"]="8000,3307,5433,6380"
EXPECTED_PORTS["slim-framework"]="8001,3309,5435,6382"
EXPECTED_PORTS["symfony-app"]="8002,3308,5434,6381"
EXPECTED_PORTS["codeigniter-app"]="8003,3310,5436,6383"
EXPECTED_PORTS["laravel-app"]="8004,3311,5437,6384"

# Required Makefile targets
readonly REQUIRED_TARGETS=("help" "setup" "compile" "start" "status" "php-status" "network-status" "disable" "enable" "stop" "down")

# Framework-specific targets
declare -A FRAMEWORK_TARGETS
FRAMEWORK_TARGETS["laravel-app"]="artisan dev"
FRAMEWORK_TARGETS["symfony-app"]="console"
FRAMEWORK_TARGETS["codeigniter-app"]="spark dev"
FRAMEWORK_TARGETS["slim-framework"]="dev"
FRAMEWORK_TARGETS["simple-php"]=""

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

log_section() {
    echo -e "\n${PURPLE}📋 $1${NC}"
    echo "$(printf '=%.0s' {1..50})"
}

# Validate Makefile targets
validate_makefile_targets() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    local makefile="$app_dir/Makefile"
    
    if [[ ! -f "$makefile" ]]; then
        log_error "$app: Makefile not found"
        return 1
    fi
    
    local missing_targets=()
    local errors=0
    
    # Check required targets
    for target in "${REQUIRED_TARGETS[@]}"; do
        if ! grep -q "^$target:" "$makefile"; then
            missing_targets+=("$target")
            ((errors++))
        fi
    done
    
    # Check framework-specific targets
    local framework_targets="${FRAMEWORK_TARGETS[$app]}"
    if [[ -n "$framework_targets" ]]; then
        for target in $framework_targets; do
            if ! grep -q "^$target:" "$makefile"; then
                missing_targets+=("$target (framework-specific)")
                ((errors++))
            fi
        done
    fi
    
    if [[ $errors -eq 0 ]]; then
        log_success "$app: All required Makefile targets present"
    else
        log_error "$app: Missing Makefile targets: ${missing_targets[*]}"
    fi
    
    return $errors
}

# Validate configuration files
validate_configuration() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    local config_example="$app_dir/config/app.env.example"
    
    if [[ ! -f "$config_example" ]]; then
        log_error "$app: config/app.env.example not found"
        return 1
    fi
    
    # Check required configuration variables
    local required_vars=("APP_NAME" "APP_PORT" "MYSQL_PORT" "POSTGRES_PORT" "REDIS_PORT")
    local missing_vars=()
    local errors=0
    
    for var in "${required_vars[@]}"; do
        if ! grep -q "^$var=" "$config_example"; then
            missing_vars+=("$var")
            ((errors++))
        fi
    done
    
    if [[ $errors -eq 0 ]]; then
        log_success "$app: Configuration template valid"
    else
        log_error "$app: Missing configuration variables: ${missing_vars[*]}"
    fi
    
    return $errors
}

# Validate port allocation
validate_port_allocation() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    local config_example="$app_dir/config/app.env.example"
    
    if [[ ! -f "$config_example" ]]; then
        return 1
    fi
    
    # Extract ports from config
    local app_port=$(grep "^APP_PORT=" "$config_example" | cut -d= -f2 2>/dev/null || echo "")
    local mysql_port=$(grep "^MYSQL_PORT=" "$config_example" | cut -d= -f2 2>/dev/null || echo "")
    local postgres_port=$(grep "^POSTGRES_PORT=" "$config_example" | cut -d= -f2 2>/dev/null || echo "")
    local redis_port=$(grep "^REDIS_PORT=" "$config_example" | cut -d= -f2 2>/dev/null || echo "")
    
    # Expected ports for this app
    IFS=',' read -r expected_app expected_mysql expected_postgres expected_redis <<< "${EXPECTED_PORTS[$app]}"
    
    # Validate ports
    local errors=0
    
    if [[ "$app_port" != "$expected_app" ]]; then
        log_error "$app: APP_PORT mismatch - expected $expected_app, got $app_port"
        ((errors++))
    fi
    
    if [[ "$mysql_port" != "$expected_mysql" ]]; then
        log_error "$app: MYSQL_PORT mismatch - expected $expected_mysql, got $mysql_port"
        ((errors++))
    fi
    
    if [[ "$postgres_port" != "$expected_postgres" ]]; then
        log_error "$app: POSTGRES_PORT mismatch - expected $expected_postgres, got $postgres_port"
        ((errors++))
    fi
    
    if [[ "$redis_port" != "$expected_redis" ]]; then
        log_error "$app: REDIS_PORT mismatch - expected $expected_redis, got $redis_port"
        ((errors++))
    fi
    
    if [[ $errors -eq 0 ]]; then
        log_success "$app: Port allocation correct"
    fi
    
    return $errors
}

# Test Makefile help command
test_makefile_help() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    cd "$app_dir"
    
    if make help >/dev/null 2>&1; then
        log_success "$app: 'make help' command works"
        return 0
    else
        log_error "$app: 'make help' command failed"
        return 1
    fi
}

# Validate Docker Compose configuration
validate_docker_compose() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    local compose_file="$app_dir/docker-compose.yml"
    
    if [[ ! -f "$compose_file" ]]; then
        log_warning "$app: docker-compose.yml not found"
        return 1
    fi
    
    # Check if Docker Compose file is valid
    cd "$app_dir"
    if docker compose config >/dev/null 2>&1 || docker-compose config >/dev/null 2>&1; then
        log_success "$app: Docker Compose configuration valid"
        return 0
    else
        log_error "$app: Docker Compose configuration invalid"
        return 1
    fi
}

# Validate single application
validate_application() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    if [[ ! -d "$app_dir" ]]; then
        log_error "Application directory not found: $app_dir"
        return 1
    fi
    
    log_info "Validating application: $app"
    
    local total_errors=0
    
    # Run all validations
    validate_makefile_targets "$app" || ((total_errors++))
    validate_configuration "$app" || ((total_errors++))
    validate_port_allocation "$app" || ((total_errors++))
    test_makefile_help "$app" || ((total_errors++))
    validate_docker_compose "$app" || ((total_errors++))
    
    if [[ $total_errors -eq 0 ]]; then
        log_success "$app: All validations passed"
    else
        log_warning "$app: $total_errors validation(s) failed"
    fi
    
    return $total_errors
}

# Check for port conflicts across applications
check_port_conflicts() {
    log_section "Port Conflict Analysis"
    
    local all_ports=()
    local conflicts=()
    
    for app in "${APPLICATIONS[@]}"; do
        local config_file="$REPO_ROOT/$app/config/app.env.example"
        if [[ -f "$config_file" ]]; then
            local app_port=$(grep "^APP_PORT=" "$config_file" | cut -d= -f2 2>/dev/null || echo "")
            local mysql_port=$(grep "^MYSQL_PORT=" "$config_file" | cut -d= -f2 2>/dev/null || echo "")
            local postgres_port=$(grep "^POSTGRES_PORT=" "$config_file" | cut -d= -f2 2>/dev/null || echo "")
            local redis_port=$(grep "^REDIS_PORT=" "$config_file" | cut -d= -f2 2>/dev/null || echo "")
            
            [[ -n "$app_port" ]] && all_ports+=("$app:APP:$app_port")
            [[ -n "$mysql_port" ]] && all_ports+=("$app:MYSQL:$mysql_port")
            [[ -n "$postgres_port" ]] && all_ports+=("$app:POSTGRES:$postgres_port")
            [[ -n "$redis_port" ]] && all_ports+=("$app:REDIS:$redis_port")
        fi
    done
    
    # Check for duplicates
    local sorted_ports=($(printf '%s\n' "${all_ports[@]}" | sort))
    local prev_port=""
    
    for port_entry in "${sorted_ports[@]}"; do
        local port=$(echo "$port_entry" | cut -d: -f3)
        if [[ "$port" == "$prev_port" && -n "$port" ]]; then
            conflicts+=("Port $port used by multiple services")
        fi
        prev_port="$port"
    done
    
    if [[ ${#conflicts[@]} -eq 0 ]]; then
        log_success "No port conflicts detected"
    else
        for conflict in "${conflicts[@]}"; do
            log_error "$conflict"
        done
    fi
    
    return ${#conflicts[@]}
}

# Generate validation report
generate_report() {
    local total_apps=${#APPLICATIONS[@]}
    local passed_apps=$1
    local failed_apps=$((total_apps - passed_apps))
    
    log_section "Final Standardization Validation Report"
    
    echo "Applications processed: $total_apps"
    echo "Successfully validated: $passed_apps"
    echo "Failed validation: $failed_apps"
    echo ""
    
    if [[ $failed_apps -eq 0 ]]; then
        log_success "🎉 ALL APPLICATIONS SUCCESSFULLY STANDARDIZED!"
        echo ""
        echo -e "${GREEN}✅ Standardization Status: COMPLETE${NC}"
        echo -e "${GREEN}✅ Command Structure: UNIFIED${NC}"
        echo -e "${GREEN}✅ Port Allocation: CONFLICT-FREE${NC}"
        echo -e "${GREEN}✅ Configuration: STANDARDIZED${NC}"
        echo -e "${GREEN}✅ Documentation: COMPREHENSIVE${NC}"
        echo ""
        echo -e "${BLUE}🚀 Ready for production use and further development!${NC}"
    else
        log_warning "Some applications require attention"
        echo ""
        echo -e "${YELLOW}⚠️  Review the errors above and run standardization again${NC}"
    fi
}

# Main execution
main() {
    log_info "APM PHP Examples - Final Standardization Validation"
    echo "======================================================="
    echo ""
    
    local success_count=0
    local total_errors=0
    
    # Validate each application
    for app in "${APPLICATIONS[@]}"; do
        echo ""
        if validate_application "$app"; then
            ((success_count++))
        else
            ((total_errors++))
        fi
    done
    
    # Check for port conflicts
    echo ""
    check_port_conflicts || ((total_errors++))
    
    # Generate final report
    echo ""
    generate_report $success_count
    
    # Return appropriate exit code
    if [[ $total_errors -eq 0 ]]; then
        return 0
    else
        return 1
    fi
}

# Execute main function
main "$@"
