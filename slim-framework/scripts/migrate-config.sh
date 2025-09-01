#!/bin/bash

# APM PHP Examples - Configuration Migration Utility
# Migrates from legacy config files to centralized runtime.env

set -e

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common-functions.sh"

# Configuration files
RUNTIME_CONFIG="config/runtime.env"
LEGACY_APP_ENV="config/app.env"
LEGACY_DOT_ENV=".env"
BACKUP_DIR="config/backup-$(date +%Y%m%d-%H%M%S)"

# Migration mode
DRY_RUN=${DRY_RUN:-0}

migrate_configuration() {
    log_info "Starting configuration migration"
    
    # Create backup directory
    mkdir -p "$BACKUP_DIR"
    
    # Check for existing configurations
    local has_legacy=0
    local legacy_files=()
    
    if [[ -f "$LEGACY_APP_ENV" ]]; then
        legacy_files+=("$LEGACY_APP_ENV")
        has_legacy=1
    fi
    
    if [[ -f "$LEGACY_DOT_ENV" ]]; then
        legacy_files+=("$LEGACY_DOT_ENV")
        has_legacy=1
    fi
    
    if [[ $has_legacy -eq 0 ]]; then
        log_info "No legacy configuration files found"
        if [[ ! -f "$RUNTIME_CONFIG" ]]; then
            log_info "Creating default runtime configuration"
            create_default_config
        fi
        return 0
    fi
    
    log_info "Found legacy configuration files: ${legacy_files[*]}"
    
    # Backup existing files
    for file in "${legacy_files[@]}"; do
        if [[ -f "$file" ]]; then
            log_verbose "Backing up $file to $BACKUP_DIR/"
            cp "$file" "$BACKUP_DIR/"
        fi
    done
    
    # Read values from legacy files
    declare -A config_values
    read_legacy_values config_values
    
    # Show migration preview
    show_migration_preview config_values
    
    if [[ $DRY_RUN -eq 1 ]]; then
        log_info "DRY RUN: Would create $RUNTIME_CONFIG with migrated values"
        return 0
    fi
    
    # Confirm migration
    if ! confirm_migration; then
        log_info "Migration cancelled by user"
        return 1
    fi
    
    # Create new runtime config
    create_runtime_config config_values
    
    # Validate new configuration
    validate_runtime_config
    
    log_success "Configuration migration completed successfully"
    log_info "Legacy files backed up to: $BACKUP_DIR"
    log_info "New configuration: $RUNTIME_CONFIG"
}

read_legacy_values() {
    local -n values=$1
    
    # Default values
    values[APP_NAME]="Simple PHP"
    values[APP_PORT]="8001"
    values[NETWORK_INTERFACE]="127.0.0.1"
    values[PHP_VERSION]="8.4"
    values[DEPLOYMENT_TYPE]="php-cli"
    values[MYSQL_PORT]="3309"
    values[POSTGRES_PORT]="5435"
    values[REDIS_PORT]="6382"
    values[MYSQL_DATABASE]="slim_framework_db"
    values[POSTGRES_DATABASE]="slim_framework_db"
    
    # Read from legacy files
    for file in "$LEGACY_APP_ENV" "$LEGACY_DOT_ENV"; do
        if [[ -f "$file" ]]; then
            log_verbose "Reading values from $file"
            while IFS='=' read -r key value; do
                # Skip comments and empty lines
                [[ $key =~ ^[[:space:]]*# ]] && continue
                [[ -z $key ]] && continue
                
                # Clean up key and value
                key=$(echo "$key" | tr -d ' ')
                value=$(echo "$value" | sed 's/^["'\'']//' | sed 's/["'\'']$//')
                
                if [[ -n $key && -n $value ]]; then
                    values[$key]="$value"
                    log_verbose "  $key=$value"
                fi
            done < "$file"
        fi
    done
}

show_migration_preview() {
    local -n values=$1
    
    echo -e "\n${BLUE}📋 Migration Preview${NC}"
    echo "===================="
    echo -e "${YELLOW}The following configuration will be migrated:${NC}"
    
    printf "%-20s %-15s %-15s\n" "Setting" "Current" "Migrated"
    printf "%-20s %-15s %-15s\n" "-------" "-------" "--------"
    
    # Key settings to show
    local key_settings=(
        "APP_NAME"
        "APP_PORT"
        "PHP_VERSION"
        "DEPLOYMENT_TYPE"
        "MYSQL_PORT"
        "POSTGRES_PORT"
        "REDIS_PORT"
        "MYSQL_DATABASE"
    )
    
    for setting in "${key_settings[@]}"; do
        local current="N/A"
        local migrated="${values[$setting]:-default}"
        
        # Try to get current value
        if [[ -f "$LEGACY_APP_ENV" ]] && grep -q "^$setting=" "$LEGACY_APP_ENV"; then
            current=$(grep "^$setting=" "$LEGACY_APP_ENV" | cut -d'=' -f2 | sed 's/^["'\'']//' | sed 's/["'\'']$//')
        elif [[ -f "$LEGACY_DOT_ENV" ]] && grep -q "^$setting=" "$LEGACY_DOT_ENV"; then
            current=$(grep "^$setting=" "$LEGACY_DOT_ENV" | cut -d'=' -f2 | sed 's/^["'\'']//' | sed 's/["'\'']$//')
        fi
        
        printf "%-20s %-15s %-15s\n" "$setting" "$current" "$migrated"
    done
    
    echo ""
    echo -e "${YELLOW}📁 Files to be backed up:${NC}"
    for file in "$LEGACY_APP_ENV" "$LEGACY_DOT_ENV"; do
        if [[ -f "$file" ]]; then
            echo "  - $file → $BACKUP_DIR/$(basename "$file")"
        fi
    done
}

confirm_migration() {
    if [[ -t 0 ]]; then  # Interactive terminal
        echo -e "\n${YELLOW}❓ Proceed with migration? (y/N):${NC}"
        read -r response
        case "$response" in
            [yY]|[yY][eE][sS]) return 0 ;;
            *) return 1 ;;
        esac
    else
        # Non-interactive, assume yes if AUTO_CONFIRM is set
        if [[ "${AUTO_CONFIRM:-0}" -eq 1 ]]; then
            log_info "Auto-confirming migration (non-interactive mode)"
            return 0
        else
            log_error "Migration requires confirmation in interactive mode"
            return 1
        fi
    fi
}

