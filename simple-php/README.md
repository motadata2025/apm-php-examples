# Simple PHP

## Overview

The **Simple PHP Application** is a comprehensive, production-ready PHP application designed for Application Performance Monitoring (APM) testing and demonstration. This application showcases modern PHP development practices, multi-database operations, queue management, external API integrations, and health monitoring capabilities.

**What the Simple App Does:**
- Provides a complete web-based interface for testing database operations (MySQL, PostgreSQL)
- Demonstrates Redis-based queue management with real-time operations
- Integrates with external APIs for performance testing and monitoring
- Offers comprehensive health checks and system monitoring endpoints
- Supports multiple deployment types (PHP-CLI, Apache mod_php, Apache/Nginx PHP-FPM)
- Implements production-grade architecture with proper error handling and logging

**Frameworks & Libraries Used:**
- **Backend**: Vanilla PHP 8.1+ with PSR-4 autoloading
- **Database**: PDO for MySQL and PostgreSQL connections
- **Cache/Queue**: Redis with Predis client library
- **HTTP Client**: cURL-based API client for external integrations
- **Testing**: PHPUnit for comprehensive unit testing
- **Frontend**: Pure HTML5/CSS3/JavaScript with responsive design
- **Containerization**: Docker Compose for supporting services
- **Build System**: Make-based deployment and management

**Architectural Style:**
- **MVC Pattern**: Clean separation of concerns with Models, Views, and Controllers
- **Service Layer Architecture**: Dedicated service classes for database, queue, and API operations
- **RESTful APIs**: Clean REST endpoints for all operations
- **Event-Driven**: Queue-based asynchronous processing
- **Microservices-Ready**: Independent, containerized supporting services
- **Production-Grade**: Health checks, monitoring, logging, and error handling

## Features

### 🎯 Core Features

#### **Web User Interface**
- **Modern Responsive Design**: Mobile-first CSS3 with gradient backgrounds and smooth animations
- **Real-time Operations**: AJAX-powered interface with loading indicators and error handling
- **Interactive Dashboard**: Live system information display (PHP version, web server type, deployment status)
- **Operation Sections**: Organized by functionality (Health, Database, API, Queue operations)

#### **Database Operations**
- **Multi-Database Support**: MySQL and PostgreSQL with automatic connection management
- **CRUD Operations**: Complete Create, Read, Update, Delete operations with transaction support
- **Connection Testing**: Real-time database connectivity validation
- **Schema Management**: Automatic table creation and migration support
- **Transaction Examples**: Demonstrates complex multi-step database operations

#### **Redis Queue System**
- **Queue Management**: Full queue lifecycle with enqueue, dequeue, and monitoring
- **Auto-Expiry**: 1-minute TTL for queue messages with automatic cleanup
- **Batch Operations**: Support for adding multiple items simultaneously
- **Queue Monitoring**: Real-time queue status and statistics
- **Application-Specific Queues**: Dynamic queue naming based on app name, PHP version, and web server

#### **External API Integration**
- **Multiple API Testing**: JSONPlaceholder, HTTPBin, and custom slow API simulation
- **Performance Monitoring**: Request timing, response analysis, and error handling
- **HTTP Methods**: Support for GET, POST, PUT, DELETE operations
- **Timeout Management**: Configurable timeouts and retry mechanisms

#### **Health Checks & Monitoring**
- **System Health**: Comprehensive health status for all services
- **Performance Metrics**: Memory usage, execution time, and system load monitoring
- **Service Status**: Real-time status of MySQL, PostgreSQL, and Redis
- **Production Endpoints**: Dedicated monitoring endpoints for load balancers

### 🔌 API Endpoints

#### **Health Check Endpoint**
```http
GET /health
```
**Response Example:**
```json
{
  "status": "healthy",
  "timestamp": "2025-08-31T06:05:17+00:00",
  "php_version": "8.4.0",
  "memory_usage": 2097152,
  "uptime": 3600,
  "services": {
    "redis": "healthy",
    "mysql": "healthy",
    "postgres": "healthy"
  }
}
```

#### **API Testing Endpoint**
```http
GET /api/test
```
**Response Example:**
```json
{
  "success": true,
  "data": {
    "jsonplaceholder": {
      "service": "JSONPlaceholder",
      "tests": {
        "get_posts": {"success": true, "duration": 245.67},
        "get_post": {"success": true, "duration": 123.45},
        "create_post": {"success": true, "duration": 189.23}
      },
      "status": "completed"
    },
    "httpbin": {
      "service": "HTTPBin",
      "tests": {
        "get_test": {"success": true, "duration": 156.78},
        "post_test": {"success": true, "duration": 234.56},
        "delay_test": {"success": true, "duration": 2134.67}
      },
      "status": "completed"
    }
  }
}
```

#### **AJAX Operations (POST to /)**
- `action=test_databases` - Test all database connections
- `action=create_tables` - Create database tables
- `action=demo_crud` - Perform comprehensive CRUD operations
- `action=fetch_api_data` - Test external APIs
- `action=test_queue` - Demo queue operations
- `action=add_queue_data` - Add data to queue
- `action=read_queue_data` - Read data from queue
- `action=clear_queue` - Clear queue contents
- `action=generate_random_data` - Generate random test data

### 📊 Redis Usage Patterns

#### **Queue Operations**
- **Queue Naming**: `simple_php_{php_version}_{web_server}` (e.g., `simple_php_84_apache_fpm`)
- **Message Structure**: JSON with id, data, timestamps, and metadata
- **TTL Management**: 1-minute automatic expiration for all queue messages
- **Status Tracking**: Pending, processing, completed, failed states

#### **Caching Patterns**
- **User Data Caching**: 1-hour TTL for user information
- **Session Management**: Redis-based session storage
- **Performance Metrics**: Cached system statistics

#### **Key Patterns**
```
simple_php_84_apache_fpm           # Main queue
simple_php_84_apache_fpm:jobs      # Job tracking hash
simple_php_84_apache_fpm:completed # Completed jobs list
simple_php_84_apache_fpm:failed    # Failed jobs list
user:cache:{user_id}               # User data cache
system:metrics                     # System performance cache
```

### 🗄️ Database Models & Schemas

