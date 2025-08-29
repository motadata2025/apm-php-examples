# APM PHP Examples - Main Makefile
# Global automation commands for all PHP applications

# Load configuration if it exists
-include config/deployment.env
export

.PHONY: help setup start stop clean test endpoints logs status wizard configure validate select-php show-config test-cli start-services start-services-admin stop-services start-local stop-local restart

# Default target
help:
	@echo "APM PHP Examples - Available Commands:"
	@echo ""
	@echo "Configuration Commands:"
	@echo "  make wizard         - Interactive deployment wizard (recommended)"
	@echo "  make configure      - Configure deployment settings interactively"
	@echo "  make validate       - Validate deployment configuration"
	@echo "  make select-php     - Select PHP version interactively"
	@echo "  make show-config    - Show current configuration"
	@echo ""
	@echo "Setup Commands:"
	@echo "  make setup               - Setup local PHP environment and supporting services"
	@echo ""
	@echo "Service Commands:"
	@echo "  make start-services      - Start supporting services (databases, Redis)"
	@echo "  make start-services-admin - Start services with admin interfaces"
	@echo "  make stop-services       - Stop supporting services"
	@echo ""
	@echo "Application Commands:"
	@echo "  make start-local         - Start PHP applications locally"
	@echo "  make stop-local          - Stop local PHP applications"
	@echo "  make start               - Start everything (services + applications)"
	@echo "  make stop                - Stop everything"
	@echo "  make restart             - Restart everything"
	@echo ""
	@echo "Development Commands:"
	@echo "  make test                - Run comprehensive application tests"
	@echo "  make test-cli            - Run tests using PHP CLI"
	@echo "  make php-versions        - List available PHP versions"
	@echo "  make validate-php        - Validate PHP setup"
	@echo "  make logs                - View logs for supporting services"
	@echo "  make status              - Show status of services and applications"
	@echo ""
	@echo "Management Commands:"
	@echo "  make endpoints           - Show all application and service endpoints"
	@echo "  make clean               - Clean up containers and volumes"
	@echo ""
	@echo "Network Configuration Commands:"
	@echo "  make network-local       - Configure for local development (127.0.0.1)"
	@echo "  make network-public      - Configure for public access (0.0.0.0)"
	@echo "  make network-custom IP=x - Configure for custom IP address"
	@echo ""
	@echo "Library Management Commands:"
	@echo "  make install-php VER=x.x - Install specific PHP version (8.1-8.4)"
	@echo "  make install-lib LIB=pkg - Install PHP library for application"
	@echo "  make compile-app APP=name - Compile application with current PHP"
	@echo "  make list-php            - List available PHP versions"
	@echo "  make list-libraries      - List installed libraries"
	@echo ""
	@echo "Production Commands:"
	@echo "  make prod                - Deploy for production with Nginx"
	@echo "  make prod-stop           - Stop production deployment"
	@echo ""
	@echo "Information Commands:"
	@echo "  make endpoints      - Display all running application endpoints"
	@echo ""
	@echo "Cleanup Commands:"
	@echo "  make clean          - Remove all containers and volumes"
	@echo "  make clean-images   - Remove all built images"
	@echo ""
	@echo "PHP Version Commands:"
	@echo "  make compile-81     - Build with PHP 8.1"
	@echo "  make compile-82     - Build with PHP 8.2"
	@echo "  make compile-83     - Build with PHP 8.3"
	@echo "  make compile-84     - Build with PHP 8.4 (default)"

# Configuration commands
wizard:
	@echo "Starting interactive deployment wizard..."
	@./scripts/deployment-wizard.sh

configure:
	@echo "Starting deployment configuration..."
	@./scripts/configure-deployment.sh

validate:
	@echo "Validating deployment configuration..."
	@./scripts/validate-deployment.sh

select-php:
	@echo "Starting PHP version selection..."
	@./scripts/select-php-version.sh

