# CodeIgniter APM Application

A CodeIgniter-based Application Performance Monitoring (APM) dashboard that demonstrates database connectivity, Redis queue operations, and external API calls.

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
- redis (or predis via Composer)
- curl
- openssl

### Services
- MySQL (port 3310)
- PostgreSQL (port 5436)
- Redis (port 6383)

## Installation & Setup

### 1. Start Services
```bash
# Start Docker services (MySQL, PostgreSQL, Redis)
docker compose up -d

# Wait for services to be ready
docker compose ps
```

### 2. Environment Configuration
The application uses the existing `.env` file with these key variables:

```env
APP_NAME="CodeIgniter App"
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3310
DB_MYSQL_DATABASE=codeigniter_app_db
DB_MYSQL_USERNAME=codeigniter_app_user
DB_MYSQL_PASSWORD=codeigniter_app_password

DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5436
DB_PGSQL_DATABASE=codeigniter_app_db
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=postgrespassword

REDIS_HOST=127.0.0.1
REDIS_PORT=6383
EXTERNAL_API_URL=https://httpbin.org/get
```

### 3. Run the Application

**Option 1: Using PHP Built-in Server (Recommended)**
```bash
php -S 0.0.0.0:8082 -t public
```

**Option 2: Using CodeIgniter Spark (if available)**
```bash
php spark serve --host=0.0.0.0 --port=8082
```

### 4. Access the Dashboard
Open your browser and navigate to: `http://localhost:8082`

## API Endpoints

The application provides the following REST API endpoints:

### Database Operations
- `POST /api/db/connection` - Test database connectivity
- `POST /api/db/crud` - Perform CRUD operations on both databases

### Redis Queue Operations
- `POST /api/redis/insert-batch` - Insert 3 messages to queue
- `POST /api/redis/insert-one` - Insert single message and return count
- `POST /api/redis/pop` - Pop message from queue
- `POST /api/redis/clear` - Clear entire queue

### External API
- `POST /api/external` - Test external API call

## Testing with cURL

### Database Connection Test
```bash
curl -X POST http://localhost:8082/api/db/connection
```

### Database CRUD Test
```bash
curl -X POST http://localhost:8082/api/db/crud
```

### Redis Operations
```bash
# Insert batch
curl -X POST http://localhost:8082/api/redis/insert-batch

# Insert single
curl -X POST http://localhost:8082/api/redis/insert-one

# Pop message
curl -X POST http://localhost:8082/api/redis/pop

# Clear queue
curl -X POST http://localhost:8082/api/redis/clear
```

### External API Test
```bash
curl -X POST http://localhost:8082/api/external
```

## Validation

### Automated Validation
```bash
# Run the validation script
./validate.sh

# Results are saved to augment/codeigniter/validation_results/
```

### Manual Testing
```bash
# Test individual endpoints
./tests/validate_endpoints.sh
```

## Redis Queue Naming Convention

The application uses a specific queue naming pattern:
- Format: `{APP_NAME}_{PHP_MAJOR_VERSION}.{PHP_MINOR_VERSION}`
- Example: `codeigniter_app_8.1` (for PHP 8.1)

## Architecture

### Directory Structure
```
codeigniter-app/
├── app/
│   ├── Controllers/
│   │   └── ApmController.php
│   └── Views/
│       └── apm_dashboard.php
├── public/
│   ├── index.php
│   └── assets/
│       ├── css/
│       │   └── apm-style.css
│       └── js/
│           └── apm.js
├── docker/
├── .env
├── validate.sh
├── validator.php
└── README.md
```

### Key Components

1. **ApmController**: Main controller handling all API endpoints
2. **Dashboard View**: Responsive HTML interface
3. **CSS/JS Assets**: Styling and AJAX functionality
4. **Validator**: Automated testing and validation
5. **Bootstrap**: Minimal routing and application setup

## Troubleshooting

### Common Issues

1. **Port Conflicts**: Ensure port 8082 is available
2. **Database Connection**: Verify Docker services are running
3. **Redis Extension**: Install php-redis or use Predis via Composer
4. **Permissions**: Ensure validate.sh is executable (`chmod +x validate.sh`)

### Logs and Debugging

- Validation results: `augment/codeigniter/validation_results/`
- Application logs: Check PHP error logs
- Docker logs: `docker compose logs [service_name]`

## Development Notes

- The application uses a minimal CodeIgniter 4 structure
- No full CodeIgniter installation required
- Compatible with PHP 8.0+
- Uses PDO for database operations
- Implements proper error handling and timeouts