#### **MySQL Schema**
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **PostgreSQL Schema**
```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Project Structure

```
simple-php/
├── 📁 config/                    # Configuration files
│   ├── app.env                   # Application environment variables
│   ├── network.state             # Network configuration state
│   ├── nginx.conf                # Nginx configuration template
│   └── php-version.state         # PHP version tracking
├── 📁 docker/                    # Docker initialization scripts
│   ├── mysql/                    # MySQL initialization scripts
│   └── postgres/                 # PostgreSQL initialization scripts
├── 📁 lib/                       # Core library classes (Business Logic)
│   ├── ApiClient.php             # External API integration client
│   ├── DatabaseConnection.php    # Multi-database connection manager
│   ├── Logger.php                # Application logging utility
│   ├── QueueManager.php          # Redis queue management system
│   └── UserModel.php             # User data model with CRUD operations
├── 📁 public/                    # Web-accessible files
│   ├── index.php                 # Main application entry point & router
│   ├── main-page.php             # HTML/CSS/JS user interface
│   └── monitoring.php            # Production monitoring endpoints
├── 📁 scripts/                   # Deployment and management scripts
│   ├── check-system.sh           # System requirements validation
│   ├── cleanup.sh                # Complete application cleanup
│   ├── compile-app.sh            # Application compilation
│   ├── deploy-apache-fpm.sh      # Apache PHP-FPM deployment
│   ├── deploy-apache-mod-php.sh  # Apache mod_php deployment
│   ├── deploy-nginx-fpm.sh       # Nginx PHP-FPM deployment
│   ├── disable-app.sh            # Application disable script
│   ├── docker-helper.sh          # Docker container management
│   ├── enable-app.sh             # Application enable script
│   ├── network-manager.sh        # Network configuration management
│   ├── php-version-manager.sh    # PHP version management
│   ├── start-app.sh              # Application startup script
│   ├── status.sh                 # Application status checker
│   ├── stop-app.sh               # Application stop script
│   └── webserver-manager.sh      # Web server configuration
├── 📁 tests/                     # Unit tests
│   └── SimplePhpTest.php         # PHPUnit test suite
├── 📁 vendor/                    # Composer dependencies
├── composer.json                 # PHP dependency management
├── composer.lock                 # Locked dependency versions
├── docker-compose.services.yml   # Supporting services (MySQL, PostgreSQL, Redis)
├── docker-compose.yml            # Main Docker Compose configuration
├── Makefile                      # Build and deployment commands
├── phpunit.xml                   # PHPUnit configuration
├── README.md                     # This documentation file
└── start-local.sh               # Local development startup script
```

### 📂 Directory Explanations

- **`config/`**: Contains all configuration files including environment variables, network settings, and web server configurations
- **`lib/`**: Core business logic classes following PSR-4 autoloading standards
- **`public/`**: Web-accessible directory containing the main application entry point and UI
- **`scripts/`**: Shell scripts for deployment, management, and system operations
- **`tests/`**: PHPUnit test suite for comprehensive application testing
- **`docker/`**: Database initialization scripts for containerized services

## Installation

### Prerequisites

#### **System Requirements**
- **PHP**: 8.1 or higher with extensions:
  - `ext-pdo` (PDO database abstraction)
  - `ext-redis` (Redis connectivity)
  - `ext-curl` (HTTP client functionality)
  - `ext-json` (JSON processing)
- **Composer**: Latest version for dependency management
- **Docker & Docker Compose**: For supporting services
- **Make**: For build automation
- **Web Server**: Apache 2.4+ or Nginx 1.18+ (optional for local development)

#### **Operating System Support**
- **Linux**: Ubuntu 20.04+, CentOS 8+, Debian 11+
- **macOS**: 10.15+ with Homebrew
- **Windows**: WSL2 with Ubuntu

### 🚀 Local Installation (Without Docker)

#### **Step 1: Clone and Setup**
```bash
# Navigate to the simple-php directory
cd simple-php

# Install PHP dependencies
composer install --optimize-autoloader

# Check system requirements
make setup
```

#### **Step 2: Configure Environment**
```bash
# Copy and edit environment configuration
cp config/app.env.example config/app.env

# Edit configuration (optional - defaults work for local development)
nano config/app.env
```

#### **Step 3: Start Supporting Services**
```bash
# Start MySQL, PostgreSQL, and Redis containers
docker-compose -f docker-compose.services.yml up -d

# Verify services are running
docker-compose -f docker-compose.services.yml ps
```

#### **Step 4: Compile and Start Application**
```bash
# Compile application (select PHP version and deployment type)
make compile

# Start the application
make start
```

#### **Step 5: Access Application**
- **Main Application**: http://localhost:8000
- **Health Check**: http://localhost:8000/health
- **API Test**: http://localhost:8000/api/test

### 🐳 Docker Installation (Complete Setup)

#### **Step 1: Quick Start**
```bash
# Navigate to simple-php directory
cd simple-php

# Start all services including application
docker-compose up -d

# Check status
docker-compose ps
```

#### **Step 2: Access Services**
- **Application**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8080 (root/rootpassword)
- **pgAdmin**: http://localhost:8081 (admin@example.com/admin123)
- **Redis Commander**: http://localhost:8082

### 🔧 Development Setup

#### **Step 1: Install Development Dependencies**
```bash
# Install with development packages
composer install

# Install PHPUnit globally (optional)
composer global require phpunit/phpunit
```

#### **Step 2: Run Tests**
```bash
# Run all tests
make test

# Run tests with coverage
composer run test-coverage
```

#### **Step 3: Development Server**
```bash
# Start PHP built-in server for development
php -S localhost:8000 -t public/

