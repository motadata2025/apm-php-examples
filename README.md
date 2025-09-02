# APM PHP Examples - Clean Sample Applications

A minimal collection of PHP framework applications designed for Application Performance Monitoring (APM) testing and demonstration. This branch contains simplified, runnable sample applications with Docker essentials and documentation.

## Repository Structure

```
apm-php-examples/
├── requirements_overview.md   # Consolidated requirements for all apps
├── README.md                  # This file
├── DOCKER_PORTS.md           # Port allocation reference
├── simple-php/               # Vanilla PHP application
├── laravel-app/              # Laravel framework application
├── symfony-app/              # Symfony framework application
├── slim-framework/           # Slim framework application
└── codeigniter-app/          # CodeIgniter framework application
```

## Applications Overview

Each application is a complete, runnable example with:

- **Docker containerization** with Dockerfile and docker-compose.yml
- **Multi-database support** (MySQL, PostgreSQL, Redis)
- **Minimal source code** required for demonstration
- **Clean documentation** and setup instructions
- **Isolated port allocation** to prevent conflicts

## Port Allocation

| Application       | App  | MySQL | PostgreSQL | Redis | Adminer |
|-------------------|------|-------|------------|-------|---------|
| simple-php        | 8000 | 3307  | 5433       | 6380  | 8080    |
| slim-framework    | 8001 | 3309  | 5435       | 6382  | 8081    |
| symfony-app       | 8002 | 3308  | 5434       | 6381  | 8082    |
| codeigniter-app   | 8003 | 3310  | 5436       | 6383  | 8083    |
| laravel-app       | 8004 | 3311  | 5437       | 6384  | 8084    |

## 🚀 CLI Server Mode Quick Start (Recommended)

### Prerequisites

- PHP 8.1+ (8.3 recommended)
- Composer (for dependency management)
- Linux distribution (Ubuntu, CentOS, RHEL, etc.)

### Universal Setup

```bash
# 1. Check demo status (optional)
chmod +x demo-status-check.sh
./demo-status-check.sh

# 2. Start all applications with CLI server
./start-cli-server.sh simple-php 0.0.0.0 8080 &
./start-cli-server.sh laravel-app 0.0.0.0 8081 &
./start-cli-server.sh symfony-app 0.0.0.0 8082 &
./start-cli-server.sh slim-framework 0.0.0.0 8083 &
./start-cli-server.sh codeigniter-app 0.0.0.0 8084 &

# 3. Access applications
curl http://localhost:8080/  # Simple PHP APM
curl http://localhost:8081/  # Laravel APM
curl http://localhost:8082/  # Symfony APM
curl http://localhost:8083/  # Slim Framework APM
curl http://localhost:8084/  # CodeIgniter APM

# 4. View in browser
# http://localhost:8080/  # Simple PHP APM Dashboard
# http://localhost:8081/  # Laravel APM Dashboard
# http://localhost:8082/  # Symfony APM Dashboard
# http://localhost:8083/  # Slim Framework APM Dashboard
# http://localhost:8084/  # CodeIgniter APM Dashboard
```

### 💡 Database Connection Status

**Expected behavior**: You may see database connection failures like:
```json
{
  "mysql": "Failed: MySQL connection failed...",
  "postgres": "Failed: PostgreSQL connection failed...",
  "redis": "Connected"
}
```

**This is normal and expected** for demonstration purposes. The applications work without databases and will show connection status for monitoring purposes.

- ✅ **For Demo**: No database setup required
- 📖 **For Full Setup**: See `DATABASE_SETUP_GUIDE.md`

---

## 🐳 Docker Mode (Alternative)

### Prerequisites

- Docker and Docker Compose v2
- PHP 8.1+ (for local development)
- Composer (for dependency management)

### Running Any Application

```bash
# Navigate to application directory
cd {application-name}

# Start Docker services (databases, cache)
docker compose up -d

# Install PHP dependencies
composer install

# Start the application (method varies by framework)
# See individual application README for specific commands
```

### Example: Running Simple PHP

```bash
cd simple-php
docker compose up -d
composer install
php -S localhost:8000 -t public
```

### Example: Running Laravel

```bash
cd laravel-app
docker compose up -d
composer install
php artisan key:generate
php artisan serve --port=8004
```

## Application Details

Each application includes:

- **Dockerfile**: Ready-to-use container configuration
- **docker-compose.yml**: Database and cache services
- **composer.json**: PHP dependencies
- **README.md**: Application-specific setup instructions
- **Minimal source code**: Essential files for demonstration

## Verification

After starting any application:

```bash
# Check Docker services
docker compose ps

# Test application
curl http://localhost:{port}

# Access database management (optional)
# Adminer available at http://localhost:{adminer-port}
```

## Documentation

- **requirements_overview.md**: Complete setup requirements for all applications
- **DOCKER_PORTS.md**: Detailed port allocation and conflict resolution
- Individual application READMEs: Framework-specific instructions

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

## Getting Started

For detailed setup requirements and troubleshooting, see:
- `requirements_overview.md` - Complete setup guide
- `DOCKER_PORTS.md` - Port allocation details
- Individual application READMEs for framework-specific instructions

This clean branch provides minimal, working examples perfect for APM testing and demonstration.