# Slim Framework APM Application

A Slim Framework-based Application Performance Monitoring (APM) dashboard that demonstrates database connectivity, Redis queue operations, and external API calls.

## Features

- **Responsive Web Dashboard**: Clean, modern UI with three main blocks
- **Database Operations**: MySQL and PostgreSQL connectivity and CRUD operations
- **Redis Queue Management**: Insert, pop, and clear operations with proper queue naming
- **External API Testing**: HTTP calls with timeout handling
- **Real-time Feedback**: Toast notifications and result displays

## Requirements

### PHP Extensions
- PHP >= 8.0
- pdo_mysql
- pdo_pgsql
- curl
- json
- mbstring

### Services
- MySQL (port 3309)
- PostgreSQL (port 5435)
- Redis (port 6382)

## Installation & Setup

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

### 2. Start Services
```bash
# Start Docker services (MySQL, PostgreSQL, Redis)
docker compose up -d

# Wait for services to be ready
docker compose ps
```

### 3. Environment Configuration
The application uses the existing `.env` file with these key variables:

```env
APP_NAME="Slim Framework App"
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3309
DB_MYSQL_DATABASE=slim_framework_db
DB_MYSQL_USERNAME=slim_framework_user
DB_MYSQL_PASSWORD=slim_framework_password

DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5435
DB_PGSQL_DATABASE=slim_framework_db
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=postgrespassword

REDIS_HOST=127.0.0.1
REDIS_PORT=6382
EXTERNAL_API_URL=https://httpbin.org/get
```

### 4. Run the Application

**Option 1: Using PHP Built-in Server (Recommended)**
```bash
# Standard PHP
php -S 0.0.0.0:8083 -t public

# For PHP 8.1 users (if encountering timezone issues)
./php-php81.sh -S 0.0.0.0:8083 -t public
```

**Option 2: Using Composer Script**
```bash
composer start
```

**Option 3: Direct PHP execution**
```bash
php public/index.php
```

### 5. Access the Dashboard
Open your browser and navigate to: `http://localhost:8083`

## API Endpoints

The application provides the following REST API endpoints:

### Database Operations
- `POST /api/db/check` - Test database connectivity
- `POST /api/db/crud` - Perform CRUD operations on both databases

### Redis Queue Operations
- `POST /api/redis/insert_bulk` - Insert 3 messages to queue
- `POST /api/redis/insert_single` - Insert single message and return count
- `POST /api/redis/read_single` - Pop message from queue
- `POST /api/redis/clear` - Clear entire queue

### External API
- `POST /api/external` - Test external API call

## Testing with cURL

### Database Connection Test
```bash
curl -X POST http://localhost:8083/api/db/check
```

### Database CRUD Test
```bash
curl -X POST http://localhost:8083/api/db/crud
```

### Redis Operations
```bash
# Insert bulk
curl -X POST http://localhost:8083/api/redis/insert_bulk

# Insert single
curl -X POST http://localhost:8083/api/redis/insert_single

# Read single
curl -X POST http://localhost:8083/api/redis/read_single

# Clear queue
curl -X POST http://localhost:8083/api/redis/clear
```

### External API Test
```bash
curl -X POST http://localhost:8083/api/external
```

## Validation

### Automated Validation
```bash
# Run the validation script
./validate.sh
```

### Manual Testing
See `tests/manual_checks.md` for a comprehensive manual testing checklist.

## Redis Queue Naming Convention

The application uses a specific queue naming pattern:
- Format: `slim-framework_{PHP_VERSION}` with dots replaced by underscores
- Example: `slim-framework_8_1_33` (for PHP 8.1.33)

## Architecture

### Directory Structure
```
slim-framework/
├── src/
│   ├── AppConfig.php
│   └── Controllers/
│       └── ApiController.php
├── public/
│   ├── index.php
│   └── assets/
│       ├── main.css
│       └── main.js
├── templates/
├── tests/
│   └── manual_checks.md
├── docker/
├── .env
├── composer.json
├── validate.sh
├── validator.php
└── README.md
```

### Key Components

1. **AppConfig**: Configuration helper that reads .env and provides service settings
2. **ApiController**: Main controller handling all API endpoints
3. **Bootstrap**: Slim application setup with routing in public/index.php
4. **Frontend**: Responsive HTML interface with Bootstrap 5 and custom JavaScript
5. **Validator**: Automated testing and validation

## Troubleshooting

### Common Issues

1. **Port Conflicts**: Ensure port 8083 is available
2. **Database Connection**: Verify Docker services are running
3. **Composer Dependencies**: Run `composer install` if vendor/ is missing
4. **Permissions**: Ensure validate.sh is executable (`chmod +x validate.sh`)
5. **PHP 8.1 Timezone Database Corruption**:
   - Error: "Timezone database is corrupt" when running Composer or PHP scripts
   - Solution: Use the provided wrapper scripts:
     - For Composer: `./composer-php81.sh [arguments]`
     - For PHP: `./php-php81.sh [arguments]`
   - Alternative: Run with timezone settings manually:
     ```bash
     TZ=UTC php -d date.timezone=UTC composer install
     TZ=UTC php -d date.timezone=UTC -S 0.0.0.0:8083 -t public
     ```

### Logs and Debugging

- Application errors: Check PHP error logs
- Docker logs: `docker compose logs [service_name]`
- Validation results: Check validator output

## Development Notes

- The application uses Slim Framework 4 with PSR-7 standards
- Database operations use PDO with prepared statements
- Redis operations use Predis client library
- Frontend uses Bootstrap 5 for responsive design
- All API responses follow consistent JSON structure: `{ok: boolean, payload: object}` or `{ok: false, error: object}`

## Dependencies

- **slim/slim**: Slim Framework 4
- **slim/psr7**: PSR-7 implementation
- **vlucas/phpdotenv**: Environment variable loader
- **predis/predis**: Redis client library
- **symfony/var-dumper**: Development debugging (dev dependency)