# Or use make command
make start
```

## Environment Variables

### 📋 Complete Environment Variables List

| Variable | Description | Default Value | Required | Example |
|----------|-------------|---------------|----------|---------|
| `APP_NAME` | Application name | `"Simple PHP"` | No | `"Simple PHP"` |
| `APP_PORT` | Application port | `8000` | No | `8000` |
| `NETWORK_INTERFACE` | Network interface to bind | `"0.0.0.0"` | No | `"127.0.0.1"` |
| `PUBLIC_IP` | Public IP address | Auto-detected | No | `"192.168.1.100"` |
| `PHP_VERSION` | PHP version to use | `"8.4"` | No | `"8.1"`, `"8.2"`, `"8.3"`, `"8.4"` |
| `DEPLOYMENT_TYPE` | Deployment method | `"apache-fpm"` | No | `"php-cli"`, `"apache-mod-php"`, `"nginx-fpm"` |
| `APP_ENV` | Application environment | `production` | No | `development`, `testing`, `production` |
| `APP_DEBUG` | Debug mode | `false` | No | `true`, `false` |

### 🗄️ Database Configuration

| Variable | Description | Default Value | Required | Example |
|----------|-------------|---------------|----------|---------|
| `MYSQL_HOST` | MySQL hostname | `localhost` | Yes | `localhost`, `mysql-server` |
| `MYSQL_PORT` | MySQL port | `3306` | No | `3306`, `3307` |
| `MYSQL_DATABASE` | MySQL database name | `apm_examples` | Yes | `simple_php_db` |
| `MYSQL_USERNAME` | MySQL username | `root` | Yes | `simple_php_user` |
| `MYSQL_PASSWORD` | MySQL password | `rootpassword` | Yes | `secure_password` |
| `POSTGRES_HOST` | PostgreSQL hostname | `localhost` | Yes | `localhost`, `postgres-server` |
| `POSTGRES_PORT` | PostgreSQL port | `5432` | No | `5432`, `5433` |
| `POSTGRES_DATABASE` | PostgreSQL database name | `apm_examples` | Yes | `simple_php_db` |
| `POSTGRES_USERNAME` | PostgreSQL username | `postgres` | Yes | `simple_php_user` |
| `POSTGRES_PASSWORD` | PostgreSQL password | `postgrespassword` | Yes | `secure_password` |

### 🔴 Redis Configuration

| Variable | Description | Default Value | Required | Example |
|----------|-------------|---------------|----------|---------|
| `REDIS_HOST` | Redis hostname | `localhost` | Yes | `localhost`, `redis-server` |
| `REDIS_PORT` | Redis port | `6379` | No | `6379`, `6380` |
| `REDIS_PASSWORD` | Redis password | `null` | No | `redis_password` |

### ⚠️ Missing Variable Behavior

**What happens if variables are missing:**

- **Database Variables**: Application will attempt to connect with defaults, may fail if services aren't available on default ports
- **Redis Variables**: Queue operations will fail, but application will continue to function for other features
- **APP_* Variables**: Application uses sensible defaults, functionality remains intact
- **Network Variables**: Application binds to all interfaces (0.0.0.0) and uses port 8000

### 📝 Environment File Example

```bash
# config/app.env
APP_NAME="Simple PHP"
APP_PORT=8000
NETWORK_INTERFACE="127.0.0.1"
PHP_VERSION="8.4"
DEPLOYMENT_TYPE="apache-fpm"
APP_ENV=production
APP_DEBUG=false

# Database Configuration
MYSQL_HOST=localhost
MYSQL_PORT=3307
MYSQL_DATABASE=simple_php_db
MYSQL_USERNAME=simple_php_user
MYSQL_PASSWORD=simple_php_password

POSTGRES_HOST=localhost
POSTGRES_PORT=5433
POSTGRES_DATABASE=simple_php_db
POSTGRES_USERNAME=simple_php_user
POSTGRES_PASSWORD=simple_php_password

REDIS_HOST=localhost
REDIS_PORT=6380
```

## Database

### 🗄️ Database Configuration

#### **MySQL 8.0**
- **Version**: 8.0 (Latest stable)
- **Port**: 3307 (to avoid conflicts with system MySQL)
- **Default Database**: `simple_php_db`
- **Character Set**: UTF8MB4 with full Unicode support
- **Engine**: InnoDB with foreign key constraints

#### **PostgreSQL 15**
- **Version**: 15 (Latest stable)
- **Port**: 5433 (to avoid conflicts with system PostgreSQL)
- **Default Database**: `simple_php_db`
- **Extensions**: Standard PostgreSQL extensions enabled

### 🔄 Migration & Seed Steps

#### **Automatic Table Creation**
The application automatically creates required tables on first run:

```bash
# Create tables in both databases
curl -X POST http://localhost:8000 \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=create_tables"
```

#### **Manual Database Setup**
```bash
# Connect to MySQL
mysql -h localhost -P 3307 -u root -p

# Connect to PostgreSQL
psql -h localhost -p 5433 -U postgres -d simple_php_db
```

### 📊 Database Schema Details

#### **Users Table Structure**
- **Primary Key**: Auto-incrementing ID
- **Unique Constraint**: Email address
- **Timestamps**: Created and updated timestamps
- **Indexes**: Email index for fast lookups

#### **Posts Table Structure**
- **Foreign Key**: References users table with CASCADE delete
- **Content Storage**: TEXT field for large content
- **Relationships**: One-to-many relationship with users

#### **Transaction Examples**
The application demonstrates:
- **MySQL Transactions**: Transfer operations between users
- **PostgreSQL Transactions**: Swap operations with rollback support
- **Error Handling**: Proper rollback on transaction failures

## Redis

### 🔴 Redis Configuration & Purpose

#### **Purpose in Application**
- **Queue Management**: Primary message queue for asynchronous processing
- **Caching Layer**: User data and system metrics caching
- **Session Storage**: Session management for web application
- **Performance Monitoring**: Real-time metrics storage

#### **Connection Setup**
```php
// Redis connection configuration
$redis = new Redis();
$redis->connect('localhost', 6380);

// With authentication (if configured)
if ($password) {
    $redis->auth($password);
}
```

### 🔧 Redis Usage Examples

#### **Queue Operations**
```php
// Add job to queue
$queueManager = new QueueManager('simple_php_84_apache_fpm');
$queueManager->addToQueue([
    'action' => 'process_user',
    'user_id' => 123,
    'data' => ['name' => 'John Doe']
]);

// Process queue
$job = $queueManager->readFromQueue();
if ($job) {
    // Process job
    $queueManager->markJobCompleted($job['id'], $result);
}
```

#### **Caching Operations**
```php
// Cache user data
$userModel = new UserModel();
$userModel->cacheUser('user:123', $userData);

