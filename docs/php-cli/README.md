# APM PHP Examples - PHP CLI Usage Guide

This guide covers using PHP CLI for development, testing, and deployment of the APM PHP Examples applications.

## 📋 Table of Contents

1. [Overview](#overview)
2. [CLI Deployment](#cli-deployment)
3. [Development Workflow](#development-workflow)
4. [Testing with CLI](#testing-with-cli)
5. [Debugging](#debugging)
6. [Performance Analysis](#performance-analysis)
7. [CLI Commands Reference](#cli-commands-reference)

## 🔍 Overview

PHP CLI mode provides several advantages for development and testing:

- **Fast iteration**: No web server restart required
- **Direct debugging**: Easy to add debug output
- **Memory profiling**: Better visibility into memory usage
- **Testing isolation**: Each test runs in a clean environment
- **Development server**: Built-in PHP development server

## 🚀 CLI Deployment

### Quick CLI Setup

```bash
# Deploy all applications in CLI mode
make deploy-php-cli
```

### Manual CLI Setup

```bash
# Set CLI deployment type
echo "DEPLOYMENT_TYPE=php-cli" > config/deployment.env

# Generate configuration
./scripts/generate-docker-compose.sh

# Build and start
make clean
make setup
make start
```

### CLI Container Features

Each CLI container includes:
- PHP CLI with all required extensions
- Composer for dependency management
- PHPUnit for testing
- Development server capability
- Health check scripts
- Debugging tools

## 💻 Development Workflow

### Starting Development Servers

```bash
# Start all applications in CLI mode
make start

# Or start individual applications
cd simple-php
docker run --rm -v $(pwd):/var/www/html -p 8000:8000 apm-simple-php-cli:8.4 /usr/local/bin/dev-server.sh

cd laravel-app
docker run --rm -v $(pwd):/var/www/html -p 8001:8000 apm-laravel-app-cli:8.4 php artisan serve --host=0.0.0.0

cd symfony-app
docker run --rm -v $(pwd):/var/www/html -p 8002:8000 apm-symfony-app-cli:8.4 symfony server:start --no-tls

cd slim-framework
docker run --rm -v $(pwd):/var/www/html -p 8003:8000 apm-slim-framework-cli:8.4 /usr/local/bin/dev-server.sh

cd codeigniter-app
docker run --rm -v $(pwd):/var/www/html -p 8004:8000 apm-codeigniter-app-cli:8.4 /usr/local/bin/dev-server.sh
```

### Live Code Reloading

CLI mode supports live code reloading:

```bash
# Changes to PHP files are immediately reflected
# No container restart required
echo "<?php echo 'Hello World Updated!';" > simple-php/public/test.php

# Test immediately
curl http://localhost:8000/test.php
```

### Interactive PHP Shell

```bash
# Access PHP interactive shell for any application
docker run --rm -it -v $(pwd)/simple-php:/var/www/html apm-simple-php-cli:8.4 php -a

# Or with application context
docker run --rm -it -v $(pwd)/laravel-app:/var/www/html apm-laravel-app-cli:8.4 php artisan tinker
```

## 🧪 Testing with CLI

### Comprehensive CLI Testing

```bash
# Run all tests using CLI
make test-cli
```

This runs:
1. PHP syntax validation
2. Composer dependency validation
3. PHPUnit tests
4. Application health checks
5. Database connectivity tests
6. Redis connectivity tests

### Individual Application Testing

```bash
# Test specific application
cd simple-php
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-simple-php-cli:8.4 /usr/local/bin/run-tests.sh

# Laravel testing
cd laravel-app
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-laravel-app-cli:8.4 php artisan test

# Symfony testing
cd symfony-app
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-symfony-app-cli:8.4 php bin/phpunit

# Slim Framework testing
cd slim-framework
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-slim-framework-cli:8.4 phpunit

# CodeIgniter testing
cd codeigniter-app
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-codeigniter-app-cli:8.4 phpunit
```

### Custom Test Scripts

Create custom test scripts for specific scenarios:

```bash
# Create a custom test script
cat > test-api-endpoints.php << 'EOF'
<?php
// Test all API endpoints
$endpoints = [
    'http://localhost:8000/health',
    'http://localhost:8000/test-databases',
    'http://localhost:8000/demo-crud',
    'http://localhost:8000/fetch-api-data',
    'http://localhost:8000/test-queue'
];

foreach ($endpoints as $endpoint) {
    $response = file_get_contents($endpoint);
    $data = json_decode($response, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ $endpoint - OK\n";
    } else {
        echo "❌ $endpoint - FAILED\n";
    }
}
EOF

# Run custom test
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-simple-php-cli:8.4 php test-api-endpoints.php
```

## 🐛 Debugging

### Debug Mode Configuration

```bash
# Enable debug mode for CLI
APP_DEBUG=true
LOG_LEVEL=debug
DISPLAY_ERRORS=On
ERROR_REPORTING=E_ALL
```

### Xdebug Setup

```bash
# Add Xdebug to CLI containers
docker run --rm -v $(pwd)/simple-php:/var/www/html -e XDEBUG_MODE=debug apm-simple-php-cli:8.4 php -dxdebug.start_with_request=yes your-script.php
```

### Error Logging

```bash
# View PHP error logs
docker run --rm -v $(pwd)/simple-php:/var/www/html apm-simple-php-cli:8.4 tail -f /var/log/php_errors.log

# Custom error logging
docker run --rm -v $(pwd)/simple-php:/var/www/html apm-simple-php-cli:8.4 php -r "error_log('Debug message', 3, '/var/log/debug.log');"
```

### Memory and Performance Debugging

```bash
# Memory usage analysis
docker run --rm -v $(pwd)/simple-php:/var/www/html apm-simple-php-cli:8.4 php -r "
echo 'Memory usage: ' . memory_get_usage(true) . ' bytes\n';
echo 'Peak memory: ' . memory_get_peak_usage(true) . ' bytes\n';
"

# Execution time measurement
docker run --rm -v $(pwd)/simple-php:/var/www/html apm-simple-php-cli:8.4 php -r "
\$start = microtime(true);
// Your code here
\$end = microtime(true);
echo 'Execution time: ' . (\$end - \$start) . ' seconds\n';
"
```

## 📊 Performance Analysis

### Benchmarking

```bash
# Simple benchmark script
cat > benchmark.php << 'EOF'
<?php
$iterations = 1000;
$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    // Simulate database operation
    $pdo = new PDO('mysql:host=mysql;dbname=apm_examples', 'root', 'rootpassword');
    $stmt = $pdo->query('SELECT 1');
    $result = $stmt->fetch();
}

$end = microtime(true);
$total_time = $end - $start;
$avg_time = $total_time / $iterations;

echo "Total time: {$total_time} seconds\n";
echo "Average time per operation: {$avg_time} seconds\n";
echo "Operations per second: " . (1 / $avg_time) . "\n";
EOF

# Run benchmark
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-simple-php-cli:8.4 php benchmark.php
```

### Memory Profiling

```bash
# Memory profiling script
cat > memory-profile.php << 'EOF'
<?php
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

echo "Initial memory: " . formatBytes(memory_get_usage(true)) . "\n";

// Your application code here
$data = [];
for ($i = 0; $i < 10000; $i++) {
    $data[] = str_repeat('x', 100);
}

echo "After data creation: " . formatBytes(memory_get_usage(true)) . "\n";
echo "Peak memory: " . formatBytes(memory_get_peak_usage(true)) . "\n";

unset($data);
echo "After cleanup: " . formatBytes(memory_get_usage(true)) . "\n";
EOF

# Run memory profiling
docker run --rm -v $(pwd):/var/www/html apm-simple-php-cli:8.4 php memory-profile.php
```

## 📚 CLI Commands Reference

### Application Management

```bash
# Start development server
docker run --rm -v $(pwd)/app:/var/www/html -p 8000:8000 apm-app-cli:8.4 /usr/local/bin/dev-server.sh

# Run tests
docker run --rm -v $(pwd)/app:/var/www/html apm-app-cli:8.4 /usr/local/bin/run-tests.sh

# Health check
docker run --rm -v $(pwd)/app:/var/www/html apm-app-cli:8.4 /usr/local/bin/health-check.sh

# Interactive shell
docker run --rm -it -v $(pwd)/app:/var/www/html apm-app-cli:8.4 bash
```

### Composer Operations

```bash
# Install dependencies
docker run --rm -v $(pwd)/app:/var/www/html apm-app-cli:8.4 composer install

# Update dependencies
docker run --rm -v $(pwd)/app:/var/www/html apm-app-cli:8.4 composer update

# Validate composer.json
docker run --rm -v $(pwd)/app:/var/www/html apm-app-cli:8.4 composer validate

# Show package info
docker run --rm -v $(pwd)/app:/var/www/html apm-app-cli:8.4 composer show
```

### Database Operations

```bash
# Test database connection
docker run --rm -v $(pwd)/app:/var/www/html --network apm-php-examples_apm-network apm-app-cli:8.4 php -r "
try {
    \$pdo = new PDO('mysql:host=mysql;dbname=apm_examples', 'root', 'rootpassword');
    echo 'Database connection successful\n';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
}
"

# Run database migrations (Laravel)
docker run --rm -v $(pwd)/laravel-app:/var/www/html --network apm-php-examples_apm-network apm-laravel-app-cli:8.4 php artisan migrate

# Run database migrations (Symfony)
docker run --rm -v $(pwd)/symfony-app:/var/www/html --network apm-php-examples_apm-network apm-symfony-app-cli:8.4 php bin/console doctrine:migrations:migrate
```

### Framework-Specific Commands

#### Laravel
```bash
# Artisan commands
docker run --rm -v $(pwd)/laravel-app:/var/www/html apm-laravel-app-cli:8.4 php artisan list
docker run --rm -v $(pwd)/laravel-app:/var/www/html apm-laravel-app-cli:8.4 php artisan make:controller TestController
docker run --rm -v $(pwd)/laravel-app:/var/www/html apm-laravel-app-cli:8.4 php artisan route:list
```

#### Symfony
```bash
# Console commands
docker run --rm -v $(pwd)/symfony-app:/var/www/html apm-symfony-app-cli:8.4 php bin/console list
docker run --rm -v $(pwd)/symfony-app:/var/www/html apm-symfony-app-cli:8.4 php bin/console make:controller TestController
docker run --rm -v $(pwd)/symfony-app:/var/www/html apm-symfony-app-cli:8.4 php bin/console debug:router
```

#### CodeIgniter
```bash
# Spark commands
docker run --rm -v $(pwd)/codeigniter-app:/var/www/html apm-codeigniter-app-cli:8.4 php spark list
docker run --rm -v $(pwd)/codeigniter-app:/var/www/html apm-codeigniter-app-cli:8.4 php spark make:controller TestController
docker run --rm -v $(pwd)/codeigniter-app:/var/www/html apm-codeigniter-app-cli:8.4 php spark routes
```

## 🔧 Advanced CLI Usage

### Custom CLI Scripts

Create reusable CLI scripts for common tasks:

```bash
# Create deployment script
cat > scripts/deploy-cli.sh << 'EOF'
#!/bin/bash
echo "Deploying in CLI mode..."
make deploy-php-cli
echo "Running tests..."
make test-cli
echo "Deployment complete!"
EOF

chmod +x scripts/deploy-cli.sh
./scripts/deploy-cli.sh
```

### Automated Testing Pipeline

```bash
# Create CI/CD pipeline script
cat > scripts/ci-pipeline.sh << 'EOF'
#!/bin/bash
set -e

echo "Starting CI/CD Pipeline..."

# Build applications
make clean
make setup

# Run comprehensive tests
make test-cli

# Deploy if tests pass
if [ $? -eq 0 ]; then
    echo "Tests passed! Deploying..."
    make start
else
    echo "Tests failed! Deployment aborted."
    exit 1
fi
EOF

chmod +x scripts/ci-pipeline.sh
./scripts/ci-pipeline.sh
```

## 📚 Next Steps

- [Deployment Guide](../deployment/README.md)
- [Application Examples](../examples/README.md)
- [Troubleshooting Guide](../troubleshooting/README.md)
