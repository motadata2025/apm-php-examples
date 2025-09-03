# Symfony APM Application

A comprehensive Symfony-based Application Performance Monitoring (APM) application with responsive web UI, database operations, Redis queue management, and external API integration.

## Features

- **Symfony 6.4** framework with PHP ≥ 8.0 compatibility
- **Responsive Web UI** with Bootstrap 5
- **Database Operations** (MySQL and PostgreSQL)
- **Redis Queue Management** with proper naming conventions
- **External API Integration** with timeout handling
- **Console Validation Command** for automated testing
- **AJAX-powered Dashboard** with real-time feedback

## Quick Start

### 1. Install Dependencies

```bash
# For standard PHP installations
composer install --no-interaction --prefer-dist --no-scripts

# For PHP 8.1 users (if encountering timezone errors)
./composer-php81.sh install --no-interaction --prefer-dist --no-scripts
```

**Note for PHP 8.1 users**: If you encounter timezone database corruption errors with Composer, use the provided wrapper script:
```bash
./composer-php81.sh install
```

### 2. Bootstrap Application

```bash
# Run the idempotent bootstrap script
bash bootstrap.sh
```

This script will:
- Install Composer dependencies
- Create necessary directories
- Set up Symfony kernel and console
- Generate autoloader

### 3. Start Services

```bash
# Start Docker services (MySQL, PostgreSQL, Redis)
docker compose up -d

# Wait for services to be healthy (about 30 seconds)
```

### 4. Run Application

**Option 1: Using PHP Built-in Server (Recommended)**
```bash
# Standard PHP
php -S 0.0.0.0:8084 -t public

# For PHP 8.1 users (if encountering timezone issues)
./php-php81.sh -S 0.0.0.0:8084 -t public
```

**Option 2: Using Symfony Console**
```bash
# Run validation command
php bin/console app:apm-validate

# For PHP 8.1 users
./php-php81.sh bin/console app:apm-validate
```

### 5. Access Dashboard

Open your browser and navigate to:
```
http://localhost:8084
```

## Application Structure

### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/` | GET | Main dashboard UI |
| `/api/external` | POST | External API test |
| `/api/db/check` | POST | Database connection check |
| `/api/db/crud` | POST | Database CRUD operations |
| `/api/redis/push` | POST | Insert 3 items to Redis queue |
| `/api/redis/push_one` | POST | Insert 1 item and return count |
| `/api/redis/pop` | POST | Pop message from queue |
| `/api/redis/clear` | POST | Clear entire queue |

### Database Schema

The application uses existing database tables:

**Users Table:**
- `id` (Primary Key)
- `name` (VARCHAR)
- `email` (VARCHAR, Unique)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Posts Table:**
- `id` (Primary Key)
- `user_id` (Foreign Key to users.id)
- `title` (VARCHAR)
- `content` (TEXT)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

### Redis Queue Operations

- **Queue Name Format**: `symfony-app_{PHP_VERSION}`
- **Example**: `symfony-app_8.4` for PHP 8.4
- **Behavior**: FIFO (First In, First Out)
- **Operations**: Push (LPUSH), Pop (RPOP), Clear (DEL)

## Configuration

### Environment Variables

The application reads configuration from `.env`:

```bash
# Application Settings
APP_NAME="Symfony App"
APP_ENV=dev
APP_DEBUG=true

# MySQL Database
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3308
DB_MYSQL_DATABASE=symfony_app_db
DB_MYSQL_USERNAME=symfony_app_user
DB_MYSQL_PASSWORD=symfony_app_password

# PostgreSQL Database
DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5434
DB_PGSQL_DATABASE=symfony_app_db
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=postgrespassword

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6381
REDIS_PASSWORD=
REDIS_DATABASE=0

# External API
HTTP_TIMEOUT=20
EXTERNAL_API_URL=https://httpbin.org/get
```

### Port Configuration

- **Web Application**: 8084
- **MySQL**: 3308
- **PostgreSQL**: 5434
- **Redis**: 6381

## Validation & Testing

