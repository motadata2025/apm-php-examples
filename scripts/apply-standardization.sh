#!/bin/bash

# APM PHP Examples - Final Standardization Implementation Script
# Applies standardized Makefiles and configurations across all applications

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
readonly TEMPLATES_DIR="$SCRIPT_DIR/templates"

# Application configurations
declare -A APP_CONFIGS
APP_CONFIGS["simple-php"]="Simple PHP,8000,3307,5433,6380,simple_php_db,"
APP_CONFIGS["slim-framework"]="Slim Framework,8001,3309,5435,6382,slim_framework_db,"
APP_CONFIGS["symfony-app"]="Symfony,8002,3308,5434,6381,symfony_app_db,console"
APP_CONFIGS["codeigniter-app"]="CodeIgniter,8003,3310,5436,6383,codeigniter_app_db,spark"
APP_CONFIGS["laravel-app"]="Laravel,8004,3311,5437,6384,laravel_app_db,artisan dev"

# Framework-specific configurations
declare -A FRAMEWORK_CONFIGS

# Laravel specific
FRAMEWORK_CONFIGS["laravel-app"]='
# Laravel-Specific Configuration
LARAVEL_APP_KEY=
LARAVEL_APP_URL=http://localhost:8004
LARAVEL_DB_CONNECTION=mysql
LARAVEL_CACHE_DRIVER=redis
LARAVEL_SESSION_DRIVER=redis
LARAVEL_QUEUE_CONNECTION=redis
LARAVEL_MAIL_MAILER=log'

# Symfony specific
FRAMEWORK_CONFIGS["symfony-app"]='
# Symfony-Specific Configuration
SYMFONY_APP_SECRET=
SYMFONY_DATABASE_URL=mysql://root:rootpassword@localhost:3308/symfony_app_db
SYMFONY_REDIS_URL=redis://localhost:6381
SYMFONY_CACHE_DRIVER=redis
SYMFONY_SESSION_DRIVER=redis
SYMFONY_MAILER_DSN=null://null'

# CodeIgniter specific
FRAMEWORK_CONFIGS["codeigniter-app"]='
# CodeIgniter-Specific Configuration
CI_ENVIRONMENT=production
CI_BASE_URL=http://localhost:8003
CI_DATABASE_DEFAULT_HOSTNAME=localhost
CI_DATABASE_DEFAULT_DATABASE=codeigniter_app_db
CI_DATABASE_DEFAULT_USERNAME=root
CI_DATABASE_DEFAULT_PASSWORD=rootpassword
CI_DATABASE_DEFAULT_PORT=3310'

# Framework-specific help sections
declare -A FRAMEWORK_HELP_SECTIONS

FRAMEWORK_HELP_SECTIONS["laravel-app"]='
	@echo "$(PURPLE)🎨 Laravel Specific:$(NC)"
	@echo "  $(YELLOW)make artisan$(NC)       - Laravel Artisan CLI"
	@echo "  $(YELLOW)make dev$(NC)           - Start development server"'

FRAMEWORK_HELP_SECTIONS["symfony-app"]='
	@echo "$(PURPLE)🎼 Symfony Specific:$(NC)"
	@echo "  $(YELLOW)make console$(NC)       - Symfony Console CLI"'

FRAMEWORK_HELP_SECTIONS["codeigniter-app"]='
	@echo "$(PURPLE)⚡ CodeIgniter Specific:$(NC)"
	@echo "  $(YELLOW)make spark$(NC)         - CodeIgniter Spark CLI"
	@echo "  $(YELLOW)make dev$(NC)           - Start development server"'

FRAMEWORK_HELP_SECTIONS["slim-framework"]='
	@echo "$(PURPLE)🏃 Slim Framework Specific:$(NC)"
	@echo "  $(YELLOW)make dev$(NC)           - Start development server"'

# Framework-specific command sections
declare -A FRAMEWORK_COMMAND_SECTIONS

FRAMEWORK_COMMAND_SECTIONS["laravel-app"]='
artisan: ## Laravel Artisan CLI
	@echo "$(BLUE)🎨 Laravel Artisan CLI$(NC)"
	@if [ -f artisan ]; then \
		if [ "$(filter-out $@,$(MAKECMDGOALS))" ]; then \
			php artisan $(filter-out $@,$(MAKECMDGOALS)); \
		else \
			php artisan list; \
		fi; \
	else \
		echo "$(RED)❌ Artisan not found$(NC)"; \
		exit 1; \
	fi

dev: ## Start Laravel development server
	@echo "$(BLUE)🔧 Starting Laravel development server...$(NC)"
	@php artisan serve --host=localhost --port=$(DEFAULT_PORT)'