show-config:
	@echo "Current Configuration:"
	@echo "====================="
	@if [ -f config/deployment.env ]; then \
		cat config/deployment.env | grep -E "^[A-Z]" | head -20; \
	else \
		echo "No configuration found. Run 'make configure' to create one."; \
	fi

# Setup infrastructure
setup:
	@echo "Setting up APM PHP Examples infrastructure..."
	@echo "Step 1: Validating deployment configuration..."
	@$(MAKE) validate
	@echo "Step 2: Checking Docker requirements..."
	@docker --version || (echo "Docker is required but not installed. Please install Docker first." && exit 1)
	@docker compose version || (echo "Docker Compose is required but not installed. Please install Docker Compose first." && exit 1)
	@echo "Step 3: Creating shared database initialization files..."
	@mkdir -p shared/database
	@$(MAKE) create-db-init-files
	@echo "Step 4: Setting up local PHP environment..."
	@./scripts/setup-local-php.sh
	@echo "✅ Setup complete! Use 'make start-services' to start supporting services, then './start-all-local.sh' to start applications."

# Create database initialization files
create-db-init-files:
	@echo "-- MySQL initialization script" > shared/database/mysql-init.sql
	@echo "CREATE DATABASE IF NOT EXISTS laravel_db;" >> shared/database/mysql-init.sql
	@echo "CREATE DATABASE IF NOT EXISTS symfony_db;" >> shared/database/mysql-init.sql
	@echo "CREATE DATABASE IF NOT EXISTS slim_db;" >> shared/database/mysql-init.sql
	@echo "CREATE DATABASE IF NOT EXISTS codeigniter_db;" >> shared/database/mysql-init.sql
	@echo "USE apm_db;" >> shared/database/mysql-init.sql
	@echo "CREATE TABLE IF NOT EXISTS users (" >> shared/database/mysql-init.sql
	@echo "  id BIGINT AUTO_INCREMENT PRIMARY KEY," >> shared/database/mysql-init.sql
	@echo "  email VARCHAR(255) UNIQUE NOT NULL," >> shared/database/mysql-init.sql
	@echo "  name VARCHAR(255) NOT NULL," >> shared/database/mysql-init.sql
	@echo "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP" >> shared/database/mysql-init.sql
	@echo ");" >> shared/database/mysql-init.sql
	@echo ""
	@echo "-- PostgreSQL initialization script" > shared/database/postgres-init.sql
	@echo "CREATE DATABASE laravel_db;" >> shared/database/postgres-init.sql
	@echo "CREATE DATABASE symfony_db;" >> shared/database/postgres-init.sql
	@echo "CREATE DATABASE slim_db;" >> shared/database/postgres-init.sql
	@echo "CREATE DATABASE codeigniter_db;" >> shared/database/postgres-init.sql
	@echo "\\c apm_db;" >> shared/database/postgres-init.sql
	@echo "CREATE TABLE IF NOT EXISTS users (" >> shared/database/postgres-init.sql
	@echo "  id BIGSERIAL PRIMARY KEY," >> shared/database/postgres-init.sql
	@echo "  email TEXT UNIQUE NOT NULL," >> shared/database/postgres-init.sql
	@echo "  name TEXT NOT NULL," >> shared/database/postgres-init.sql
	@echo "  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()" >> shared/database/postgres-init.sql
	@echo ");" >> shared/database/postgres-init.sql
	@echo ""


# Start supporting services only (databases, Redis)
start-services:
	@echo "Starting supporting services (databases, Redis)..."
	@docker compose up -d
	@echo "Waiting for services to be ready..."
	@sleep 15
	@echo "Supporting services started!"
	@echo "Database management interfaces (use --profile admin to enable):"
	@echo "  Adminer (DB): http://$$(grep NETWORK_INTERFACE config/deployment.env 2>/dev/null | cut -d= -f2 || echo 127.0.0.1):8090"
	@echo "  Redis Commander: http://$$(grep NETWORK_INTERFACE config/deployment.env 2>/dev/null | cut -d= -f2 || echo 127.0.0.1):8091"