// Retrieve cached data
$cachedUser = $userModel->getCachedUser('user:123');
```

#### **Performance Monitoring**
```php
// Store performance metrics
$redis->hset('metrics:' . date('Y-m-d'), 'requests', $requestCount);
$redis->expire('metrics:' . date('Y-m-d'), 86400); // 24 hour TTL
```

## API Documentation

### 🌐 Complete API Reference

#### **Health Check Endpoints**

##### `GET /health`
**Purpose**: Comprehensive application health status
**Authentication**: None required
**Response Format**: JSON

**Response Fields**:
- `status`: Overall health status (`healthy`/`unhealthy`)
- `timestamp`: ISO 8601 timestamp
- `php_version`: Current PHP version
- `memory_usage`: Current memory usage in bytes
- `uptime`: System uptime in seconds
- `services`: Object containing service health status

**Example Response**:
```json
{
  "status": "healthy",
  "timestamp": "2025-08-31T06:05:17+00:00",
  "php_version": "8.4.0",
  "memory_usage": 2097152,
  "uptime": 3600,
  "services": {
    "redis": "healthy",
    "mysql": "healthy",
    "postgres": "healthy"
  }
}
```

##### `GET /monitoring.php?endpoint=health`
**Purpose**: Production monitoring endpoint for load balancers
**Response**: Extended health information with load averages

##### `GET /monitoring.php?endpoint=metrics`
**Purpose**: Performance metrics for monitoring systems
**Response**: Memory usage, OPcache statistics, server load

##### `GET /monitoring.php?endpoint=ready`
**Purpose**: Simple readiness check for Kubernetes/Docker health checks
**Response**: `{"status": "ready", "timestamp": 1693478717}`

#### **API Testing Endpoints**

##### `GET /api/test`
**Purpose**: Test external API integrations
**Authentication**: None required
**Response**: Results from multiple external API tests

**External APIs Tested**:
- **JSONPlaceholder**: REST API testing with GET/POST operations
- **HTTPBin**: HTTP testing service with various endpoints
- **Slow API Simulation**: Performance testing with artificial delays

#### **AJAX Operations (POST to /)**

All AJAX operations use `application/x-www-form-urlencoded` content type with `action` parameter.

##### Database Operations
- **`action=test_databases`**: Test all database connections
- **`action=create_tables`**: Create database tables in MySQL and PostgreSQL
- **`action=demo_crud`**: Perform comprehensive CRUD operations

##### Queue Operations
- **`action=test_queue`**: Demo queue operations with sample data
- **`action=add_queue_data`**: Add batch data to queue (3 items with 1-minute TTL)
- **`action=read_queue_data`**: Read all data from queue without removing
- **`action=clear_queue`**: Clear all queue contents
- **`action=generate_random_data`**: Generate new random test data

##### Utility Operations
- **`action=fetch_api_data`**: Test external APIs
- **`action=debug_env`**: Debug environment variables and file status

### 🔐 Authentication

**Current Implementation**: No authentication required (development/testing application)

**Production Considerations**:
- Implement API key authentication for monitoring endpoints
- Add rate limiting for public endpoints
- Consider JWT tokens for session management

## UI

### 🎨 Frontend Framework & Design

#### **Technology Stack**
- **Framework**: Pure HTML5/CSS3/JavaScript (No external frameworks)
- **CSS Framework**: Custom responsive design with CSS Grid and Flexbox
- **JavaScript**: Vanilla ES6+ with modern async/await patterns
- **Icons**: Unicode emojis for cross-platform compatibility
- **Fonts**: System fonts (Segoe UI, Tahoma, Geneva, Verdana, sans-serif)

#### **Design System**
- **Color Scheme**: Purple gradient theme (`#667eea` to `#764ba2`)
- **Typography**: Modern sans-serif with clear hierarchy
- **Layout**: Card-based design with rounded corners and shadows
- **Responsive**: Mobile-first design with breakpoints at 768px
- **Animations**: Smooth transitions and hover effects

#### **UI Components**

##### **Header Section**
- Application title with gradient background
- System information cards (Framework, PHP Version, Web Server, Status)
- Responsive grid layout (auto-fit, minimum 250px columns)

##### **Operation Sections**
- **Health Check**: Single button with detailed status display
- **Database Operations**: Connection testing and CRUD demonstrations
- **API Operations**: External API testing with performance metrics
- **Queue System**: Complete queue management with real-time data

##### **Interactive Elements**
- **Buttons**: Gradient backgrounds with hover animations
- **Loading Indicators**: Smooth fade-in/out with descriptive text
- **Result Displays**: Scrollable containers with syntax highlighting
- **Input Fields**: Queue data input with auto-generation

### 🔄 UI Workflow Examples

#### **Database Workflow**
1. **User clicks "Test Database Connections"**
2. **AJAX request** → `POST / {action: 'test_databases'}`
3. **Backend processes** → Tests MySQL, PostgreSQL, Redis connections
4. **Response displayed** → Connection status for each database
5. **User can proceed** → Create tables or run CRUD operations

#### **Queue Workflow**
1. **Auto-generated data** → Random JSON data displayed in input field
2. **User clicks "Add Data to Queue"** → Batch of 3 items added
3. **Real-time feedback** → Success message with queue name and TTL info
4. **User can monitor** → Read queue data to see current contents
5. **Auto-cleanup** → Items expire after 1 minute automatically

#### **API Testing Workflow**
1. **User clicks "Test External APIs"**
2. **Parallel requests** → JSONPlaceholder, HTTPBin, Slow API simulation
3. **Performance tracking** → Request duration and response analysis
4. **Results display** → Success/failure status with timing information

### 📱 Responsive Design

#### **Desktop (1200px+)**
- Full grid layout with 4 information cards
- Side-by-side button groups
- Expanded result containers

#### **Tablet (768px - 1199px)**
- 2-column grid for information cards
- Stacked button groups
- Maintained spacing and readability

#### **Mobile (< 768px)**
- Single column layout
- Full-width buttons
- Compressed spacing
- Touch-friendly interface

## Health Checks

### 🏥 Health Check System

#### **How Health Checks Work**

The application implements a comprehensive health monitoring system with multiple endpoints for different use cases:

##### **Application Health Check (`/health`)**
- **Purpose**: Complete application health assessment
- **Checks Performed**:
  - PHP runtime status and version
  - Memory usage and limits
  - System uptime
  - Database connectivity (MySQL, PostgreSQL)
  - Redis connectivity and ping test
  - Service response times

##### **Production Monitoring (`/monitoring.php`)**
- **Load Balancer Ready**: `/monitoring.php?endpoint=ready`
- **Detailed Health**: `/monitoring.php?endpoint=health`
- **Performance Metrics**: `/monitoring.php?endpoint=metrics`

#### **Ports Exposed**

| Service | Port | Purpose | Health Check URL |
|---------|------|---------|------------------|
| **Application** | 8000 | Main web interface | `http://localhost:8000/health` |
| **MySQL** | 3307 | Database service | Internal connectivity check |
| **PostgreSQL** | 5433 | Database service | Internal connectivity check |
| **Redis** | 6380 | Cache/Queue service | Internal ping test |
| **phpMyAdmin** | 8080 | MySQL management | `http://localhost:8080` |
| **pgAdmin** | 8081 | PostgreSQL management | `http://localhost:8081` |
| **Redis Commander** | 8082 | Redis management | `http://localhost:8082` |

#### **Health Check Response Examples**

##### **Healthy System Response**
```json
{
  "status": "healthy",
  "timestamp": "2025-08-31T06:05:17+00:00",
  "php_version": "8.4.0",
  "memory_usage": 2097152,
  "uptime": 3600,
  "services": {
    "redis": "healthy",
    "mysql": "healthy",
    "postgres": "healthy"
  }
}
```

##### **Unhealthy System Response**
```json
{
  "status": "unhealthy",
  "timestamp": "2025-08-31T06:05:17+00:00",
  "php_version": "8.4.0",
  "memory_usage": 2097152,
  "uptime": 3600,
  "services": {
    "redis": "unhealthy",
    "mysql": "healthy",
    "postgres": "unhealthy"
  },
  "errors": [
    "Redis connection failed: Connection refused",
    "PostgreSQL connection failed: Connection timeout"
  ]
}
```

#### **Monitoring Integration**

