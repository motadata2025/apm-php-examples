# APM PHP Examples

A comprehensive collection of production-ready PHP applications demonstrating Application Performance Monitoring (APM) integration with multiple deployment options and scaling capabilities.

## Project Structure

```
apm-php-examples/
├── Dockerfile                 # Parent-level Docker configuration
├── docker-compose.yml         # Multi-service Docker setup
├── Makefile                   # Global automation commands
├── simple-php/               # Vanilla PHP application
├── laravel-app/              # Laravel framework application
├── symfony-app/              # Symfony framework application
├── slim-framework/           # Slim framework application
├── codeigniter-app/          # CodeIgniter framework application
├── shared/                   # Shared utilities and configurations
└── docs/                     # Additional documentation
```

## Features

Each application includes:

- **Responsive UI** displaying PHP version and framework information
- **Multi-database operations** (MySQL, PostgreSQL)
- **External API integrations** (JSONPlaceholder, JokeAPI)
- **Queue system operations** (Redis/RabbitMQ)
- **Comprehensive error handling** and logging
- **Unit testing suite**
- **Docker containerization**

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Make (for automation commands)
- Git

### Setup All Applications

```bash
# Clone the repository
git clone <repository-url>
cd apm-php-examples

# Setup infrastructure and build all applications
make setup

# Start all services
make start

# View running endpoints
make endpoints
```

### Setup Individual Applications

```bash
# Setup specific application
cd laravel-app
make setup
make start
```

## Available Commands

### Global Commands (from root directory)

- `make setup` - Sets up Docker infrastructure for all apps
- `make start` - Starts all applications and services
- `make stop` - Stops all applications and services
- `make clean` - Removes all containers and volumes
- `make test` - Runs tests for all applications
- `make endpoints` - Displays all running application endpoints

### Application-Specific Commands

Each application directory supports:

- `make setup` - Setup application dependencies
- `make start` - Start the application
- `make stop` - Stop the application
- `make test` - Run application tests
- `make logs` - View application logs

## PHP Versions Supported

- PHP 8.1
- PHP 8.2
- PHP 8.3
- PHP 8.4

## Services Included

- **MySQL 8.0** - Primary SQL database
- **PostgreSQL 15** - Secondary SQL database

- **Redis 7.0** - Caching and queue system
- **RabbitMQ 3.11** - Message queue system

## Applications

### 1. Simple PHP (`simple-php/`)
Vanilla PHP application demonstrating basic APM integration without frameworks.

### 2. Laravel (`laravel-app/`)
Laravel application with Eloquent ORM, Guzzle HTTP client, and Laravel Queue.

### 3. Symfony (`symfony-app/`)
Symfony application with Doctrine ORM, Symfony HTTP Client, and Messenger component.

### 4. Slim Framework (`slim-framework/`)
Lightweight Slim application with database abstraction and queue operations.

### 5. CodeIgniter (`codeigniter-app/`)
CodeIgniter application with Active Record and external API integration.

## Database Schema

All applications use a consistent user schema:

```sql
CREATE TABLE users (
  id BIGSERIAL PRIMARY KEY,
  email TEXT UNIQUE NOT NULL,
  name TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

## External APIs

- **JSONPlaceholder**: `https://jsonplaceholder.typicode.com/posts`
- **JokeAPI**: `https://sv443.net/jokeapi/v2/joke/Any`

## Testing

Run comprehensive tests:

```bash
# All applications
make test

# Specific application
cd laravel-app && make test
```

## Current Implementation Status

### ✅ Completed Components

1. **Project Structure** - Complete directory structure with all application folders
2. **Shared Utilities** - Database connections, User models, API clients, Queue management
3. **Database Schema** - MySQL and PostgreSQL initialization scripts
4. **Simple PHP Application** - Fully functional vanilla PHP application with:
   - Responsive UI with PHP version display
   - Multi-database operations (MySQL, PostgreSQL)
   - External API integrations (JSONPlaceholder, JokeAPI)
   - Redis queue operations
   - Docker configuration
   - Unit tests
   - Makefile automation

5. **Laravel Application** - Complete Laravel framework implementation with:
   - **MVC Architecture** - Controllers, Models, Views following Laravel conventions
   - **Eloquent ORM** - Database operations using Laravel's ORM alongside shared utilities
   - **Blade Templates** - Responsive UI with Laravel's templating engine
   - **Laravel HTTP Client** - External API calls using Laravel's built-in HTTP client
   - **Laravel Queue System** - Job processing with Redis backend
   - **Route Management** - RESTful routes with named route support
   - **CSRF Protection** - Built-in security features
   - **Environment Configuration** - Proper .env setup for multi-database connections
   - **Feature Tests** - Comprehensive test suite using Laravel's testing framework
   - **Artisan Commands** - Laravel CLI integration
   - **Docker Configuration** - Optimized for Laravel with Apache virtual host
   - **Makefile Automation** - Laravel-specific commands including migrations

