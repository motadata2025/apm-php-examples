# Symfony Application - Standardized Makefile
# APM PHP Examples - Final Standardization Implementation

.PHONY: help setup compile start status php-status network-status disable enable stop down console

# =============================================================================
# CONFIGURATION
# =============================================================================
APP_NAME = symfony-app
DEFAULT_PORT = 8002
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
	@echo "$(BLUE)Symfony Application - APM Testing (Standardized)$(NC)"
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
	@echo "$(PURPLE)🎼 Symfony Specific:$(NC)"
	@echo "  $(YELLOW)make console$(NC)       - Symfony Console CLI"
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
	@echo "$(BLUE)🔍 Checking system requirements for Symfony...$(NC)"
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
	@echo "$(BLUE)🧙 Starting Configuration Wizard for Symfony...$(NC)"
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
			rm -rf var/cache/* 2>/dev/null || true; \
		fi; \
	else \
		echo "$(RED)❌ [compile] failed: Configuration incomplete$(NC)"; \
		echo "$(YELLOW)💡 Check logs in logs/compile-app.log$(NC)"; \
		echo "$(YELLOW)💡 Run 'VERBOSE=1 make compile' for detailed output$(NC)"; \
		exit 1; \
	fi

start: ## Deploy and start the application
	@echo "$(BLUE)🚀 Starting Symfony application...$(NC)"
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

status: ## Comprehensive application status with resource monitoring
	@echo "$(BLUE)📊 Symfony Application Status$(NC)"
	@echo "================================================"
	@if [ -f config/app.env ]; then \
		echo "$(PURPLE)Configuration Status:$(NC)"; \
		. config/app.env && echo "  App: $$APP_NAME (Port: $$APP_PORT)"; \
		. config/app.env && echo "  PHP: $$PHP_VERSION ($$DEPLOYMENT_TYPE)"; \
		. config/app.env && echo "  Environment: $$APP_ENV (Debug: $$APP_DEBUG)"; \
		. config/app.env && echo "  Network: $$NETWORK_INTERFACE"; \
	else \
		echo "$(YELLOW)⚠️  Configuration: Not found (run 'make compile')$(NC)"; \
	fi
	@echo ""
	@echo "$(PURPLE)Docker Container Status:$(NC)"
	@if command -v docker >/dev/null 2>&1; then \
		COMPOSE_CMD=$$(if docker compose version >/dev/null 2>&1; then echo "docker compose"; else echo "docker-compose"; fi); \
		if $$COMPOSE_CMD ps 2>/dev/null | grep -q "$(APP_NAME)"; then \
			$$COMPOSE_CMD ps; \
			echo ""; \
			echo "$(PURPLE)Container Resource Usage:$(NC)"; \
			docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" $$(docker ps --filter "name=$(APP_NAME)" --format "{{.Names}}" 2>/dev/null) 2>/dev/null || echo "  No resource data available"; \
		else \
			echo "  No containers running"; \
		fi; \
	else \
		echo "  Docker not available"; \
	fi
	@./scripts/status.sh 2>/dev/null || echo "$(YELLOW)⚠️  Detailed status script not available$(NC)"

php-status: ## PHP version diagnostics and compatibility
	@echo "$(BLUE)🐘 PHP Version Status for Symfony$(NC)"
	@echo "================================================"
	@if [ -f scripts/php-version-manager.sh ]; then \
		./scripts/php-version-manager.sh status; \
	else \
		echo "$(YELLOW)⚠️  PHP version manager not available$(NC)"; \
		echo "$(BLUE)System PHP version:$(NC)"; \
		php --version 2>/dev/null || echo "  PHP not found"; \
	fi

network-status: ## Network configuration and IP change detection
	@echo "$(BLUE)🌐 Network Status for Symfony$(NC)"
	@echo "============================================="
	@if [ -f scripts/network-manager.sh ]; then \
		./scripts/network-manager.sh status; \
	else \
		echo "$(YELLOW)⚠️  Network manager not available$(NC)"; \
		echo "$(BLUE)Current network configuration:$(NC)"; \
		if [ -f config/app.env ]; then \
			. config/app.env && echo "  Interface: $$NETWORK_INTERFACE"; \
			. config/app.env && echo "  Port: $$APP_PORT"; \
		fi; \
	fi

disable: ## Disable web server virtual host (keep containers running)
	@echo "$(YELLOW)⏸️  Disabling Symfony web server...$(NC)"
	@if [ -f scripts/webserver-manager.sh ]; then \
		./scripts/webserver-manager.sh disable; \
		echo "$(GREEN)✅ [disable] completed - application disabled$(NC)"; \
		echo "$(BLUE)💡 Use 'make enable' to re-enable the application$(NC)"; \
	else \
		echo "$(YELLOW)⚠️  Web server manager not available$(NC)"; \
		echo "$(BLUE)💡 Use 'make stop' to stop the application completely$(NC)"; \
	fi

enable: ## Enable web server virtual host
	@echo "$(GREEN)▶️  Enabling Symfony web server...$(NC)"
	@if [ -f scripts/webserver-manager.sh ]; then \
		./scripts/webserver-manager.sh enable; \
		echo "$(GREEN)✅ [enable] completed - application enabled$(NC)"; \
		echo "$(BLUE)🔍 Verification: Visit http://localhost:$(DEFAULT_PORT) to test$(NC)"; \
	else \
		echo "$(YELLOW)⚠️  Web server manager not available$(NC)"; \
		echo "$(BLUE)💡 Use 'make start' to start the application$(NC)"; \
	fi

stop: ## Stop application and cleanup resources
	@echo "$(YELLOW)🛑 Stopping Symfony application...$(NC)"
	@if ./scripts/stop-app.sh 2>/dev/null; then \
		echo "$(GREEN)✅ [stop] completed successfully$(NC)"; \
		echo "$(BLUE)🔍 Verification: Application stopped, containers may remain$(NC)"; \
		echo "$(BLUE)💡 Use 'make down' for complete cleanup$(NC)"; \
	else \
		echo "$(YELLOW)⚠️  Stop script not available, attempting basic cleanup...$(NC)"; \
		if command -v docker >/dev/null 2>&1; then \
			COMPOSE_CMD=$$(if docker compose version >/dev/null 2>&1; then echo "docker compose"; else echo "docker-compose"; fi); \
			$$COMPOSE_CMD stop 2>/dev/null || true; \
		fi; \
		echo "$(GREEN)✅ [stop] basic cleanup completed$(NC)"; \
	fi

down: ## Complete cleanup (remove containers, configs, logs)
	@echo "$(RED)🗑️  Complete cleanup of Symfony application...$(NC)"
	@echo "$(YELLOW)⚠️  This will remove all containers, configuration, and optionally volumes$(NC)"
	@./scripts/stop-app.sh 2>/dev/null || true
	@if command -v docker >/dev/null 2>&1; then \
		COMPOSE_CMD=$$(if docker compose version >/dev/null 2>&1; then echo "docker compose"; else echo "docker-compose"; fi); \
		if [ "${REMOVE_VOLUMES}" = "1" ]; then \
			echo "$(YELLOW)🗑️  Removing containers and volumes...$(NC)"; \
			$$COMPOSE_CMD down -v --remove-orphans 2>/dev/null || true; \
		else \
			echo "$(YELLOW)🗑️  Removing containers (keeping volumes)...$(NC)"; \
			$$COMPOSE_CMD down --remove-orphans 2>/dev/null || true; \
		fi; \
		if [ "${CLEAR_CACHE}" = "1" ]; then \
			echo "$(BLUE)🧹 Clearing application cache...$(NC)"; \
			rm -rf var/cache/* logs/*.log 2>/dev/null || true; \
		fi; \
		echo "$(BLUE)🔍 Verifying cleanup...$(NC)"; \
		REMAINING=$$(docker ps -a --filter "name=$(APP_NAME)" --format "{{.Names}}" 2>/dev/null | wc -l); \
		if [ "$$REMAINING" -eq 0 ]; then \
			echo "$(GREEN)✅ [down] completed successfully - all containers removed$(NC)"; \
		else \
			echo "$(YELLOW)⚠️  Some containers may still exist, run 'docker ps -a' to check$(NC)"; \
		fi; \
	else \
		echo "$(YELLOW)⚠️  Docker not available, skipping container cleanup$(NC)"; \
	fi
	@echo "$(BLUE)💡 Use 'REMOVE_VOLUMES=1 make down' to also remove data volumes$(NC)"
	@echo "$(BLUE)💡 Use 'CLEAR_CACHE=1 make down' to also clear cache and logs$(NC)"

# =============================================================================
# FRAMEWORK-SPECIFIC COMMANDS
# =============================================================================

console: ## Symfony Console CLI
	@echo "$(BLUE)🎼 Symfony Console CLI$(NC)"
	@if [ -f bin/console ]; then \
		php bin/console list; \
	else \
		echo "$(RED)❌ Symfony console not found$(NC)"; \
		exit 1; \
	fi

# =============================================================================
# ENVIRONMENT VARIABLES FOR ENHANCED CONTROL
# =============================================================================
# VERBOSE=1          - Enable detailed logging
# CLEAR_CACHE=1      - Clear application cache during operations
# REMOVE_VOLUMES=1   - Remove Docker volumes during cleanup
# AUTO_CONFIRM=1     - Skip confirmation prompts (non-interactive mode)
# PHP_VERSION=8.x    - Override PHP version selection
# NETWORK_INTERFACE  - Override network interface (127.0.0.1, 0.0.0.0, custom IP)


# Docker Compose command detection (supports both docker-compose and docker compose)
