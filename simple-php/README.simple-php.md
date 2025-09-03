# Simple PHP UI + Backend

This directory contains a complete UI and backend implementation for the Simple PHP APM example application.

## Overview

The application provides a web interface with three main sections:
1. **Application Info** - Displays PHP version and server information
2. **External API & Database** - Test external API calls and database operations
3. **Redis Queue** - Manage Redis queue operations

## Requirements

- PHP 8.1 or higher
- Composer
- Access to MySQL, PostgreSQL, and Redis services (configured via `.env`)

## Installation

1. Install Composer dependencies:
   ```bash
   composer install --no-interaction --no-scripts
   ```

   **Note for PHP 8.1 users**: If you encounter timezone database corruption errors with Composer, use the provided wrapper script:
   ```bash
   ./composer-php81.sh install --no-interaction --no-scripts
   ```

2. Ensure your `.env` file is properly configured with database and Redis connection details.

## Starting the Server

Use the provided start script:
```bash
bash start.sh
```

This will:
- Install Composer dependencies (if not already installed)
- Start the PHP built-in server on `0.0.0.0:8080`
- Verify the server is responding

The application will be available at: http://127.0.0.1:8080/

## Environment Variables

The application expects the following environment variables in `.env`:

### Database Configuration
- `DB_MYSQL_HOST` - MySQL host (default: 127.0.0.1)
- `DB_MYSQL_PORT` - MySQL port (default: 3307)
- `DB_MYSQL_DATABASE` - MySQL database name
- `DB_MYSQL_USERNAME` - MySQL username
- `DB_MYSQL_PASSWORD` - MySQL password
- `DB_PGSQL_HOST` - PostgreSQL host (default: 127.0.0.1)
- `DB_PGSQL_PORT` - PostgreSQL port (default: 5433)
- `DB_PGSQL_DATABASE` - PostgreSQL database name
- `DB_PGSQL_USERNAME` - PostgreSQL username
- `DB_PGSQL_PASSWORD` - PostgreSQL password

### Redis Configuration
- `REDIS_HOST` - Redis host (default: 127.0.0.1)
- `REDIS_PORT` - Redis port (default: 6380)
- `REDIS_PASSWORD` - Redis password (optional)
- `REDIS_DATABASE` - Redis database number (default: 0)

### External API Configuration
- `EXTERNAL_API_URL` - URL for external API testing
- `HTTP_TIMEOUT` - HTTP request timeout in seconds (default: 20)

## Host and Port Expectations

The application expects:
- **Database hosts/ports**: As configured in `.env` file
- **Web server**: Runs on `0.0.0.0:8080` (accessible via `127.0.0.1:8080`)
- **External services**: MySQL, PostgreSQL, and Redis should be accessible on the configured hosts/ports

## API Endpoints

The application provides the following API endpoints:

- `GET /api/php-version` - Returns PHP version information
- `GET /api/config` - Returns configuration information
- `POST /api/external` - Tests external API call
- `POST /api/db/check` - Tests database connections
- `POST /api/db/crud` - Performs CRUD operations on databases
- `POST /api/redis/insert-multiple?count=N` - Inserts multiple items to Redis queue
- `POST /api/redis/insert-single` - Inserts single item to Redis queue
- `POST /api/redis/read-single` - Reads single item from Redis queue
- `POST /api/redis/clear` - Clears Redis queue

## Validation

To validate the application is working correctly:
```bash
bash validate_ui.sh
```

This script will:
- Start the server if not already running
- Test all API endpoints
- Verify database connections
- Test Redis operations
- Save validation results to `augment/validation-simple-php/`

## File Structure

```
simple-php/
├── composer.json              # Composer dependencies
├── src/
│   ├── Config.php            # Configuration management
│   ├── DB.php                # Database operations
│   └── RedisQueue.php        # Redis queue operations
├── public/
│   ├── index.php             # Main application entry point
│   └── assets/
│       ├── app.js            # Frontend JavaScript
│       └── styles.css        # CSS styles
├── start.sh                  # Server start script
├── validate_ui.sh            # Validation script
└── README.simple-php.md      # This file
```

## Troubleshooting

- **Server won't start**: Check `augment/logs/php-server.log` for errors
- **Database connection issues**: Verify `.env` configuration and ensure services are running
- **Redis connection issues**: Check Redis service status and configuration
- **Validation failures**: Check `augment/logs/validate-failure.log` for specific error details

### PHP 8.1 Specific Issues

- **Timezone database corruption error**:
  - Error: "Timezone database is corrupt" when running Composer or PHP scripts
  - Solution: Use the provided wrapper scripts:
    - For Composer: `./composer-php81.sh [arguments]`
    - For PHP: `./php-php81.sh [arguments]`
    - For validation: `./validate.sh` (automatically detects PHP 8.1)
  - Alternative: Run with timezone settings manually:
    ```bash
    TZ=UTC php -d date.timezone=UTC composer install
    TZ=UTC php -d date.timezone=UTC validator.php
    ```

## Logs

Application logs are stored in:
- `augment/logs/php-server.log` - PHP server output
- `augment/logs/simple-php-error-*.log` - Application error logs
- `augment/logs/validate-failure.log` - Validation failure details
- `augment/validation-simple-php/` - Validation test results