# Start supporting services with admin interfaces
start-services-admin:
	@echo "Starting supporting services with admin interfaces..."
	@docker compose --profile admin up -d
	@echo "Waiting for services to be ready..."
	@sleep 15
	@echo "Supporting services and admin interfaces started!"
	@echo "Database management interfaces:"
	@echo "  Adminer (DB): http://$$(grep NETWORK_INTERFACE config/deployment.env 2>/dev/null | cut -d= -f2 || echo 127.0.0.1):8090"
	@echo "  Redis Commander: http://$$(grep NETWORK_INTERFACE config/deployment.env 2>/dev/null | cut -d= -f2 || echo 127.0.0.1):8091"

# Stop supporting services
stop-services:
	@echo "Stopping supporting services..."
	@docker compose down

# Start all applications locally
start-local:
	@echo "Starting all PHP applications locally..."
	@./start-all-local.sh

# Stop all local applications
stop-local:
	@echo "Stopping all local PHP applications..."
	@./stop-all-local.sh

# Start everything (services + applications)
start: start-services
	@echo "Starting local PHP applications..."
	@sleep 5
	@./start-all-local.sh

# Stop everything
stop: stop-local stop-services

# Restart everything
restart: stop start

# Show status of services and applications
status:
	@echo "Supporting Services Status:"
	@docker compose ps
	@echo ""
	@echo "Local Applications Status:"
	@./scripts/check-local-apps.sh

# View logs for supporting services
logs:
	@docker compose logs -f

# View logs for specific service
logs-mysql:
	@docker compose logs -f mysql

logs-postgres:
	@docker compose logs -f postgres

logs-redis:
	@docker compose logs -f redis

logs-adminer:
	@docker compose logs -f adminer

logs-redis-commander:
	@docker compose logs -f redis-commander

# Run tests for all applications
test:
	@echo "Running tests for all applications..."
	@echo "Testing Simple PHP..."
	@cd simple-php && $(MAKE) test || true
	@echo "Testing Laravel..."
	@cd laravel-app && $(MAKE) test || true
	@echo "Testing Symfony..."
	@cd symfony-app && $(MAKE) test || true
	@echo "Testing Slim Framework..."
	@cd slim-framework && $(MAKE) test || true
	@echo "Testing CodeIgniter..."
	@cd codeigniter-app && $(MAKE) test || true

# Run tests using PHP CLI
test-cli:
	@echo "Running comprehensive PHP CLI tests for all applications..."
	@echo "=========================================================="
	@./scripts/run-cli-tests.sh

# Deployment-specific commands
deploy-apache-mod-php:
	@echo "Deploying with Apache mod_php..."
	@echo "DEPLOYMENT_TYPE=apache-mod-php" > config/deployment.env.tmp
	@cat config/deployment.env >> config/deployment.env.tmp 2>/dev/null || true
	@mv config/deployment.env.tmp config/deployment.env
	@./scripts/generate-docker-compose.sh
	@$(MAKE) clean
	@$(MAKE) build-all
	@$(MAKE) start

deploy-apache-fpm:
	@echo "Deploying with Apache PHP-FPM..."
	@echo "DEPLOYMENT_TYPE=apache-fpm" > config/deployment.env.tmp
	@cat config/deployment.env >> config/deployment.env.tmp 2>/dev/null || true
	@mv config/deployment.env.tmp config/deployment.env
	@./scripts/generate-docker-compose.sh
	@$(MAKE) clean
	@$(MAKE) build-all
	@$(MAKE) start

deploy-nginx-fpm:
	@echo "Deploying with Nginx PHP-FPM..."
	@echo "DEPLOYMENT_TYPE=nginx-fpm" > config/deployment.env.tmp
	@cat config/deployment.env >> config/deployment.env.tmp 2>/dev/null || true
	@mv config/deployment.env.tmp config/deployment.env
	@./scripts/generate-docker-compose.sh
	@$(MAKE) clean
	@$(MAKE) build-all
	@$(MAKE) start

