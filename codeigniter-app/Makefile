# CodeIgniter Application - Standardized Makefile
# APM PHP Examples - Final Standardization Implementation

.PHONY: help setup compile start status php-status network-status disable enable stop down spark dev

# =============================================================================
# CONFIGURATION
# =============================================================================
APP_NAME = codeigniter-app
DEFAULT_PORT = 8003
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
	@echo "$(BLUE)CodeIgniter Application - APM Testing (Standardized)$(NC)"
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
	@echo "$(PURPLE)⚡ CodeIgniter Specific:$(NC)"
	@echo "  $(YELLOW)make spark$(NC)         - CodeIgniter Spark CLI"
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
setup: ## Check system requirements and setup Docker services
	@echo "$(BLUE)🔍 Checking system requirements for CodeIgniter...$(NC)"
	@if [ ! -f scripts/common-functions.sh ]; then \
		echo "$(RED)❌ [setup] failed: Common functions not found$(NC)"; \
		echo "$(YELLOW)💡 Ensure standardized scripts are present in scripts/ directory$(NC)"; \
		exit 1; \
	fi
	@chmod +x scripts/*.sh 2>/dev/null || true
	@if ./scripts/check-system-hardened.sh; then \
		echo "$(GREEN)✅ [setup] completed successfully$(NC)"; \
		echo "$(BLUE)🔍 Verification: Run 'docker ps' to see running containers$(NC)"; \
		echo "$(BLUE)💡 Next step: Run 'make compile' to configure the application$(NC)"; \
	else \
		echo "$(RED)❌ [setup] failed: System requirements not met$(NC)"; \
		echo "$(YELLOW)💡 Check logs in logs/check-system.log$(NC)"; \
		echo "$(YELLOW)💡 Run 'VERBOSE=1 make setup' for detailed output$(NC)"; \
		exit 1; \
	fi

compile: ## Interactive configuration wizard (PHP version, deployment type, network)
	@echo "$(BLUE)🧙 Starting Configuration Wizard for CodeIgniter...$(NC)"
	@if [ ! -f scripts/compile-app-enhanced.sh ]; then \
		echo "$(RED)❌ [compile] failed: Enhanced compilation script not found$(NC)"; \
		echo "$(YELLOW)💡 Ensure scripts/compile-app-enhanced.sh exists$(NC)"; \
		exit 1; \
	fi
	@chmod +x scripts/compile-app-enhanced.sh 2>/dev/null || true
	@if ./scripts/compile-app-enhanced.sh; then \
		echo "$(GREEN)✅ [compile] completed successfully$(NC)"; \
		echo "$(BLUE)🔍 Verification: Check config/app.env for settings$(NC)"; \
		echo "$(BLUE)💡 Next step: Run 'make start' to deploy the application$(NC)"; \
		if [ "${CLEAR_CACHE}" = "1" ]; then \
			echo "$(BLUE)🧹 Clearing application cache...$(NC)"; \
			rm -rf writable/cache/* writable/logs/* 2>/dev/null || true; \
		fi; \
	else \
		echo "$(RED)❌ [compile] failed: Configuration incomplete$(NC)"; \
		echo "$(YELLOW)💡 Check logs in logs/compile-app.log$(NC)"; \
		echo "$(YELLOW)💡 Run 'VERBOSE=1 make compile' for detailed output$(NC)"; \
		exit 1; \
	fi

start: ## Deploy and start the application
	@echo "$(BLUE)🚀 Starting CodeIgniter application...$(NC)"
	@if [ ! -f config/app.env ]; then \
		echo "$(RED)❌ [start] failed: Configuration not found$(NC)"; \
		echo "$(YELLOW)💡 Run 'make compile' first to configure the application$(NC)"; \
		exit 1; \
	fi
	@if ./scripts/start-app.sh; then \
		echo "$(GREEN)✅ [start] completed successfully$(NC)"; \
		echo "$(BLUE)🔍 Verification: Visit http://localhost:$(DEFAULT_PORT) to test$(NC)"; \
		echo "$(BLUE)💡 Management: Use 'make status' to monitor the application$(NC)"; \
	else \
		echo "$(RED)❌ [start] failed: Application startup failed$(NC)"; \
		echo "$(YELLOW)💡 Check logs in logs/start-app.log$(NC)"; \
		echo "$(YELLOW)💡 Run 'make status' to diagnose issues$(NC)"; \
		exit 1; \
	fi

disable: ## Disable the application
	@echo "$(YELLOW)⏸️  Disabling CodeIgniter application...$(NC)"
	@./scripts/disable-app.sh

enable: ## Enable the application
	@echo "$(GREEN)▶️  Enabling CodeIgniter application...$(NC)"
	@./scripts/enable-app.sh

stop: ## Stop the application
	@echo "$(YELLOW)🛑 Stopping CodeIgniter application...$(NC)"
	@./scripts/stop-app.sh

down: ## Remove containers and cleanup
	@echo "$(RED)🗑️  Cleaning up CodeIgniter application...$(NC)"
	@./scripts/cleanup.sh

# Development helpers
test: ## Run tests
	@echo "$(BLUE)🧪 Running tests...$(NC)"
	@php vendor/bin/phpunit

install: ## Install dependencies
	@echo "$(BLUE)📦 Installing dependencies...$(NC)"
	@composer install --optimize-autoloader

update: ## Update dependencies
	@echo "$(BLUE)🔄 Updating dependencies...$(NC)"
	@composer update

dev: ## Start development server
	@echo "$(BLUE)🔧 Starting development server...$(NC)"
	@cd public && php -S localhost:$(DEFAULT_PORT)

spark: ## Run CodeIgniter Spark commands
	@echo "$(BLUE)⚡ CodeIgniter Spark CLI$(NC)"
	@php spark

status: ## Show application status
	@echo "$(BLUE)📊 CodeIgniter Application Status$(NC)"
	@echo "=================================="
	@./scripts/status.sh

php-status: ## Show PHP version status and compatibility
	@echo "$(BLUE)🐘 PHP Version Status$(NC)"
	@./scripts/php-version-manager.sh status

network-status: ## Show network status and IP changes
	@echo "$(BLUE)🌐 Network Status$(NC)"
	@./scripts/network-manager.sh status


# Docker Compose command detection (supports both docker-compose and docker compose)
