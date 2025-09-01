#!/bin/bash

# APM PHP Examples - Standardization Validation Script
# Validates that all applications follow the standardized configuration and Makefile structure

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Application list
APPLICATIONS=("simple-php" "symfony-app" "slim-framework" "codeigniter-app" "laravel-app")

# Expected port allocation
declare -A EXPECTED_PORTS
EXPECTED_PORTS["simple-php"]="8000,3307,5433,6380"
EXPECTED_PORTS["symfony-app"]="8002,3308,5434,6381"
EXPECTED_PORTS["slim-framework"]="8001,3309,5435,6382"
EXPECTED_PORTS["codeigniter-app"]="8003,3310,5436,6383"
EXPECTED_PORTS["laravel-app"]="8004,3311,5437,6384"

# Required Makefile targets
REQUIRED_TARGETS=("help" "setup" "compile" "start" "status" "disable" "enable" "stop" "down" "php-status" "network-status" "test" "install")

echo -e "${BLUE}🔍 APM PHP Examples - Standardization Validation${NC}"
echo "=================================================="

# Function to validate application configuration
validate_app_config() {
    local app=$1
    local config_file="$app/config/app.env"
    local example_file="$app/config/app.env.example"
    
    echo -e "\n${BLUE}📋 Validating $app configuration...${NC}"
    
    # Check if example file exists
    if [[ ! -f "$example_file" ]]; then
        echo -e "${RED}❌ Missing config template: $example_file${NC}"
        return 1
    else
        echo -e "${GREEN}✅ Configuration template exists${NC}"
    fi
    
    # Check if runtime config exists
    if [[ ! -f "$config_file" ]]; then
        echo -e "${YELLOW}⚠️  Runtime config missing: $config_file (run 'make compile')${NC}"
        return 0
    fi
    
    # Extract ports from config
    local app_port=$(grep "^APP_PORT=" "$config_file" | cut -d= -f2)
    local mysql_port=$(grep "^MYSQL_PORT=" "$config_file" | cut -d= -f2)
    local postgres_port=$(grep "^POSTGRES_PORT=" "$config_file" | cut -d= -f2)
    local redis_port=$(grep "^REDIS_PORT=" "$config_file" | cut -d= -f2)
    
    # Expected ports for this app
    IFS=',' read -r expected_app expected_mysql expected_postgres expected_redis <<< "${EXPECTED_PORTS[$app]}"
    
    # Validate ports
    local errors=0
    
    if [[ "$app_port" != "$expected_app" ]]; then
        echo -e "${RED}❌ APP_PORT mismatch: expected $expected_app, got $app_port${NC}"
        ((errors++))
    fi
    
    if [[ "$mysql_port" != "$expected_mysql" ]]; then
        echo -e "${RED}❌ MYSQL_PORT mismatch: expected $expected_mysql, got $mysql_port${NC}"
        ((errors++))
    fi
    
    if [[ "$postgres_port" != "$expected_postgres" ]]; then
        echo -e "${RED}❌ POSTGRES_PORT mismatch: expected $expected_postgres, got $postgres_port${NC}"
        ((errors++))
    fi
    
    if [[ "$redis_port" != "$expected_redis" ]]; then
        echo -e "${RED}❌ REDIS_PORT mismatch: expected $expected_redis, got $redis_port${NC}"
        ((errors++))
    fi
    
    if [[ $errors -eq 0 ]]; then
        echo -e "${GREEN}✅ $app configuration is valid${NC}"
        echo "   APP: $app_port, MySQL: $mysql_port, PostgreSQL: $postgres_port, Redis: $redis_port"
    else
        echo -e "${RED}❌ $app has $errors configuration error(s)${NC}"
        return 1
    fi
}

# Function to validate Makefile standardization
validate_makefile() {
    local app=$1
    local makefile="$app/Makefile"
    
    echo -e "\n${BLUE}📋 Validating $app Makefile...${NC}"
    
    if [[ ! -f "$makefile" ]]; then
        echo -e "${RED}❌ Makefile missing: $makefile${NC}"
        return 1
    fi
    
    local errors=0
    local missing_targets=()
    
    # Check for required targets
    for target in "${REQUIRED_TARGETS[@]}"; do
        if grep -q "^$target:" "$makefile"; then
            echo -e "${GREEN}✅ $target${NC}"
        else
            echo -e "${RED}❌ Missing target: $target${NC}"
            missing_targets+=("$target")
            ((errors++))
        fi
    done
    
    # Check for consistent help text format
    if grep -q "make setup.*Check system requirements" "$makefile"; then
        echo -e "${GREEN}✅ Consistent help text format${NC}"
    else
        echo -e "${YELLOW}⚠️  Help text format may be inconsistent${NC}"
    fi
    
    if [[ $errors -eq 0 ]]; then
        echo -e "${GREEN}✅ $app Makefile is standardized${NC}"
    else
        echo -e "${RED}❌ $app Makefile missing ${#missing_targets[@]} target(s): ${missing_targets[*]}${NC}"
        return 1
    fi
}