deploy-php-cli:
	@echo "Deploying with PHP CLI..."
	@echo "DEPLOYMENT_TYPE=php-cli" > config/deployment.env.tmp
	@cat config/deployment.env >> config/deployment.env.tmp 2>/dev/null || true
	@mv config/deployment.env.tmp config/deployment.env
	@./scripts/generate-docker-compose.sh
	@$(MAKE) clean
	@$(MAKE) build-all
	@$(MAKE) start

# Display all application endpoints
endpoints:
	@echo "APM PHP Applications Endpoints:"
	@echo "================================"
	@NETWORK_IP=$$(grep NETWORK_INTERFACE config/deployment.env 2>/dev/null | cut -d= -f2 || echo 127.0.0.1); \
	echo "Simple PHP:      http://$$NETWORK_IP:9080"; \
	echo "Laravel:         http://$$NETWORK_IP:9081"; \
	echo "Symfony:         http://$$NETWORK_IP:9082"; \
	echo "Slim Framework:  http://$$NETWORK_IP:9083"; \
	echo "CodeIgniter:     http://$$NETWORK_IP:9084"; \
	echo ""; \
	echo "Supporting Services:"; \
	echo "=================="; \
	echo "MySQL:           $$NETWORK_IP:3306"; \
	echo "PostgreSQL:      $$NETWORK_IP:5432"; \
	echo "MongoDB:         $$NETWORK_IP:27017"; \
	echo "Redis:           $$NETWORK_IP:6379"; \
	echo ""; \
	echo "Management Interfaces:"; \
	echo "===================="; \
	echo "Adminer (DB):    http://$$NETWORK_IP:8090"; \
	echo "Redis Commander: http://$$NETWORK_IP:8091"; \
	echo "MongoDB Express: http://$$NETWORK_IP:8092"

# Clean up containers and volumes
clean:
	@echo "Cleaning up all containers and volumes..."
	@docker compose down -v
	@docker system prune -f

# Clean up built images
clean-images:
	@echo "Removing all built images..."
	@docker compose down --rmi all

# PHP Version specific builds
compile-81:
	@echo "Building with PHP 8.1..."
	@PHP_VERSION=8.1 docker compose build

compile-82:
	@echo "Building with PHP 8.2..."
	@PHP_VERSION=8.2 docker compose build

compile-83:
	@echo "Building with PHP 8.3..."
	@PHP_VERSION=8.3 docker compose build

compile-84:
	@echo "Building with PHP 8.4..."
	@PHP_VERSION=8.4 docker compose build

# Enhanced testing and validation
test:
	@echo "🧪 Running comprehensive application tests..."
	./scripts/test-applications.sh

test-cli:
	@echo "🧪 Running CLI tests..."
	./scripts/run-cli-tests.sh

# PHP version management
php-versions:
	@echo "🐘 Available PHP versions:"
	./scripts/manage-php-version.sh list

validate-php:
	@echo "🐘 Validating PHP setup..."
	./scripts/manage-php-version.sh validate

# Enhanced application management
start-apps:
	@echo "🚀 Starting all applications..."
	./scripts/start-applications.sh start

stop-apps:
	@echo "🛑 Stopping all applications..."
	./scripts/start-applications.sh stop

restart-apps:
	@echo "🔄 Restarting all applications..."
	./scripts/start-applications.sh restart

status-apps:
	@echo "📊 Application status:"
	./scripts/start-applications.sh status

# Network Configuration Commands
network-local: ## Configure for local development (127.0.0.1)
	@echo "🔧 Configuring for local development..."
	@mkdir -p config
	@echo "NETWORK_INTERFACE=127.0.0.1" > config/network.env
	@$(MAKE) restart
	@echo "✅ Configured for local access (127.0.0.1)"

network-public: ## Configure for public access (0.0.0.0)
	@echo "🔧 Configuring for public access..."
	@mkdir -p config
	@echo "NETWORK_INTERFACE=0.0.0.0" > config/network.env
	@$(MAKE) restart
	@echo "✅ Configured for public access (0.0.0.0)"
	@echo "⚠️  Applications are now accessible from the internet!"