6. **Symfony Application** - Complete Symfony framework implementation with:
   - **MVC Architecture** - Controllers, Entities, Templates following Symfony conventions
   - **Doctrine ORM** - Database operations using Doctrine alongside shared utilities
   - **Twig Templates** - Responsive UI with Symfony's templating engine
   - **Symfony HTTP Client** - External API calls using Symfony's HTTP client component
   - **Symfony Messenger** - Message handling with Redis transport
   - **Route Annotations** - Modern PHP 8+ attribute-based routing
   - **Dependency Injection** - Symfony's powerful DI container
   - **Environment Configuration** - Flexible .env configuration system
   - **Web Test Cases** - Comprehensive functional testing with Symfony's test framework
   - **Console Commands** - Symfony Console component integration
   - **Docker Configuration** - Optimized for Symfony with Apache and OPcache
   - **Makefile Automation** - Symfony-specific commands including cache management

7. **Slim Framework Application** - Complete Slim microframework implementation with:
   - **Lightweight Architecture** - Minimal, fast microframework approach
   - **PSR-7 HTTP Messages** - Modern HTTP request/response handling
   - **Twig Templates** - Responsive UI with Twig templating engine
   - **Guzzle HTTP Client** - External API calls using Guzzle HTTP client
   - **Dependency Injection** - PHP-DI container for service management
   - **Middleware Support** - Extensible middleware pipeline
   - **Environment Configuration** - DotEnv-based configuration management
   - **Monolog Logging** - Comprehensive logging with Monolog
   - **PHPUnit Testing** - Unit and integration testing framework
   - **Docker Configuration** - Optimized for Slim with Apache URL rewriting
   - **Makefile Automation** - Slim-specific commands including development server

8. **CodeIgniter Application** - Complete CodeIgniter framework implementation with:
   - **MVC Architecture** - Controllers, Models, Views following CodeIgniter conventions
   - **Active Record Pattern** - Database operations using CodeIgniter's Active Record
   - **PHP Views** - Responsive UI with native PHP templating
   - **Multiple HTTP Clients** - Both Guzzle and CodeIgniter's HTTP client
   - **Environment Configuration** - CodeIgniter's .env configuration system
   - **Spark CLI** - CodeIgniter's command-line interface
   - **Route Groups** - Organized routing with CodeIgniter's router
   - **Built-in Logging** - CodeIgniter's logging system
   - **CIUnitTestCase** - CodeIgniter-specific testing framework
   - **Docker Configuration** - Optimized for CodeIgniter with proper permissions
   - **Makefile Automation** - CodeIgniter-specific commands including Spark CLI

### ✅ **Project Complete!**

All five PHP applications have been successfully implemented, demonstrating comprehensive APM integration patterns across different PHP frameworks and approaches.

## Testing the Current Setup

### Quick Test

```bash
# Setup the infrastructure
make setup

# Start all services
make start

# Check if Simple PHP is running
curl http://localhost:8081

# View endpoints
make endpoints
```

### Manual Testing

1. **Simple PHP Application**: http://localhost:8081
   - Test database connections
   - Try CRUD operations
   - Fetch external API data
   - Test queue operations

2. **Laravel Application**: http://localhost:8082
   - Laravel-specific UI with Blade templates
   - Eloquent ORM demonstrations
   - Laravel HTTP client for external APIs
   - Laravel Queue system with job processing
   - Health check endpoint: http://localhost:8082/health

3. **Symfony Application**: http://localhost:8083
   - Symfony-specific UI with Twig templates
   - Doctrine ORM demonstrations
   - Symfony HTTP client for external APIs
   - Symfony Messenger for message handling
   - Health check endpoint: http://localhost:8083/health

4. **Slim Framework Application**: http://localhost:8084
   - Slim microframework UI with Twig templates
   - PDO database operations demonstrations
   - Guzzle HTTP client for external APIs
   - Redis queue operations with shared utilities
   - Health check endpoint: http://localhost:8084/health

5. **CodeIgniter Application**: http://localhost:8085
   - CodeIgniter framework UI with PHP views
   - Active Record database operations demonstrations
   - Multiple HTTP clients (Guzzle + CodeIgniter)
   - Redis queue operations with shared utilities
   - Health check endpoint: http://localhost:8085/health

6. **Database Services**:
   - MySQL: localhost:3306
   - PostgreSQL: localhost:5432


4. **Queue Services**:
   - Redis: localhost:6379
   - RabbitMQ: localhost:5672
   - RabbitMQ Management: http://localhost:15672

## Architecture Overview

The project follows a modular architecture:

- **Shared utilities** (`shared/`) provide common functionality across all applications
- **Individual applications** implement framework-specific patterns while using shared utilities
- **Docker containers** ensure consistent environments across different PHP versions
- **Database initialization** scripts set up consistent schemas across all databases
- **Queue systems** demonstrate both Redis and RabbitMQ integration

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

MIT License - see LICENSE file for details.