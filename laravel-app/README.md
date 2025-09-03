# Laravel APM UI Application

A Laravel-based application monitoring and testing UI that provides endpoints for testing database connectivity, Redis operations, and external API calls.

## Requirements

- PHP >= 8.0
- Composer
- Docker and Docker Compose (for database and Redis services)
- Laravel 10.x

## Quick Start

### 1. Start Docker Services

Start the required database and Redis services using Docker Compose:

```bash
cd laravel-app
docker compose up -d
```

This will start:
- MySQL database on port 3311
- PostgreSQL database on port 5437
- Redis on port 6384

### 2. Install Dependencies

Install PHP dependencies using Composer:

```bash
composer install
```

### 3. Configure Environment

The application uses the existing `.env` file. Ensure all required environment variables are set:

- `APP_NAME` - Application name (default: "Laravel App")
- `DB_MYSQL_HOST`, `DB_MYSQL_PORT`, `DB_MYSQL_DATABASE`, `DB_MYSQL_USERNAME`, `DB_MYSQL_PASSWORD`
- `DB_PGSQL_HOST`, `DB_PGSQL_PORT`, `DB_PGSQL_DATABASE`, `DB_PGSQL_USERNAME`, `DB_PGSQL_PASSWORD`
- `REDIS_HOST`, `REDIS_PORT`
- `EXTERNAL_API_URL` - External API endpoint for testing

### 4. Generate Application Key

Generate the Laravel application key:

```bash
php artisan key:generate
```

### 5. Start the Application

Start the Laravel development server:

```bash
php artisan serve --host=0.0.0.0 --port=8081
```

Alternatively, you can use PHP's built-in server:

```bash
php -S 0.0.0.0:8081 -t public
```

### 6. Access the UI

Open your browser and navigate to:
```
http://localhost:8081
```

## Features

### Application Information
- Displays application type (Laravel)
- Shows running PHP version
- Indicates web server type (php_cli)

### External API Testing
- Tests connectivity to external APIs
- Measures response time and analyzes response structure
- Configurable timeout (20 seconds)

### Database Operations
- **Connection Check**: Tests connectivity to both MySQL and PostgreSQL databases
- **CRUD Operations**: Performs create, read, update, delete operations on both databases
- Dynamic database connections created at runtime
- Automatic table creation if tables don't exist

### Redis Queue Operations
- **Insert Bulk**: Adds 3 random messages to the queue
- **Insert Single**: Adds 1 random message to the queue
- **Read Message**: Pops one message from the queue
- **Clear Queue**: Removes all messages from the queue
- Queue naming: `{APP_NAME}_{PHP_VERSION}` (e.g., `laravel-app_8.4.1`)

## API Endpoints

All API endpoints return JSON responses with the format:
```json
{
  "ok": true/false,
  "details": { ... }
}
```

### Available Endpoints

- `GET /` - Main UI page
- `POST /api/external-call` - Test external API connectivity
- `POST /api/db-check` - Check database connections
- `POST /api/db-crud` - Perform database CRUD operations
- `POST /api/redis/insert-bulk` - Insert 3 messages to Redis queue
- `POST /api/redis/insert-single` - Insert 1 message to Redis queue
- `POST /api/redis/pop` - Read one message from Redis queue
- `POST /api/redis/clear` - Clear Redis queue

## Validation

### Running the Validator

Run the automated validator to test all functionality:

```bash
./validate.sh
```

Or run the PHP validator directly:

```bash
php validator.php
```

The validator will:
1. Test external API connectivity
2. Verify database connections and perform CRUD operations
3. Test Redis queue operations
4. Output results in JSON format
5. Save detailed results to `augment/validation_results/`

### Validator Output

The validator outputs a single-line JSON summary to stdout and saves detailed results to a timestamped file.

Example output:
```json
{
  "app": "laravel-app",
  "php_version": "8.4.12",
  "laravel_version": "10.48.29",
  "success": true,
  "duration": 2.45,
  "external_api_ok": true,
  "databases_ok": true,
  "database_crud_ok": true,
  "redis_ok": true
}
```

## Architecture

### Models
- `User` - User model with posts relationship
- `Post` - Post model belonging to users

### Controllers
- `ApmUiController` - Main controller handling all UI and API endpoints

### Database Connections
- Dynamic database connections created at runtime
- Supports both MySQL and PostgreSQL
- Automatic table creation with proper relationships

### Redis Integration
- Supports multiple Redis clients (Laravel Redis facade, Predis, PHP Redis extension)
- Automatic fallback between available clients
- Queue-based operations with unique naming

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Ensure Docker services are running: `docker compose ps`
   - Check database credentials in `.env` file
   - Verify database ports are not in use by other services

2. **Redis Connection Failed**
   - Ensure Redis service is running in Docker
   - Check Redis configuration in `.env` file
   - Verify Predis package is installed: `composer show predis/predis`

3. **External API Timeout**
   - Check internet connectivity
   - Verify `EXTERNAL_API_URL` is accessible
   - Increase timeout in `.env` if needed

4. **Permission Issues**
   - Ensure `validate.sh` is executable: `chmod +x validate.sh`
   - Check write permissions for `augment/` directory

### Logs

Application logs are stored in:
- Laravel logs: `storage/logs/`
- Validation results: `augment/validation_results/`
- Error logs: `augment/logs/`

## Development

### Adding New Tests

To add new validation tests:

1. Add new methods to `ApmUiController`
2. Create corresponding routes in `routes/web.php`
3. Update the validator in `validator.php`
4. Add UI elements in `resources/views/apm-ui.blade.php`
5. Update JavaScript in `public/js/apm-ui.js`

### Database Schema

The application automatically creates these tables if they don't exist:

**users table:**
- id (primary key)
- name (string)
- email (string, unique)
- email_verified_at (timestamp, nullable)
- password (string)
- remember_token (string, nullable)
- created_at, updated_at (timestamps)

**posts table:**
- id (primary key)
- user_id (foreign key to users)
- title (string)
- content (text)
- created_at, updated_at (timestamps)

## License

This project is part of the APM PHP examples and follows the same licensing terms.