network-custom: ## Configure for custom IP address (use IP=x.x.x.x)
	@echo "🔧 Configuring for custom IP: $(IP)..."
	@mkdir -p config
	@echo "NETWORK_INTERFACE=$(IP)" > config/network.env
	@$(MAKE) restart
	@echo "✅ Configured for custom IP: $(IP)"

# Production Deployment Commands
prod: ## Deploy for production with Nginx
	@echo "🚀 Deploying for production..."
	@mkdir -p config
	@echo "NETWORK_INTERFACE=0.0.0.0" > config/network.env
	@docker-compose -f docker-compose.yml up -d
	@echo "✅ Production deployment complete"
	@$(MAKE) status

prod-stop: ## Stop production deployment
	@echo "🛑 Stopping production deployment..."
	@docker-compose -f docker-compose.yml down
	@echo "✅ Production deployment stopped"

# Library Management Commands
install-php: ## Install specific PHP version (use VER=x.x)
	@echo "📦 Installing PHP $(VER)..."
	@./scripts/library-manager.sh install-php $(VER)

install-lib: ## Install PHP library (use LIB=package APP=app-name)
	@echo "📚 Installing library $(LIB) for $(APP)..."
	@./scripts/library-manager.sh install-library $(LIB) $(APP)

compile-app: ## Compile application (use APP=app-name)
	@echo "🔨 Compiling application $(APP)..."
	@./scripts/library-manager.sh compile-app $(APP)

list-php: ## List available PHP versions
	@./scripts/library-manager.sh list-php

list-libraries: ## List installed libraries
	@./scripts/library-manager.sh list-libraries

check-deps: ## Check system dependencies
	@./scripts/library-manager.sh check-dependencies

set-php-version: ## Set PHP version for application (use VER=x.x APP=app-name)
	@echo "🔧 Setting PHP $(VER) for $(APP)..."
	@./scripts/library-manager.sh set-php-version $(VER) $(APP)

# Final Verification and Testing Commands
test-php-cli: ## Test all applications with PHP-CLI
	@echo "🧪 Testing applications with PHP-CLI..."
	@./scripts/final-verification.sh test-php-cli

test-apache-fpm: ## Test all applications with Apache PHP-FPM
	@echo "🌐 Testing applications with Apache PHP-FPM..."
	@./scripts/final-verification.sh test-apache-fpm

run-unit-tests: ## Run unit tests for all applications
	@echo "🧪 Running unit tests..."
	@./scripts/final-verification.sh run-unit-tests

full-verification: ## Complete verification process (PHP-CLI + Apache + Unit Tests)
	@echo "🚀 Running full verification process..."
	@./scripts/final-verification.sh full-verification

generate-report: ## Generate comprehensive test report
	@echo "📊 Generating comprehensive test report..."
	@./scripts/final-verification.sh generate-report

stop-all-apps: ## Stop all applications and services
	@echo "🛑 Stopping all applications..."
	@./scripts/final-verification.sh stop-all

# Expert-Level Testing Commands
expert-test: ## Run comprehensive expert-level testing (like a professional QA engineer)
	@echo "🎯 Running expert-level comprehensive testing..."
	@./scripts/expert-tester.sh

test-websites: ## Test all website functionality and API endpoints
	@echo "🌐 Testing website functionality..."
	@./scripts/expert-tester.sh test-websites

test-apis: ## Test all API endpoints with real requests
	@echo "🔌 Testing API endpoints..."
	@./scripts/expert-tester.sh test-apis

test-php-versions: ## Test compatibility with different PHP versions
	@echo "🔄 Testing PHP version compatibility..."
	@./scripts/expert-tester.sh test-php-versions

test-deployments: ## Test PHP-CLI, Apache mod_php, and PHP-FPM deployments
	@echo "🚀 Testing deployment methods..."
	@./scripts/expert-tester.sh test-deployments