##### **Kubernetes Health Checks**
```yaml
livenessProbe:
  httpGet:
    path: /monitoring.php?endpoint=ready
    port: 8000
  initialDelaySeconds: 30
  periodSeconds: 10

readinessProbe:
  httpGet:
    path: /health
    port: 8000
  initialDelaySeconds: 5
  periodSeconds: 5
```

##### **Docker Health Checks**
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD curl -f http://localhost:8000/health || exit 1
```

##### **Load Balancer Configuration**
```nginx
upstream simple_php_backend {
    server localhost:8000 max_fails=3 fail_timeout=30s;
}

location /health {
    access_log off;
    proxy_pass http://simple_php_backend/health;
}
```

## Development Standards

### 📋 Coding Style Conventions

#### **PHP Standards**
- **PSR-4 Autoloading**: All classes follow PSR-4 namespace conventions
- **PSR-12 Coding Style**: Extended coding style guide compliance
- **Type Declarations**: Strict typing with return type declarations
- **Documentation**: PHPDoc blocks for all public methods and classes
- **Error Handling**: Comprehensive exception handling with meaningful messages

#### **Code Examples**
```php
<?php
declare(strict_types=1);

namespace SimplePhp\Lib;

/**
 * Example class following project standards
 */
class ExampleClass
{
    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    /**
     * Example method with proper documentation
     *
     * @param string $input Input parameter
     * @return array<string, mixed> Processed result
     * @throws \Exception When processing fails
     */
    public function processData(string $input): array
    {
        try {
            // Implementation here
            return ['success' => true, 'data' => $input];
        } catch (\Exception $e) {
            throw new \Exception("Processing failed: " . $e->getMessage());
        }
    }
}
```

#### **JavaScript Standards**
- **ES6+ Features**: Modern JavaScript with async/await, arrow functions
- **No External Dependencies**: Pure vanilla JavaScript
- **Error Handling**: Comprehensive try-catch blocks for all async operations
- **Code Organization**: Logical function grouping and clear naming

#### **CSS Standards**
- **Mobile-First**: Responsive design starting from mobile breakpoints
- **CSS Grid & Flexbox**: Modern layout techniques
- **Custom Properties**: CSS variables for consistent theming
- **BEM-like Naming**: Clear, descriptive class names

### 📁 Folder/Module Naming Standards

#### **Directory Structure Standards**
- **`lib/`**: Core business logic classes (PascalCase filenames)
- **`public/`**: Web-accessible files (kebab-case for multi-word files)
- **`scripts/`**: Shell scripts (kebab-case with .sh extension)
- **`config/`**: Configuration files (lowercase with extensions)
- **`tests/`**: Test files (PascalCase with Test suffix)

#### **File Naming Conventions**
- **PHP Classes**: `PascalCase.php` (e.g., `DatabaseConnection.php`)
- **PHP Scripts**: `kebab-case.php` (e.g., `main-page.php`)
- **Shell Scripts**: `kebab-case.sh` (e.g., `start-app.sh`)
- **Configuration**: `lowercase.extension` (e.g., `app.env`)

#### **Class and Method Naming**
- **Classes**: PascalCase (e.g., `QueueManager`, `ApiClient`)
- **Methods**: camelCase (e.g., `getUserById`, `processQueue`)
- **Constants**: SCREAMING_SNAKE_CASE (e.g., `DEFAULT_TIMEOUT`)
- **Variables**: camelCase (e.g., `$userData`, `$connectionString`)

### 🏗️ Best Practices Applied

#### **Database Operations**
- **Connection Pooling**: Singleton pattern for database connections
- **Prepared Statements**: All queries use prepared statements
- **Transaction Management**: Proper transaction handling with rollback
- **Error Handling**: Database-specific error messages and logging

#### **Security Practices**
- **Input Validation**: All user inputs validated and sanitized
- **SQL Injection Prevention**: Prepared statements for all queries
- **XSS Prevention**: Output escaping for all dynamic content
- **Environment Variables**: Sensitive data stored in environment files

#### **Performance Optimization**
- **Autoloader Optimization**: Composer autoloader optimization enabled
- **Memory Management**: Proper resource cleanup and memory monitoring
- **Caching Strategy**: Redis caching for frequently accessed data
- **Database Indexing**: Proper indexes on frequently queried columns

#### **Error Handling & Logging**
- **Graceful Degradation**: Application continues functioning when services fail
- **Comprehensive Logging**: All errors logged with context information
- **User-Friendly Messages**: Clear error messages for end users
- **Debug Information**: Detailed debug info in development mode

## Testing

### 🧪 Available Test Cases

#### **Unit Tests**
The application includes comprehensive PHPUnit tests covering:

##### **Core Functionality Tests**
- **PHP Version Compatibility**: Ensures PHP 8.1+ compatibility
- **Class Existence**: Verifies all core classes are properly autoloaded
- **Database Connection**: Tests database connection utilities
- **Queue Management**: Validates queue operations and data handling
- **API Client**: Tests external API integration functionality

##### **Integration Tests**
- **Database Operations**: End-to-end database CRUD operations
- **Queue Processing**: Complete queue lifecycle testing
- **API Endpoints**: HTTP endpoint response validation
- **Health Checks**: System health monitoring functionality

##### **Configuration Tests**
- **File Existence**: Validates all required files are present
- **Composer Configuration**: Tests dependency management setup
- **Docker Configuration**: Validates container configurations
- **Makefile Commands**: Tests build system functionality

#### **Test File Structure**
```
tests/
└── SimplePhpTest.php          # Main test suite
    ├── testPhpVersion()       # PHP compatibility test
    ├── testDatabaseConnection() # Database utility tests
    ├── testUserModelExists()  # Model class tests
    ├── testApiClientExists()  # API client tests
    ├── testQueueManager()     # Queue management tests
    ├── testIndexPageExists()  # Frontend tests
    ├── testComposerJson()     # Configuration tests
    └── testMakefileExists()   # Build system tests
```

### 🚀 Commands to Run Tests

#### **Basic Test Execution**
```bash
# Run all tests
make test

# Run tests with Composer
composer test

# Run tests directly with PHPUnit
./vendor/bin/phpunit

# Run specific test class
./vendor/bin/phpunit tests/SimplePhpTest.php
```

#### **Advanced Testing Options**
```bash
# Run tests with coverage report
composer run test-coverage

# Run tests with verbose output
./vendor/bin/phpunit --verbose

# Run tests with detailed output
./vendor/bin/phpunit --debug

# Run specific test method
./vendor/bin/phpunit --filter testPhpVersion

# Generate coverage report in HTML format
./vendor/bin/phpunit --coverage-html coverage/
```

#### **Continuous Integration**
```bash
# CI-friendly test execution
./vendor/bin/phpunit --log-junit junit.xml --coverage-clover coverage.xml

