# Laravel Application - Standardized Makefile
# APM PHP Examples - Final Standardization Implementation

.PHONY: help setup compile start status php-status network-status disable enable stop down artisan dev

# =============================================================================
# CONFIGURATION
# =============================================================================
APP_NAME = laravel-app
DEFAULT_PORT = 8004
PHP_VERSION ?= 8.4
NETWORK_INTERFACE ?= 0.0.0.0

# =============================================================================
# COLORS FOR OUTPUT
# =============================================================================
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
PURPLE=\033[0;35m
CYAN=\033[0;36m
NC=\033[0m # No Color

# =============================================================================
# DEFAULT TARGET
# =============================================================================
.DEFAULT_GOAL := help

help: ## Show available commands
	@echo "$(BLUE)Laravel Application - APM Testing (Standardized)$(NC)"
	@echo "================================================================"
	@echo ""
	@echo "$(PURPLE)🔧 Core Operations:$(NC)"
	@echo "  $(YELLOW)make setup$(NC)         - Check system requirements and setup Docker services"
	@echo "  $(YELLOW)make compile$(NC)       - Interactive configuration wizard (PHP, deployment, network)"
	@echo "  $(YELLOW)make start$(NC)         - Deploy and start the application"
	@echo "  $(YELLOW)make status$(NC)        - Comprehensive application status with resource monitoring"
	@echo "  $(YELLOW)make stop$(NC)          - Stop application and cleanup resources"
	@echo "  $(YELLOW)make down$(NC)          - Complete cleanup (remove containers, configs, logs)"
	@echo ""
	@echo "$(PURPLE)🔍 Diagnostics:$(NC)"
	@echo "  $(YELLOW)make php-status$(NC)    - PHP version diagnostics and compatibility"
	@echo "  $(YELLOW)make network-status$(NC) - Network configuration and IP change detection"
	@echo ""
	@echo "$(PURPLE)⚡ Service Management:$(NC)"
	@echo "  $(YELLOW)make disable$(NC)       - Disable web server virtual host (keep containers)"
	@echo "  $(YELLOW)make enable$(NC)        - Enable web server virtual host"
	@echo ""
	@echo "$(PURPLE)🎨 Laravel Specific:$(NC)"
	@echo "  $(YELLOW)make artisan$(NC)       - Laravel Artisan CLI"
	@echo "  $(YELLOW)make dev$(NC)           - Start development server"
	@echo ""
	@echo "$(PURPLE)💡 Standardized Features:$(NC)"
	@echo "  • Cross-platform compatibility (Ubuntu, RHEL, Arch, etc.)"
	@echo "  • Robust error handling with detailed diagnostics"
	@echo "  • Port conflict detection and automatic resolution"
	@echo "  • Real-time resource monitoring and health checks"
	@echo "  • Configuration profiles (development/production)"
	@echo "  • Automatic firewall configuration for public access"
	@echo ""

setup: ## Check system requirements and setup
	@echo "$(BLUE)🔍 Checking system requirements...$(NC)"
	@./scripts/check-system.sh

compile: ## Compile application for deployment
	@echo "$(BLUE)🔨 Compiling Laravel application...$(NC)"
	@./scripts/compile-app.sh

start: ## Start the application
	@echo "$(BLUE)🚀 Starting Laravel application...$(NC)"
	@./scripts/start-app.sh

disable: ## Disable the application
	@echo "$(YELLOW)⏸️  Disabling Laravel application...$(NC)"
	@./scripts/disable-app.sh

enable: ## Enable the application
	@echo "$(GREEN)▶️  Enabling Laravel application...$(NC)"
	@./scripts/enable-app.sh

stop: ## Stop the application
	@echo "$(YELLOW)🛑 Stopping Laravel application...$(NC)"
	@./scripts/stop-app.sh

down: ## Remove containers and cleanup
	@echo "$(RED)🗑️  Cleaning up Laravel application...$(NC)"
	@./scripts/cleanup.sh

# Development helpers
test: ## Run tests
	@echo "$(BLUE)🧪 Running tests...$(NC)"
	@php artisan test

install: ## Install dependencies
	@echo "$(BLUE)📦 Installing dependencies...$(NC)"
	@composer install --optimize-autoloader

update: ## Update dependencies
	@echo "$(BLUE)🔄 Updating dependencies...$(NC)"
	@composer update

artisan: ## Laravel Artisan CLI
	@echo "$(BLUE)🎨 Laravel Artisan CLI$(NC)"
	@if [ -f artisan ]; then \
		php artisan list; \
	else \
		echo "$(RED)❌ Artisan not found$(NC)"; \
		exit 1; \
	fi

dev: ## Start Laravel development server
	@echo "$(BLUE)🔧 Starting Laravel development server...$(NC)"
	@php artisan serve --host=localhost --port=$(DEFAULT_PORT)

status: ## Show application status
	@echo "$(BLUE)📊 Laravel Application Status$(NC)"
	@echo "=============================="
	@./scripts/status.sh

php-status: ## Show PHP version status and compatibility
	@echo "$(BLUE)🐘 PHP Version Status$(NC)"
	@./scripts/php-version-manager.sh status

network-status: ## Show network status and IP changes
	@echo "$(BLUE)🌐 Network Status$(NC)"
	@./scripts/network-manager.sh status

# Docker Compose command detection (supports both docker-compose and docker compose)