create_runtime_config() {
    local -n values=$1
    
    log_info "Creating runtime configuration: $RUNTIME_CONFIG"
    
    # Ensure config directory exists
    mkdir -p "$(dirname "$RUNTIME_CONFIG")"
    
    # Generate runtime.env with migrated values
    cat > "$RUNTIME_CONFIG" << EOF
# Simple PHP Application - Runtime Configuration
# This is the SINGLE SOURCE OF TRUTH for all application configuration
# Migrated from legacy configuration on $(date)

# =============================================================================
# APPLICATION SETTINGS
# =============================================================================
APP_NAME="${values[APP_NAME]}"
APP_PORT=${values[APP_PORT]}
NETWORK_INTERFACE="${values[NETWORK_INTERFACE]}"
NETWORK_DISPLAY="${values[NETWORK_DISPLAY]:-localhost}"
PUBLIC_IP="${values[PUBLIC_IP]:-auto-detected}"
PHP_VERSION="${values[PHP_VERSION]}"
DEPLOYMENT_TYPE="${values[DEPLOYMENT_TYPE]}"
DEPLOYMENT_DESC="${values[DEPLOYMENT_DESC]:-PHP Built-in Server}"

# =============================================================================
# ENVIRONMENT CONFIGURATION
# =============================================================================
APP_ENV=${values[APP_ENV]:-production}
APP_DEBUG=${values[APP_DEBUG]:-false}

# =============================================================================
# DATABASE CONFIGURATION (Application-Specific Ports)
# =============================================================================
# MySQL Configuration
MYSQL_HOST=${values[MYSQL_HOST]:-localhost}
MYSQL_PORT=${values[MYSQL_PORT]}
MYSQL_DATABASE=${values[MYSQL_DATABASE]}
MYSQL_USERNAME=${values[MYSQL_USERNAME]:-root}
MYSQL_PASSWORD=${values[MYSQL_PASSWORD]:-rootpassword}

# PostgreSQL Configuration
POSTGRES_HOST=${values[POSTGRES_HOST]:-localhost}
POSTGRES_PORT=${values[POSTGRES_PORT]}
POSTGRES_DATABASE=${values[POSTGRES_DATABASE]}
POSTGRES_USERNAME=${values[POSTGRES_USERNAME]:-postgres}
POSTGRES_PASSWORD=${values[POSTGRES_PASSWORD]:-postgrespassword}

# Redis Configuration
REDIS_HOST=${values[REDIS_HOST]:-localhost}
REDIS_PORT=${values[REDIS_PORT]}

# =============================================================================
# WEB SERVER CONFIGURATION
# =============================================================================
APACHE_APP_DIR="${values[APACHE_APP_DIR]:-/var/www/slim-framework}"
NGINX_APP_DIR="/var/www/slim-framework"
PHP_FPM_SOCKET="/run/php/php${values[PHP_VERSION]}-fpm.sock"

# =============================================================================
# DOCKER CONFIGURATION
# =============================================================================
DOCKER_COMPOSE_CMD="docker compose"
DOCKER_NETWORK="slim-framework_network"
DOCKER_MYSQL_PORT=${values[MYSQL_PORT]}
DOCKER_POSTGRES_PORT=${values[POSTGRES_PORT]}
DOCKER_REDIS_PORT=${values[REDIS_PORT]}

# =============================================================================
# SECURITY CONFIGURATION
# =============================================================================
SECURE_HEADERS=true
CSRF_PROTECTION=true
XSS_PROTECTION=true
ALLOW_PUBLIC=${values[ALLOW_PUBLIC]:-false}

# =============================================================================
# MONITORING AND LOGGING
# =============================================================================
LOG_LEVEL=${values[LOG_LEVEL]:-info}
LOG_CHANNEL=stack
MONITORING_ENABLED=true
LOG_FILE="logs/application.log"

# =============================================================================
# RESOURCE LIMITS
# =============================================================================
PHP_MEMORY_LIMIT="256M"
PHP_MAX_EXECUTION_TIME=30
COMPOSER_MEMORY_LIMIT="2G"

# =============================================================================
# TIMEOUT SETTINGS
# =============================================================================
DOCKER_HEALTH_TIMEOUT=120
CONTAINER_START_TIMEOUT=60
APPLICATION_START_TIMEOUT=30

# =============================================================================
# CONFIGURATION METADATA
# =============================================================================
CONFIG_VERSION="2.0"
CONFIG_GENERATED_BY="migrate_config"
CONFIG_LAST_UPDATED="$(date '+%Y-%m-%d %H:%M:%S')"
CONFIG_SOURCE="runtime.env"
CONFIG_MIGRATED_FROM="${legacy_files[*]}"
EOF

    log_success "Runtime configuration created successfully"
}