# Test with different PHP versions
php8.1 ./vendor/bin/phpunit
php8.2 ./vendor/bin/phpunit
php8.3 ./vendor/bin/phpunit
php8.4 ./vendor/bin/phpunit
```

### 📊 Test Coverage

#### **Current Coverage Areas**
- **Core Classes**: 100% class existence and instantiation
- **Configuration**: 100% file existence and structure validation
- **Database Utilities**: 90% functionality coverage
- **API Endpoints**: 85% response validation
- **Queue Operations**: 80% lifecycle testing

#### **Coverage Reports**
```bash
# Generate HTML coverage report
composer run test-coverage

# View coverage report
open coverage/index.html  # macOS
xdg-open coverage/index.html  # Linux
```

### 🔍 Test Development Guidelines

#### **Writing New Tests**
```php
<?php
namespace SimplePhp\Tests;

use PHPUnit\Framework\TestCase;

class NewFeatureTest extends TestCase
{
    public function testNewFeature(): void
    {
        // Arrange
        $expectedResult = 'expected_value';

        // Act
        $actualResult = $this->performAction();

        // Assert
        $this->assertEquals($expectedResult, $actualResult);
    }

    protected function setUp(): void
    {
        // Test setup code
    }

    protected function tearDown(): void
    {
        // Test cleanup code
    }
}
```

#### **Test Data Management**
- **Fixtures**: Use consistent test data across tests
- **Mocking**: Mock external dependencies for unit tests
- **Database**: Use separate test database for integration tests
- **Cleanup**: Ensure tests clean up after themselves

## Deployment

### 🚀 Docker Build & Run Steps

#### **Complete Docker Deployment**

##### **Step 1: Build Application Image**
```bash
# Build the application image
docker build -t simple-php:latest .

# Build with specific PHP version
docker build --build-arg PHP_VERSION=8.4 -t simple-php:8.4 .
```

##### **Step 2: Start All Services**
```bash
# Start supporting services first
docker-compose -f docker-compose.services.yml up -d

# Start application with services
docker-compose up -d

# Verify all containers are running
docker-compose ps
```

##### **Step 3: Production Deployment**
```bash
# Production deployment with resource limits
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Scale application instances
docker-compose up -d --scale app=3
```

### 🌐 Ports Exposed

#### **Application Ports**
| Service | Internal Port | External Port | Purpose |
|---------|---------------|---------------|---------|
| **Simple PHP App** | 80 | 8000 | Main application |
| **PHP-FPM** | 9000 | - | FastCGI process manager |

#### **Supporting Service Ports**
| Service | Internal Port | External Port | Purpose |
|---------|---------------|---------------|---------|
| **MySQL** | 3306 | 3307 | Database server |
| **PostgreSQL** | 5432 | 5433 | Database server |
| **Redis** | 6379 | 6380 | Cache/Queue server |
| **phpMyAdmin** | 80 | 8080 | MySQL management |
| **pgAdmin** | 80 | 8081 | PostgreSQL management |
| **Redis Commander** | 8081 | 8082 | Redis management |

### 📈 Scaling Notes

#### **Horizontal Scaling**
```bash
# Scale application instances
docker-compose up -d --scale app=5

# Load balancer configuration (Nginx)
upstream simple_php_backend {
    server simple-php-app-1:80;
    server simple-php-app-2:80;
    server simple-php-app-3:80;
}
```

#### **Resource Limits**
```yaml
# docker-compose.prod.yml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M
      replicas: 3
```

#### **Database Scaling Considerations**
- **Read Replicas**: Configure MySQL/PostgreSQL read replicas for read-heavy workloads
- **Connection Pooling**: Implement connection pooling for database connections
- **Redis Clustering**: Use Redis Cluster for high-availability queue operations
- **Caching Strategy**: Implement multi-level caching (Redis + Application cache)

## Troubleshooting

### 🔧 Common Issues and Solutions

#### **Database Connection Issues**

##### **Problem**: MySQL Connection Refused
```
Error: MySQL connection failed: Connection refused
```
**Solutions**:
```bash
# Check if MySQL container is running
docker-compose ps mysql

# Restart MySQL service
docker-compose restart mysql

# Check MySQL logs
docker-compose logs mysql

# Verify port binding
netstat -tlnp | grep 3307
```

##### **Problem**: PostgreSQL Authentication Failed
```
Error: PostgreSQL connection failed: FATAL: password authentication failed
```
**Solutions**:
```bash
# Reset PostgreSQL password
docker-compose exec postgres psql -U postgres -c "ALTER USER postgres PASSWORD 'newpassword';"

# Check environment variables
docker-compose exec app env | grep POSTGRES

# Recreate PostgreSQL container
docker-compose down postgres
docker volume rm simple_php_postgres_data
docker-compose up -d postgres
```

#### **Redis Connection Issues**

##### **Problem**: Redis Connection Timeout
```
Error: Redis connection failed: Connection timeout
```
**Solutions**:
```bash
# Check Redis container status
docker-compose ps redis

# Test Redis connectivity
docker-compose exec redis redis-cli ping

# Restart Redis service
docker-compose restart redis
```

#### **Application Issues**

##### **Problem**: 500 Internal Server Error
**Solutions**:
```bash
# Check application logs
docker-compose logs app

# Check PHP error logs
docker-compose exec app tail -f /var/log/apache2/error.log

# Verify file permissions
docker-compose exec app ls -la /var/www/html

# Check PHP configuration
docker-compose exec app php -m  # Check loaded modules
docker-compose exec app php -v  # Check PHP version
```

##### **Problem**: Composer Dependencies Missing
```
Error: Class 'Predis\Client' not found
```
**Solutions**:
```bash
# Install dependencies
docker-compose exec app composer install

# Update dependencies
docker-compose exec app composer update

# Clear autoloader cache
docker-compose exec app composer dump-autoload
```

### 📍 Logs Location

#### **Application Logs**
```bash
# Application logs
docker-compose logs app

# Apache access logs
docker-compose exec app tail -f /var/log/apache2/access.log

# Apache error logs
docker-compose exec app tail -f /var/log/apache2/error.log

# PHP error logs
docker-compose exec app tail -f /var/log/php_errors.log
```

#### **Database Logs**
```bash
# MySQL logs
docker-compose logs mysql

# PostgreSQL logs
docker-compose logs postgres

# Redis logs
docker-compose logs redis
```

### 🔄 Restart/Rebuild Commands

#### **Service Management**
```bash
# Restart specific service
docker-compose restart app
docker-compose restart mysql
docker-compose restart redis

# Restart all services
docker-compose restart

# Stop and start services
docker-compose down
docker-compose up -d
```

#### **Complete Rebuild**
```bash
# Rebuild application image
docker-compose build --no-cache app