# Function to check for port conflicts
check_port_conflicts() {
    echo -e "\n${BLUE}🔍 Checking for port conflicts...${NC}"
    
    local all_ports=()
    local conflicts=0
    
    for app in "${APPLICATIONS[@]}"; do
        local config_file="$app/config/app.env"
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
    for i in "${!all_ports[@]}"; do
        for j in "${!all_ports[@]}"; do
            if [[ $i -ne $j ]]; then
                local port1=$(echo "${all_ports[$i]}" | cut -d: -f3)
                local port2=$(echo "${all_ports[$j]}" | cut -d: -f3)
                local app1=$(echo "${all_ports[$i]}" | cut -d: -f1)
                local app2=$(echo "${all_ports[$j]}" | cut -d: -f1)
                local service1=$(echo "${all_ports[$i]}" | cut -d: -f2)
                local service2=$(echo "${all_ports[$j]}" | cut -d: -f2)
                
                if [[ "$port1" == "$port2" && "$app1" != "$app2" ]]; then
                    echo -e "${RED}❌ Port conflict: $app1:$service1 and $app2:$service2 both use port $port1${NC}"
                    ((conflicts++))
                fi
            fi
        done
    done
    
    if [[ $conflicts -eq 0 ]]; then
        echo -e "${GREEN}✅ No port conflicts detected${NC}"
    else
        echo -e "${RED}❌ Found $conflicts port conflict(s)${NC}"
        return 1
    fi
}

# Function to validate file structure
validate_file_structure() {
    echo -e "\n${BLUE}🔍 Validating file structure...${NC}"
    
    local errors=0
    
    for app in "${APPLICATIONS[@]}"; do
        echo -e "\n${YELLOW}Checking $app structure...${NC}"
        
        # Required files
        local required_files=(
            "$app/Makefile"
            "$app/config/app.env.example"
            "$app/docker-compose.yml"
            "$app/scripts/check-system.sh"
            "$app/scripts/compile-app.sh"
            "$app/scripts/start-app.sh"
            "$app/scripts/status.sh"
        )
        
        for file in "${required_files[@]}"; do
            if [[ -f "$file" ]]; then
                echo -e "${GREEN}✅ $file${NC}"
            else
                echo -e "${RED}❌ Missing: $file${NC}"
                ((errors++))
            fi
        done
    done
    
    if [[ $errors -eq 0 ]]; then
        echo -e "\n${GREEN}✅ All file structures are correct${NC}"
    else
        echo -e "\n${RED}❌ Found $errors missing file(s)${NC}"
        return 1
    fi
}

# Main validation function
main() {
    local total_errors=0
    
    # Validate file structure
    if ! validate_file_structure; then
        ((total_errors++))
    fi
    
    # Validate each application
    for app in "${APPLICATIONS[@]}"; do
        if ! validate_app_config "$app"; then
            ((total_errors++))
        fi
        
        if ! validate_makefile "$app"; then
            ((total_errors++))
        fi
    done
    
    # Check for port conflicts
    if ! check_port_conflicts; then
        ((total_errors++))
    fi
    
    # Final summary
    echo -e "\n${BLUE}📊 Standardization Validation Summary${NC}"
    echo "====================================="
    
    if [[ $total_errors -eq 0 ]]; then
        echo -e "${GREEN}✅ All applications are properly standardized!${NC}"
        echo -e "${GREEN}✅ Configuration structure is consistent${NC}"
        echo -e "${GREEN}✅ Makefiles are standardized${NC}"
        echo -e "${GREEN}✅ Port allocation is conflict-free${NC}"
        echo -e "${GREEN}✅ File structure is complete${NC}"
        echo ""
        echo -e "${BLUE}🎉 Ready for production use!${NC}"
        return 0
    else
        echo -e "${RED}❌ Found standardization issues that need to be resolved${NC}"
        echo -e "${YELLOW}💡 Recommendations:${NC}"
        echo -e "   1. Run 'make compile' in each application to regenerate configurations"
        echo -e "   2. Check missing Makefile targets and add them"
        echo -e "   3. Ensure all required files exist"
        echo -e "   4. Verify port allocations match the standard"
        return 1
    fi
}

# Execute main function
main "$@"