FRAMEWORK_COMMAND_SECTIONS["symfony-app"]='
console: ## Symfony Console CLI
	@echo "$(BLUE)🎼 Symfony Console CLI$(NC)"
	@if [ -f bin/console ]; then \
		if [ "$(filter-out $@,$(MAKECMDGOALS))" ]; then \
			php bin/console $(filter-out $@,$(MAKECMDGOALS)); \
		else \
			php bin/console list; \
		fi; \
	else \
		echo "$(RED)❌ Symfony console not found$(NC)"; \
		exit 1; \
	fi'

FRAMEWORK_COMMAND_SECTIONS["codeigniter-app"]='
spark: ## CodeIgniter Spark CLI
	@echo "$(BLUE)⚡ CodeIgniter Spark CLI$(NC)"
	@if [ -f spark ]; then \
		if [ "$(filter-out $@,$(MAKECMDGOALS))" ]; then \
			php spark $(filter-out $@,$(MAKECMDGOALS)); \
		else \
			php spark list; \
		fi; \
	else \
		echo "$(RED)❌ Spark not found$(NC)"; \
		exit 1; \
	fi

dev: ## Start CodeIgniter development server
	@echo "$(BLUE)🔧 Starting CodeIgniter development server...$(NC)"
	@cd public && php -S localhost:$(DEFAULT_PORT)'

FRAMEWORK_COMMAND_SECTIONS["slim-framework"]='
dev: ## Start Slim Framework development server
	@echo "$(BLUE)🔧 Starting Slim Framework development server...$(NC)"
	@php -S localhost:$(DEFAULT_PORT) -t public'

# Framework usage sections
declare -A FRAMEWORK_USAGE_SECTIONS

FRAMEWORK_USAGE_SECTIONS["laravel-app"]='
# Laravel-specific commands:
# - make artisan        (Laravel Artisan CLI)
# - make artisan migrate (run database migrations)
# - make artisan tinker  (interactive shell)
# - make dev            (development server)'

FRAMEWORK_USAGE_SECTIONS["symfony-app"]='
# Symfony-specific commands:
# - make console        (Symfony Console CLI)
# - make console cache:clear (clear cache)
# - make console debug:router (show routes)'

FRAMEWORK_USAGE_SECTIONS["codeigniter-app"]='
# CodeIgniter-specific commands:
# - make spark          (CodeIgniter Spark CLI)
# - make spark migrate  (run database migrations)
# - make dev            (development server)'

FRAMEWORK_USAGE_SECTIONS["slim-framework"]='
# Slim Framework-specific commands:
# - make dev            (development server)'

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
    
    IFS=',' read -r APP_DISPLAY_NAME DEFAULT_PORT MYSQL_PORT POSTGRES_PORT REDIS_PORT DATABASE_NAME FRAMEWORK_COMMANDS <<< "$config"
    
    # Set framework-specific variables
    FRAMEWORK_CONFIG_SECTION="${FRAMEWORK_CONFIGS[$app]:-}"
    FRAMEWORK_HELP_SECTION="${FRAMEWORK_HELP_SECTIONS[$app]:-}"
    FRAMEWORK_COMMANDS_SECTION="${FRAMEWORK_COMMAND_SECTIONS[$app]:-}"
    FRAMEWORK_USAGE_SECTION="${FRAMEWORK_USAGE_SECTIONS[$app]:-}"
}

# Generate Makefile for application
generate_makefile() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    log_info "Generating standardized Makefile for $app"
    
    # Parse configuration
    parse_app_config "$app"
    
    # Read template
    local template_content
    template_content=$(cat "$TEMPLATES_DIR/Makefile.template")
    
    # Replace placeholders
    template_content="${template_content//\{\{APP_NAME\}\}/$app}"
    template_content="${template_content//\{\{APP_DISPLAY_NAME\}\}/$APP_DISPLAY_NAME}"
    template_content="${template_content//\{\{DEFAULT_PORT\}\}/$DEFAULT_PORT}"
    template_content="${template_content//\{\{FRAMEWORK_COMMANDS\}\}/$FRAMEWORK_COMMANDS}"
    template_content="${template_content//\{\{FRAMEWORK_HELP_SECTION\}\}/$FRAMEWORK_HELP_SECTION}"
    template_content="${template_content//\{\{FRAMEWORK_COMMANDS_SECTION\}\}/$FRAMEWORK_COMMANDS_SECTION}"
    
    # Write Makefile
    echo "$template_content" > "$app_dir/Makefile"
    
    log_success "Makefile generated for $app"
}