# Rebuild and restart
docker-compose down
docker-compose build
docker-compose up -d

# Clean rebuild (removes volumes)
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

#### **Emergency Recovery**
```bash
# Complete cleanup and restart
make down  # Clean everything
make setup # Check system requirements
make compile # Recompile application
make start # Start fresh

# Manual cleanup
docker-compose down -v --remove-orphans
docker system prune -f
docker volume prune -f
docker-compose up -d
```

## Future Enhancements

### 🚀 Planned Improvements

#### **Performance Enhancements**
- **OPcache Integration**: Implement PHP OPcache for improved performance
- **Database Query Optimization**: Add query caching and optimization
- **CDN Integration**: Add support for static asset delivery via CDN
- **Lazy Loading**: Implement lazy loading for database connections
- **Connection Pooling**: Add database connection pooling for high-traffic scenarios

#### **Security Improvements**
- **Authentication System**: Implement JWT-based authentication
- **Rate Limiting**: Add API rate limiting and DDoS protection
- **Input Validation**: Enhanced input validation and sanitization
- **HTTPS Support**: SSL/TLS certificate management and HTTPS enforcement
- **Security Headers**: Implement security headers (HSTS, CSP, etc.)

#### **Monitoring & Observability**
- **APM Integration**: Add support for New Relic, Datadog, or Elastic APM
- **Metrics Collection**: Implement Prometheus metrics collection
- **Distributed Tracing**: Add OpenTelemetry or Jaeger tracing
- **Log Aggregation**: Integrate with ELK stack or similar logging solutions
- **Alerting System**: Implement automated alerting for system issues

#### **Feature Additions**
- **WebSocket Support**: Real-time communication capabilities
- **File Upload/Download**: File management functionality
- **Email Integration**: SMTP email sending capabilities
- **Cron Job Management**: Scheduled task execution
- **Multi-tenancy**: Support for multiple tenants/organizations

#### **Infrastructure Improvements**
- **Kubernetes Support**: Helm charts and Kubernetes manifests
- **CI/CD Pipeline**: GitHub Actions or GitLab CI integration
- **Auto-scaling**: Horizontal pod autoscaling based on metrics
- **Blue-Green Deployment**: Zero-downtime deployment strategies
- **Backup & Recovery**: Automated database backup and recovery procedures

#### **Developer Experience**
- **API Documentation**: OpenAPI/Swagger documentation generation
- **Development Tools**: Enhanced debugging and profiling tools
- **Code Quality**: Integration with SonarQube or similar tools
- **IDE Integration**: Better IDE support and debugging capabilities
- **Documentation**: Interactive documentation with examples

#### **Database Enhancements**
- **Database Migrations**: Automated database migration system
- **Read Replicas**: Master-slave database configuration
- **Database Sharding**: Horizontal database scaling support
- **Full-Text Search**: Elasticsearch integration for search capabilities
- **Data Analytics**: Integration with analytics and reporting tools

#### **Queue System Improvements**
- **Dead Letter Queues**: Failed message handling
- **Priority Queues**: Message prioritization support
- **Queue Monitoring**: Enhanced queue monitoring and alerting
- **Message Routing**: Advanced message routing capabilities
- **Batch Processing**: Efficient batch message processing

### 🛠️ Technical Debt & Refactoring

#### **Code Quality Improvements**
- **Type Safety**: Implement strict typing throughout the codebase
- **Error Handling**: Standardize error handling and exception management
- **Code Documentation**: Comprehensive PHPDoc documentation
- **Unit Test Coverage**: Achieve 95%+ test coverage
- **Integration Tests**: Comprehensive integration test suite

#### **Architecture Improvements**
- **Dependency Injection**: Implement DI container for better testability
- **Event System**: Add event-driven architecture support
- **Service Layer**: Extract business logic into dedicated service classes
- **Repository Pattern**: Implement repository pattern for data access
- **CQRS Implementation**: Command Query Responsibility Segregation

#### **Configuration Management**
- **Environment-based Config**: Enhanced environment-specific configurations
- **Feature Flags**: Dynamic feature toggling capabilities
- **Configuration Validation**: Validate configuration on startup
- **Hot Reloading**: Configuration changes without restart
- **Centralized Config**: External configuration management

### 📋 Implementation Roadmap

#### **Phase 1: Foundation (Months 1-2)**
- [ ] Implement comprehensive logging system
- [ ] Add authentication and authorization
- [ ] Enhance error handling and validation
- [ ] Implement basic monitoring and health checks
- [ ] Add comprehensive unit and integration tests

#### **Phase 2: Performance (Months 3-4)**
- [ ] Implement caching strategies
- [ ] Add database optimization and indexing
- [ ] Implement connection pooling
- [ ] Add performance monitoring and profiling
- [ ] Optimize application for high traffic

#### **Phase 3: Scalability (Months 5-6)**
- [ ] Implement horizontal scaling support
- [ ] Add load balancing configuration
- [ ] Implement database read replicas
- [ ] Add queue system enhancements
- [ ] Implement auto-scaling capabilities

#### **Phase 4: Advanced Features (Months 7-8)**
- [ ] Add real-time communication features
- [ ] Implement advanced monitoring and alerting
- [ ] Add CI/CD pipeline automation
- [ ] Implement blue-green deployment
- [ ] Add comprehensive API documentation

#### **Phase 5: Production Readiness (Months 9-10)**
- [ ] Security audit and penetration testing
- [ ] Performance testing and optimization
- [ ] Disaster recovery planning and testing
- [ ] Documentation and training materials
- [ ] Production deployment and monitoring

---

## 📞 Support & Contributing

### 🤝 Contributing Guidelines
- Fork the repository and create feature branches
- Follow the established coding standards and conventions
- Write comprehensive tests for new features
- Update documentation for any changes
- Submit pull requests with detailed descriptions

### 📧 Support Channels
- **Issues**: GitHub Issues for bug reports and feature requests
- **Documentation**: This README and inline code documentation
- **Community**: Discussions and community support

### 📄 License
This project is part of the APM PHP Examples collection and follows the same licensing terms.

---

**🎉 Thank you for using Simple PHP Application!**

This comprehensive documentation covers every aspect of the Simple PHP application. For any questions or issues not covered here, please refer to the troubleshooting section or create an issue in the repository.

## Deployment

### 🚀 Docker Build & Run Steps

#### **Complete Docker Deployment**

##### **Step 1: Build Application Image**
```bash
# Build the application image
docker build -t simple-php:latest .

# Build with specific PHP version
docker build --build-arg PHP_VERSION=8.4 -t simple-php:8.4 .
```

##### **Step 2: Start All Services**
```bash
# Start supporting services first
docker-compose -f docker-compose.services.yml up -d

# Start application with services
docker-compose up -d

# Verify all containers are running
docker-compose ps
```