### Automated Validation

```bash
# Run comprehensive validation
./validate.sh
```

This will test:
- External API connectivity
- MySQL and PostgreSQL connections
- Database CRUD operations
- Redis queue operations

### Manual API Testing

```bash
# Test database connections
curl -X POST http://localhost:8084/api/db/check

# Test database CRUD operations
curl -X POST http://localhost:8084/api/db/crud

# Test Redis operations
curl -X POST http://localhost:8084/api/redis/push
curl -X POST http://localhost:8084/api/redis/push_one
curl -X POST http://localhost:8084/api/redis/pop
curl -X POST http://localhost:8084/api/redis/clear

# Test external API
curl -X POST http://localhost:8084/api/external
```

### Console Command

```bash
# Run APM validation command
php bin/console app:apm-validate

# Expected output (JSON format):
{
  "app": "symfony-app",
  "php_version": "8.4.12",
  "web_server": "php_cli",
  "mysql_ok": true,
  "pgsql_ok": true,
  "redis_ok": true,
  "external_ok": true,
  "errors": [],
  "duration": 1.53,
  "timestamp": 1756935815
}
```

## Troubleshooting

### Common Issues

1. **Port Conflicts**: Ensure ports 8084, 3308, 5434, and 6381 are available
2. **Database Connection**: Verify Docker services are running with `docker compose ps`
3. **Composer Dependencies**: Run `composer install` if vendor/ is missing
4. **Permissions**: Ensure bootstrap.sh and validate.sh are executable

### PHP 8.1 Timezone Database Corruption

If you encounter the error "Timezone database is corrupt" when running Composer or PHP scripts:

**Error Message:**
```
PHP Fatal error: Uncaught Error: Timezone database is corrupt. Please file a bug report as this should never happen in phar:///usr/local/bin/composer/src/Composer/Util/Silencer.php:67
```

**Solution: Use the provided wrapper scripts:**

```bash
# For Composer operations
./composer-php81.sh install
./composer-php81.sh update
./composer-php81.sh require package/name

# For PHP operations
./php-php81.sh -S 0.0.0.0:8084 -t public
./php-php81.sh bin/console app:apm-validate

# For validation
./validate.sh  # (automatically uses wrapper if needed)
```

**Manual Fix (Alternative):**
```bash
# Set timezone environment and run commands
TZ=UTC php -d date.timezone=UTC composer install
TZ=UTC php -d date.timezone=UTC -S 0.0.0.0:8084 -t public
```

**Test Timezone Fix:**
```bash
# Verify timezone fix is working
php fix-timezone.php
```

## Development

### File Structure

```
symfony-app/
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml      # Database configuration
│   │   └── cache.yaml         # Redis configuration
│   └── routes.yaml            # Application routes
├── src/
│   ├── Controller/
│   │   └── UiController.php   # Main API controller
│   └── Command/
│       └── ApmValidateCommand.php  # Console command
├── templates/
│   └── index.html.twig        # Main UI template
├── public/
│   ├── index.php              # Front controller
│   └── css/
│       └── style.css          # Responsive CSS
├── bootstrap.sh               # Setup script
├── validate.sh                # Validation wrapper
├── composer-php81.sh          # PHP 8.1 Composer wrapper
├── php-php81.sh              # PHP 8.1 wrapper
└── fix-timezone.php          # Timezone fix test
```

### Dependencies

- **symfony/framework-bundle**: Core Symfony framework
- **symfony/console**: Console commands
- **symfony/http-client**: HTTP client for external APIs
- **symfony/twig-bundle**: Template engine
- **doctrine/doctrine-bundle**: Database abstraction
- **predis/predis**: Redis client
- **fakerphp/faker**: Test data generation

## License

This project is part of the APM PHP Examples repository and follows the same licensing terms.

## Support

For issues related to:
- **PHP 8.1 timezone errors**: Use the provided wrapper scripts
- **Database connections**: Check Docker service status
- **Redis operations**: Verify Redis container is running
- **External API**: Check network connectivity and firewall settings
