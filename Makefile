# APM PHP Examples - Unified Development Makefile
# Provides consistent commands across all applications

.PHONY: help install lint analyze test coverage benchmark clean setup-dev

# Default target
help: ## Show this help message
	@echo "APM PHP Examples - Development Commands"
	@echo "======================================"
	@echo ""
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Development setup
setup-dev: ## Set up development environment for all applications
	@echo "🔧 Setting up development environment..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ]; then \
			echo "Setting up $$app..."; \
			cd $$app && \
			if [ ! -f "composer.json" ]; then echo "No composer.json in $$app"; cd ..; continue; fi && \
			composer install --dev && \
			cd ..; \
		fi; \
	done
	@echo "✅ Development environment ready"

# Installation
install: ## Install dependencies for all applications
	@echo "📦 Installing dependencies..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/composer.json" ]; then \
			echo "Installing $$app dependencies..."; \
			cd $$app && composer install --optimize-autoloader && cd ..; \
		fi; \
	done

# Code quality
lint: ## Run PHP CS Fixer on all applications
	@echo "🧹 Running code linting..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/composer.json" ]; then \
			echo "Linting $$app..."; \
			cd $$app && \
			if [ -f "vendor/bin/php-cs-fixer" ]; then \
				vendor/bin/php-cs-fixer fix --dry-run --diff; \
			else \
				echo "PHP CS Fixer not installed in $$app"; \
			fi && \
			cd ..; \
		fi; \
	done

analyze: ## Run static analysis on all applications
	@echo "🔍 Running static analysis..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/composer.json" ]; then \
			echo "Analyzing $$app..."; \
			cd $$app && \
			if [ -f "vendor/bin/phpstan" ]; then \
				vendor/bin/phpstan analyse --level=max; \
			else \
				echo "PHPStan not installed in $$app"; \
			fi && \
			cd ..; \
		fi; \
	done

# Testing
test: ## Run tests for all applications
	@echo "🧪 Running tests..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/composer.json" ]; then \
			echo "Testing $$app..."; \
			cd $$app && \
			if [ -f "vendor/bin/phpunit" ]; then \
				vendor/bin/phpunit; \
			else \
				echo "PHPUnit not installed in $$app"; \
			fi && \
			cd ..; \
		fi; \
	done

coverage: ## Generate test coverage reports
	@echo "📊 Generating coverage reports..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/composer.json" ]; then \
			echo "Coverage for $$app..."; \
			cd $$app && \
			if [ -f "vendor/bin/phpunit" ]; then \
				vendor/bin/phpunit --coverage-html coverage/; \
			fi && \
			cd ..; \
		fi; \
	done

# Performance
benchmark: ## Run performance benchmarks
	@echo "⚡ Running performance benchmarks..."
	@echo "Starting all applications for benchmarking..."
	@./benchmark-all-apps.sh

# Docker operations
docker-build: ## Build Docker images for all applications
	@echo "🐳 Building Docker images..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/Dockerfile" ]; then \
			echo "Building $$app image..."; \
			cd $$app && docker build -t apm-$$app:latest . && cd ..; \
		fi; \
	done

docker-up: ## Start all Docker services
	@echo "🚀 Starting all Docker services..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/docker-compose.yml" ]; then \
			echo "Starting $$app services..."; \
			cd $$app && docker compose up -d && cd ..; \
		fi; \
	done

docker-down: ## Stop all Docker services
	@echo "🛑 Stopping all Docker services..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ] && [ -f "$$app/docker-compose.yml" ]; then \
			echo "Stopping $$app services..."; \
			cd $$app && docker compose down && cd ..; \
		fi; \
	done

# Cleanup
clean: ## Clean up generated files and caches
	@echo "🧽 Cleaning up..."
	@for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do \
		if [ -d "$$app" ]; then \
			echo "Cleaning $$app..."; \
			cd $$app && \
			rm -rf coverage/ && \
			rm -rf .phpunit.result.cache && \
			if [ -d "var/cache" ]; then rm -rf var/cache/*; fi && \
			cd ..; \
		fi; \
	done

# Validation
validate: ## Run complete validation suite
	@echo "✅ Running complete validation..."
	@make lint
	@make analyze  
	@make test
	@make coverage
	@echo "🎉 Validation complete!"

# PHP version compatibility check
php-compat: ## Check PHP version compatibility
	@echo "🐘 Checking PHP compatibility..."
	@php -v
	@php -m | grep -E "(pdo|redis|curl|json|mbstring|openssl)"
