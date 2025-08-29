# Symfony Application - Production-Grade Makefile
# Supports PHP version changes, Apache/Nginx deployment, and network accessibility

# Configuration
PHP_VERSION ?= 8.4
ENVIRONMENT ?= dev
APP_NAME = symfony-app
DEFAULT_PORT = 8002
NETWORK_INTERFACE ?= 0.0.0.0

# Colors for output
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m # No Color

.PHONY: help setup compile start disable enable stop down test install update console dev status

# Default target
help:
	@echo "Symfony Application - Available Commands:"
	@echo ""
	@echo "Setup Commands:"
	@echo "  make setup          - Setup application dependencies"
	@echo "  make build          - Build Docker container"
	@echo "  make install        - Install PHP dependencies"
	@echo "  make cache-clear    - Clear Symfony cache"
	@echo ""
	@echo "Runtime Commands:"
	@echo "  make start          - Start the application"
	@echo "  make stop           - Stop the application"
	@echo "  make restart        - Restart the application"
	@echo ""
	@echo "Development Commands:"
	@echo "  make test           - Run tests"
	@echo "  make logs           - View application logs"
	@echo "  make console        - Run Symfony console commands"
	@echo ""
	@echo "Network Configuration:"
	@echo "  make network-local  - Configure for local access (127.0.0.1)"
	@echo "  make network-public - Configure for public access (0.0.0.0)"
	@echo ""
	@echo "Deployment Commands:"
	@echo "  make apache         - Deploy with Apache"
	@echo "  make nginx          - Deploy with Nginx"
	@echo "  make deploy         - Deploy for production"
	@echo "  make php-version    - Change PHP version (use PHP_VERSION=x.x)"
	@echo ""
	@echo "Cleanup Commands:"
	@echo "  make clean          - Clean up containers and volumes"
	@echo ""
	@echo "$(YELLOW)Simplified Commands:$(NC)"
	@echo "  $(BLUE)make setup$(NC)    - Check system requirements and setup"
	@echo "  $(BLUE)make compile$(NC)  - Compile application for deployment"
	@echo "  $(BLUE)make start$(NC)    - Start the application"
	@echo "  $(BLUE)make disable$(NC)  - Disable the application"
	@echo "  $(BLUE)make enable$(NC)   - Enable the application"
	@echo "  $(BLUE)make stop$(NC)     - Stop and cleanup application"
	@echo "  $(BLUE)make down$(NC)     - Remove all containers and volumes"

# Setup application
setup: build install cache-clear
	@echo "Symfony application setup complete!"

# Build Docker container
build:
	@echo "Building Symfony Docker container..."
	@docker build -t apm-symfony-app .

# Install dependencies
install:
	@echo "Installing Symfony dependencies..."
	@docker run --rm -v $(PWD):/app -w /app composer:latest install
	@if [ ! -f .env.local ]; then echo "APP_SECRET=$$(openssl rand -hex 16)" > .env.local; fi

# Clear Symfony cache
cache-clear:
	@echo "Clearing Symfony cache..."
	@docker run --rm -v $(PWD):/app -w /app apm-symfony-app php bin/console cache:clear

# Start application
start:
	@echo "Starting Symfony application..."
	@docker-compose -f ../docker-compose.yml up -d symfony-app
	@echo "Symfony application is running at http://localhost:8083"

# Stop application
stop:
	@echo "Stopping Symfony application..."
	@docker-compose -f ../docker-compose.yml stop symfony-app

# Restart application
restart: stop start

# Run tests
test:
	@echo "Running Symfony tests..."
	@docker run --rm -v $(PWD):/app -w /app apm-symfony-app php bin/phpunit

# View logs
logs:
	@docker-compose -f ../docker-compose.yml logs -f symfony-app

# Clean up
clean:
	@echo "Cleaning up Symfony application..."
	@docker-compose -f ../docker-compose.yml down
	@docker rmi apm-symfony-app || true

# Development helpers
console:
	@docker run --rm -it -v $(PWD):/app -w /app --network apm-php-examples_apm-network apm-symfony-app php bin/console

dev-shell:
	@docker run --rm -it -v $(PWD):/app -w /app apm-symfony-app bash