create_default_config() {
    log_info "Creating default runtime configuration"
    
    # Use the template from the repository
    if [[ -f "config/runtime.env" ]]; then
        log_verbose "Default configuration already exists"
        return 0
    fi
    
    # Create minimal default configuration
    declare -A default_values
    default_values[APP_NAME]="Simple PHP"
    default_values[APP_PORT]="8001"
    default_values[NETWORK_INTERFACE]="127.0.0.1"
    default_values[PHP_VERSION]="8.4"
    default_values[DEPLOYMENT_TYPE]="php-cli"
    default_values[MYSQL_PORT]="3309"
    default_values[POSTGRES_PORT]="5435"
    default_values[REDIS_PORT]="6382"
    default_values[MYSQL_DATABASE]="slim_framework_db"
    default_values[POSTGRES_DATABASE]="slim_framework_db"
    
    create_runtime_config default_values
}

validate_runtime_config() {
    log_info "Validating runtime configuration"
    
    if [[ ! -f "$RUNTIME_CONFIG" ]]; then
        fail_with_error "validate_config" "Runtime configuration file not found" $EXIT_GENERAL_ERROR
    fi
    
    # Check required variables
    local required_vars=(
        "APP_NAME"
        "APP_PORT"
        "PHP_VERSION"
        "MYSQL_PORT"
        "POSTGRES_PORT"
        "REDIS_PORT"
    )
    
    local missing_vars=()
    for var in "${required_vars[@]}"; do
        if ! grep -q "^$var=" "$RUNTIME_CONFIG"; then
            missing_vars+=("$var")
        fi
    done
    
    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        fail_with_error "validate_config" "Missing required variables: ${missing_vars[*]}" $EXIT_GENERAL_ERROR
    fi
    
    log_success "Runtime configuration validation passed"
}

# Main execution
main() {
    log_info "Configuration Migration Utility"
    
    if [[ $DRY_RUN -eq 1 ]]; then
        log_info "Running in DRY RUN mode - no changes will be made"
    fi
    
    migrate_configuration
    
    success_with_message "migrate_config" "Configuration migration completed" \
        "• New configuration: $RUNTIME_CONFIG\n• Backup location: $BACKUP_DIR\n• Run 'make compile' to update settings"
}

# Execute main function
main "$@"