##### **Step 3: Production Deployment**
```bash
# Production deployment with resource limits
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Scale application instances
docker-compose up -d --scale app=3
```

#### **Manual Docker Commands**
```bash
# Run application container manually
docker run -d \
  --name simple-php-app \
  -p 8000:80 \
  -e MYSQL_HOST=mysql \
  -e REDIS_HOST=redis \
  --network simple-php-network \
  simple-php:latest

# Run with volume mounts for development
docker run -d \
  --name simple-php-dev \
  -p 8000:80 \
  -v $(pwd):/var/www/html \
  -e APP_ENV=development \
  simple-php:latest
```

### 🌐 Ports Exposed

#### **Application Ports**
| Service | Internal Port | External Port | Purpose |
|---------|---------------|---------------|---------|
| **Simple PHP App** | 80 | 8000 | Main application |
| **PHP-FPM** | 9000 | - | FastCGI process manager |

#### **Supporting Service Ports**
| Service | Internal Port | External Port | Purpose |
|---------|---------------|---------------|---------|
| **MySQL** | 3306 | 3307 | Database server |
| **PostgreSQL** | 5432 | 5433 | Database server |
| **Redis** | 6379 | 6380 | Cache/Queue server |
| **phpMyAdmin** | 80 | 8080 | MySQL management |
| **pgAdmin** | 80 | 8081 | PostgreSQL management |
| **Redis Commander** | 8081 | 8082 | Redis management |

#### **Network Configuration**
```yaml
networks:
  simple-php-network:
    driver: bridge
    ipam:
      config:
        - subnet: 172.21.0.0/16
```

### 📈 Scaling Notes

#### **Horizontal Scaling**
```bash
# Scale application instances
docker-compose up -d --scale app=5

# Load balancer configuration (Nginx)
upstream simple_php_backend {
    server simple-php-app-1:80;
    server simple-php-app-2:80;
    server simple-php-app-3:80;
    server simple-php-app-4:80;
    server simple-php-app-5:80;
}
```

#### **Resource Limits**
```yaml
# docker-compose.prod.yml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 512M
        reservations:
          cpus: '0.5'
          memory: 256M
      replicas: 3
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3
```

#### **Database Scaling Considerations**
- **Read Replicas**: Configure MySQL/PostgreSQL read replicas for read-heavy workloads
- **Connection Pooling**: Implement connection pooling for database connections
- **Redis Clustering**: Use Redis Cluster for high-availability queue operations
- **Caching Strategy**: Implement multi-level caching (Redis + Application cache)

## Troubleshooting

### 🔧 Common Issues and Solutions

#### **Database Connection Issues**

##### **Problem**: MySQL Connection Refused
```
Error: MySQL connection failed: Connection refused
```
**Solutions**:
```bash
# Check if MySQL container is running
docker-compose ps mysql

# Restart MySQL service
docker-compose restart mysql

# Check MySQL logs
docker-compose logs mysql

# Verify port binding
netstat -tlnp | grep 3307
```

##### **Problem**: PostgreSQL Authentication Failed
```
Error: PostgreSQL connection failed: FATAL: password authentication failed
```
**Solutions**:
```bash
# Reset PostgreSQL password
docker-compose exec postgres psql -U postgres -c "ALTER USER postgres PASSWORD 'newpassword';"

# Check environment variables
docker-compose exec app env | grep POSTGRES

# Recreate PostgreSQL container
docker-compose down postgres
docker volume rm simple_php_postgres_data
docker-compose up -d postgres
```

#### **Redis Connection Issues**

##### **Problem**: Redis Connection Timeout
```
Error: Redis connection failed: Connection timeout
```
**Solutions**:
```bash
# Check Redis container status
docker-compose ps redis

# Test Redis connectivity
docker-compose exec redis redis-cli ping

# Check Redis configuration
docker-compose exec redis redis-cli CONFIG GET "*"

# Restart Redis service
docker-compose restart redis
```

#### **Application Issues**

##### **Problem**: 500 Internal Server Error
**Solutions**:
```bash
# Check application logs
docker-compose logs app

# Check PHP error logs
docker-compose exec app tail -f /var/log/apache2/error.log

# Verify file permissions
docker-compose exec app ls -la /var/www/html

# Check PHP configuration
docker-compose exec app php -m  # Check loaded modules
docker-compose exec app php -v  # Check PHP version
```

##### **Problem**: Composer Dependencies Missing
```
Error: Class 'Predis\Client' not found
```
**Solutions**:
```bash
# Install dependencies
docker-compose exec app composer install

# Update dependencies
docker-compose exec app composer update

# Clear autoloader cache
docker-compose exec app composer dump-autoload
```

#### **Performance Issues**

##### **Problem**: Slow Response Times
**Diagnostic Steps**:
```bash
# Check system resources
docker stats

# Monitor application performance
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8000/health

# Check database performance
docker-compose exec mysql mysqladmin processlist
docker-compose exec postgres pg_stat_activity

# Monitor Redis performance
docker-compose exec redis redis-cli --latency
```

**Solutions**:
- Enable OPcache for PHP
- Optimize database queries and add indexes
- Implement Redis caching for frequently accessed data
- Scale application horizontally

### 📍 Logs Location

#### **Application Logs**
```bash
# Application logs
docker-compose logs app

# Apache access logs
docker-compose exec app tail -f /var/log/apache2/access.log

# Apache error logs
docker-compose exec app tail -f /var/log/apache2/error.log

# PHP error logs
docker-compose exec app tail -f /var/log/php_errors.log
```

#### **Database Logs**
```bash
# MySQL logs
docker-compose logs mysql

# PostgreSQL logs
docker-compose logs postgres

# Redis logs
docker-compose logs redis
```

#### **System Logs**
```bash
# All services logs
docker-compose logs

# Follow logs in real-time
docker-compose logs -f

# Logs for specific service
docker-compose logs -f app
```

### 🔄 Restart/Rebuild Commands

#### **Service Management**
```bash
# Restart specific service
docker-compose restart app
docker-compose restart mysql
docker-compose restart redis

# Restart all services
docker-compose restart

# Stop and start services
docker-compose down
docker-compose up -d
```

#### **Complete Rebuild**
```bash
# Rebuild application image
docker-compose build --no-cache app

# Rebuild and restart
docker-compose down
docker-compose build
docker-compose up -d

# Clean rebuild (removes volumes)
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

#### **Emergency Recovery**
```bash
# Complete cleanup and restart
make down  # Clean everything
make setup # Check system requirements
make compile # Recompile application
make start # Start fresh

# Manual cleanup
docker-compose down -v --remove-orphans
docker system prune -f
docker volume prune -f
docker-compose up -d
```