composer-update:
	@docker run --rm -v $(PWD):/app -w /app composer:latest update

# Network Configuration Commands
network-local: ## Configure for local access (127.0.0.1)
	@echo "$(GREEN)Configuring Symfony for local access...$(NC)"
	@$(MAKE) start NETWORK_INTERFACE=127.0.0.1
	@echo "$(GREEN)✅ Configured for local access$(NC)"

network-public: ## Configure for public access (0.0.0.0)
	@echo "$(GREEN)Configuring Symfony for public access...$(NC)"
	@$(MAKE) start NETWORK_INTERFACE=0.0.0.0
	@echo "$(GREEN)✅ Configured for public access$(NC)"
	@echo "$(YELLOW)⚠️  Application is now accessible from the internet!$(NC)"

# PHP Version Management
php-version: ## Change PHP version (use PHP_VERSION=x.x)
	@echo "$(GREEN)Changing PHP version to $(PHP_VERSION)...$(NC)"
	@sed -i 's/FROM php:[0-9]\+\.[0-9]\+/FROM php:$(PHP_VERSION)/' Dockerfile
	@$(MAKE) build
	@echo "$(GREEN)✅ PHP version changed to $(PHP_VERSION)$(NC)"

# Deployment Options
apache: ## Deploy with Apache
	@echo "$(GREEN)Deploying Symfony with Apache...$(NC)"
	@docker run -d --name $(APP_NAME)-apache \
		-p $(PORT):80 \
		-v $(PWD)/public:/var/www/html \
		--network apm-php-examples_apm-network \
		php:$(PHP_VERSION)-apache
	@echo "$(GREEN)✅ Symfony deployed with Apache on port $(PORT)$(NC)"

nginx: ## Deploy with Nginx
	@echo "$(GREEN)Deploying Symfony with Nginx...$(NC)"
	@docker run -d --name $(APP_NAME)-nginx \
		-p $(PORT):80 \
		-v $(PWD)/public:/var/www/html \
		--network apm-php-examples_apm-network \
		nginx:alpine
	@echo "$(GREEN)✅ Symfony deployed with Nginx on port $(PORT)$(NC)"

deploy: ## Deploy for production
	@echo "$(GREEN)Deploying Symfony for production...$(NC)"
	@$(MAKE) build ENVIRONMENT=prod
	@$(MAKE) start ENVIRONMENT=prod NETWORK_INTERFACE=0.0.0.0
	@echo "$(GREEN)✅ Symfony deployed for production$(NC)"

# Environment Management
dev: ## Start in development mode
	@$(MAKE) start ENVIRONMENT=dev

prod: ## Start in production mode
	@$(MAKE) start ENVIRONMENT=prod

status: ## Show application status
	@echo "$(GREEN)Symfony Application Status:$(NC)"
	@docker ps --filter "name=$(APP_NAME)" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Symfony specific commands
debug-router:
	@docker run --rm -v $(PWD):/app -w /app apm-symfony-app php bin/console debug:router

debug-container:
	@docker run --rm -v $(PWD):/app -w /app apm-symfony-app php bin/console debug:container

messenger-consume:
	@docker run --rm -it -v $(PWD):/app -w /app --network apm-php-examples_apm-network apm-symfony-app php bin/console messenger:consume async

# Simplified Commands for APM Testing
compile: ## Compile application for deployment
	@echo "$(BLUE)🔨 Compiling Symfony application...$(NC)"
	@./scripts/compile-app.sh

disable: ## Disable the application
	@echo "$(YELLOW)⏸️  Disabling Symfony application...$(NC)"
	@./scripts/disable-app.sh

enable: ## Enable the application
	@echo "$(GREEN)▶️  Enabling Symfony application...$(NC)"
	@./scripts/enable-app.sh

down: ## Remove containers and cleanup
	@echo "$(RED)🗑️  Cleaning up Symfony application...$(NC)"
	@./scripts/cleanup.sh

# Development helpers for simplified workflow
update: ## Update dependencies
	@echo "$(BLUE)🔄 Updating dependencies...$(NC)"
	@composer update

dev-simple: ## Start development server (simplified)
	@echo "$(BLUE)🔧 Starting development server...$(NC)"
	@php -S localhost:$(DEFAULT_PORT) -t public