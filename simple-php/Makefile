# Simple PHP Application - Simplified Makefile
# APM PHP Examples - Independent Application

.PHONY: help setup compile start disable enable stop down

# Configuration
APP_NAME = simple-php
DEFAULT_PORT = 8000
PHP_VERSION ?= 8.4
NETWORK_INTERFACE ?= 127.0.0.1

# Colors for output
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m # No Color

help: ## Show available commands
	@echo "$(BLUE)Simple PHP Application - APM Testing$(NC)"
	@echo "======================================"
	@echo ""
	@echo "Available commands:"
	@echo "  $(YELLOW)make setup$(NC)      - Check system requirements and setup"
	@echo "  $(YELLOW)make compile$(NC)    - Compile application for deployment"
	@echo "  $(YELLOW)make start$(NC)      - Start the application"
	@echo "  $(YELLOW)make status$(NC)        - Show application status"
	@echo "  $(YELLOW)make php-status$(NC)    - Show PHP version status and compatibility"
	@echo "  $(YELLOW)make network-status$(NC) - Show network status and IP changes"
	@echo "  $(YELLOW)make disable$(NC)    - Disable the application"
	@echo "  $(YELLOW)make enable$(NC)     - Enable the application"
	@echo "  $(YELLOW)make stop$(NC)       - Stop and cleanup application"
	@echo "  $(YELLOW)make down$(NC)       - Complete cleanup (remove all files and config)"
	@echo ""

setup: ## Check system requirements and setup
	@echo "$(BLUE)🔍 Checking system requirements...$(NC)"
	@./scripts/check-system.sh

compile: ## Compile application for deployment
	@echo "$(BLUE)🔨 Compiling Simple PHP application...$(NC)"
	@./scripts/compile-app.sh

start: ## Start the application
	@echo "$(BLUE)🚀 Starting Simple PHP application...$(NC)"
	@./scripts/start-app.sh

disable: ## Disable the application
	@echo "$(YELLOW)⏸️  Disabling Simple PHP application...$(NC)"
	@./scripts/disable-app.sh

enable: ## Enable the application
	@echo "$(GREEN)▶️  Enabling Simple PHP application...$(NC)"
	@./scripts/enable-app.sh

stop: ## Stop the application
	@echo "$(YELLOW)🛑 Stopping Simple PHP application...$(NC)"
	@./scripts/stop-app.sh

# Development helpers
test: ## Run tests
	@echo "$(BLUE)🧪 Running tests...$(NC)"
	@php vendor/bin/phpunit

install: ## Install dependencies
	@echo "$(BLUE)📦 Installing dependencies...$(NC)"
	@composer install --optimize-autoloader

status: ## Show application status
	@echo "$(BLUE)📊 Simple PHP Application Status$(NC)"
	@echo "================================="
	@./scripts/status.sh

php-status: ## Show PHP version status and compatibility
	@echo "$(BLUE)🐘 PHP Version Status$(NC)"
	@./scripts/php-version-manager.sh status

network-status: ## Show network status and IP changes
	@echo "$(BLUE)🌐 Network Status$(NC)"
	@./scripts/network-manager.sh status

down: ## Complete cleanup - remove all application files and configuration
	@echo "$(RED)🗑️ Complete cleanup of Simple PHP application...$(NC)"
	@./scripts/cleanup.sh