# Generate configuration template for application
generate_config_template() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    log_info "Generating standardized configuration template for $app"
    
    # Parse configuration
    parse_app_config "$app"
    
    # Read template
    local template_content
    template_content=$(cat "$TEMPLATES_DIR/app.env.template")
    
    # Replace placeholders
    template_content="${template_content//\{\{APP_NAME\}\}/$app}"
    template_content="${template_content//\{\{APP_DISPLAY_NAME\}\}/$APP_DISPLAY_NAME}"
    template_content="${template_content//\{\{DEFAULT_PORT\}\}/$DEFAULT_PORT}"
    template_content="${template_content//\{\{MYSQL_PORT\}\}/$MYSQL_PORT}"
    template_content="${template_content//\{\{POSTGRES_PORT\}\}/$POSTGRES_PORT}"
    template_content="${template_content//\{\{REDIS_PORT\}\}/$REDIS_PORT}"
    template_content="${template_content//\{\{DATABASE_NAME\}\}/$DATABASE_NAME}"
    template_content="${template_content//\{\{TIMESTAMP\}\}/$(date '+%Y-%m-%d %H:%M:%S')}"
    template_content="${template_content//\{\{FRAMEWORK_CONFIG_SECTION\}\}/$FRAMEWORK_CONFIG_SECTION}"
    template_content="${template_content//\{\{FRAMEWORK_USAGE_SECTION\}\}/$FRAMEWORK_USAGE_SECTION}"
    
    # Ensure config directory exists
    mkdir -p "$app_dir/config"
    
    # Write configuration template
    echo "$template_content" > "$app_dir/config/app.env.example"
    
    log_success "Configuration template generated for $app"
}

# Copy standardized scripts to application
copy_standardized_scripts() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    log_info "Copying standardized scripts to $app"
    
    # Ensure scripts directory exists
    mkdir -p "$app_dir/scripts"
    
    # Copy common functions if it exists in simple-php (our reference)
    if [[ -f "$REPO_ROOT/simple-php/scripts/common-functions.sh" ]]; then
        cp "$REPO_ROOT/simple-php/scripts/common-functions.sh" "$app_dir/scripts/"
        log_success "Copied common-functions.sh to $app"
    fi
    
    # Make scripts executable
    chmod +x "$app_dir/scripts"/*.sh 2>/dev/null || true
}

# Standardize single application
standardize_application() {
    local app="$1"
    local app_dir="$REPO_ROOT/$app"
    
    if [[ ! -d "$app_dir" ]]; then
        log_warning "Application directory not found: $app_dir"
        return 1
    fi
    
    log_info "Standardizing application: $app"
    
    # Backup existing Makefile
    if [[ -f "$app_dir/Makefile" ]]; then
        cp "$app_dir/Makefile" "$app_dir/Makefile.backup.$(date +%Y%m%d-%H%M%S)"
        log_info "Backed up existing Makefile for $app"
    fi
    
    # Generate standardized files
    generate_makefile "$app"
    generate_config_template "$app"
    copy_standardized_scripts "$app"
    
    log_success "Standardization completed for $app"
}

# Main execution
main() {
    log_info "APM PHP Examples - Final Standardization Implementation"
    echo "======================================================="
    echo ""
    
    # Validate templates exist
    if [[ ! -f "$TEMPLATES_DIR/Makefile.template" ]] || [[ ! -f "$TEMPLATES_DIR/app.env.template" ]]; then
        log_error "Template files not found in $TEMPLATES_DIR"
        exit 1
    fi
    
    # Standardize all applications
    local applications=("simple-php" "slim-framework" "symfony-app" "codeigniter-app" "laravel-app")
    local success_count=0
    
    for app in "${applications[@]}"; do
        echo ""
        if standardize_application "$app"; then
            ((success_count++))
        fi
    done
    
    echo ""
    log_info "Standardization Summary"
    echo "======================="
    echo "Applications processed: ${#applications[@]}"
    echo "Successfully standardized: $success_count"
    
    if [[ $success_count -eq ${#applications[@]} ]]; then
        log_success "All applications standardized successfully!"
        echo ""
        echo -e "${BLUE}💡 Next steps:${NC}"
        echo "  1. Test each application: cd <app> && make help"
        echo "  2. Run setup: make setup"
        echo "  3. Configure: make compile"
        echo "  4. Start: make start"
        echo "  5. Check status: make status"
    else
        log_warning "Some applications failed to standardize"
        echo "Check the output above for details"
    fi
}

# Execute main function
main "$@"
