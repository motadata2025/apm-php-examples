# Slim Framework Application - Simplified Makefile
# APM PHP Examples - Independent Application

.PHONY: help setup compile start disable enable stop down

# Configuration
APP_NAME = slim-framework
DEFAULT_PORT = 8003
PHP_VERSION ?= 8.4
NETWORK_INTERFACE ?= 127.0.0.1

# Colors for output
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m # No Color

help: ## Show available commands
	@echo "$(BLUE)Slim Framework Application - APM Testing$(NC)"
	@echo "=========================================="
	@echo ""
	@echo "Available commands:"
	@echo "  $(YELLOW)make setup$(NC)    - Check system requirements and setup"
	@echo "  $(YELLOW)make compile$(NC)  - Compile application for deployment"
	@echo "  $(YELLOW)make start$(NC)    - Start the application"
	@echo "  $(YELLOW)make disable$(NC)  - Disable the application"
	@echo "  $(YELLOW)make enable$(NC)   - Enable the application"
	@echo "  $(YELLOW)make stop$(NC)     - Stop and cleanup application"
	@echo "  $(YELLOW)make down$(NC)     - Remove all containers and volumes"
	@echo ""

setup: ## Check system requirements and setup
	@echo "$(BLUE)🔍 Checking system requirements...$(NC)"
	@./scripts/check-system.sh

compile: ## Compile application for deployment
	@echo "$(BLUE)🔨 Compiling Slim Framework application...$(NC)"
	@./scripts/compile-app.sh

start: ## Start the application
	@echo "$(BLUE)🚀 Starting Slim Framework application...$(NC)"
	@./scripts/start-app.sh

disable: ## Disable the application
	@echo "$(YELLOW)⏸️  Disabling Slim Framework application...$(NC)"
	@./scripts/disable-app.sh

enable: ## Enable the application
	@echo "$(GREEN)▶️  Enabling Slim Framework application...$(NC)"
	@./scripts/enable-app.sh

stop: ## Stop the application
	@echo "$(YELLOW)🛑 Stopping Slim Framework application...$(NC)"
	@./scripts/stop-app.sh

down: ## Remove containers and cleanup
	@echo "$(RED)🗑️  Cleaning up Slim Framework application...$(NC)"
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

status: ## Show application status
	@echo "$(BLUE)📊 Slim Framework Application Status$(NC)"
	@echo "===================================="
	@./scripts/status.sh

