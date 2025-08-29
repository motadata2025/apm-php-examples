# APM PHP Examples - Usage Examples

This guide provides practical examples of using the APM PHP Examples project for various scenarios.

## 📋 Table of Contents

1. [Basic Usage Examples](#basic-usage-examples)
2. [Development Scenarios](#development-scenarios)
3. [Testing Scenarios](#testing-scenarios)
4. [Production Scenarios](#production-scenarios)
5. [Integration Examples](#integration-examples)
6. [Custom Extensions](#custom-extensions)

## 🚀 Basic Usage Examples

### Example 1: Quick Local Development Setup

```bash
# Clone and setup for local development
git clone <repository-url>
cd apm-php-examples

# Quick setup with defaults
make setup
make start

# Access applications
open http://localhost:8080  # Simple PHP
open http://localhost:8081  # Laravel
open http://localhost:8082  # Symfony
open http://localhost:8083  # Slim Framework
open http://localhost:8084  # CodeIgniter
```

### Example 2: Custom PHP Version Setup

```bash
# Select PHP 8.3 for compatibility testing
make select-php
# Choose option 3 (PHP 8.3)

# Or set directly
echo "PHP_VERSION=8.3" > config/deployment.env

# Rebuild with PHP 8.3
make clean
make setup
```

### Example 3: Public Internet Access

```bash
# Configure for public access
./scripts/configure-deployment.sh
# Set network interface to 0.0.0.0
# Set port range 8080-8099

# Start services
make start

# Access from internet (replace with your server IP)
curl http://YOUR_SERVER_IP:8080/health
```

## 💻 Development Scenarios

### Scenario 1: Framework Comparison

Compare how different frameworks handle the same functionality:

```bash
# Start all applications
make start

# Test database operations across frameworks
curl -X POST http://localhost:8080/test-databases  # Simple PHP
curl -X POST http://localhost:8081/test-databases  # Laravel
curl -X POST http://localhost:8082/test-databases  # Symfony
curl -X POST http://localhost:8083/test-databases  # Slim
curl -X POST http://localhost:8084/apm/testDatabases  # CodeIgniter

# Compare response times and structures
```

### Scenario 2: API Development

Use the examples as a base for API development:

```bash
# Start in CLI mode for rapid development
make deploy-php-cli

# Create custom API endpoint
cat > simple-php/public/api/custom.php << 'EOF'
<?php
header('Content-Type: application/json');

$data = [
    'message' => 'Custom API endpoint',
    'timestamp' => date('c'),
    'version' => '1.0'
];

echo json_encode($data);
EOF

# Test immediately
curl http://localhost:8080/api/custom.php
```

### Scenario 3: Database Migration Testing

Test database migrations across different frameworks:

```bash
# Laravel migrations
docker-compose exec laravel-app php artisan make:migration create_test_table
docker-compose exec laravel-app php artisan migrate

# Symfony migrations
docker-compose exec symfony-app php bin/console make:migration
docker-compose exec symfony-app php bin/console doctrine:migrations:migrate

# CodeIgniter migrations
docker-compose exec codeigniter-app php spark make:migration CreateTestTable
docker-compose exec codeigniter-app php spark migrate
```

## 🧪 Testing Scenarios

### Scenario 1: Load Testing

```bash
# Install Apache Bench (if not available)
sudo apt-get install apache2-utils  # Ubuntu/Debian
brew install httpie  # macOS

# Basic load test
ab -n 1000 -c 10 http://localhost:8080/

# Test specific endpoints
ab -n 500 -c 5 -p post_data.json -T application/json http://localhost:8081/api/test

# Create post data file
echo '{"test": "data"}' > post_data.json
```

### Scenario 2: Cross-Framework Testing

```bash
# Run comprehensive tests across all frameworks
make test-cli

# Test specific functionality
for port in 8080 8081 8082 8083 8084; do
    echo "Testing port $port:"
    curl -s http://localhost:$port/health | jq .
done
```

### Scenario 3: Database Performance Testing

```bash
# Create performance test script
cat > test-db-performance.php << 'EOF'
<?php
$start = microtime(true);
$iterations = 100;

for ($i = 0; $i < $iterations; $i++) {
    $pdo = new PDO('mysql:host=mysql;dbname=apm_examples', 'root', 'rootpassword');
    $stmt = $pdo->prepare('SELECT * FROM users LIMIT 10');
    $stmt->execute();
    $results = $stmt->fetchAll();
}

$end = microtime(true);
echo "Time for $iterations queries: " . ($end - $start) . " seconds\n";
echo "Average time per query: " . (($end - $start) / $iterations) . " seconds\n";
EOF

# Run performance test
docker run --rm -v $(pwd):/var/www/html --network apm-php-examples_apm-network apm-simple-php-cli:8.4 php test-db-performance.php
```

## 🏭 Production Scenarios

### Scenario 1: High-Performance Deployment

```bash
# Deploy with Nginx + PHP-FPM for best performance
make deploy-nginx-fpm

# Enable all optimizations
cat >> config/deployment.env << 'EOF'
OPCACHE_ENABLED=true
REDIS_CACHE_ENABLED=true
GZIP_COMPRESSION_ENABLED=true
SECURITY_HEADERS_ENABLED=true
EOF

# Rebuild with optimizations
make clean
make setup
```

### Scenario 2: SSL/HTTPS Setup

```bash
# Generate SSL certificates
mkdir -p config/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout config/ssl/server.key \
    -out config/ssl/server.crt \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=yourdomain.com"

# Configure SSL
cat >> config/deployment.env << 'EOF'
SSL_ENABLED=true
SSL_CERT_PATH=/etc/ssl/certs/server.crt
SSL_KEY_PATH=/etc/ssl/private/server.key
EOF

# Deploy with SSL
make clean
make setup
```

### Scenario 3: Load Balancer Setup

```bash
# Enable load balancer
docker-compose --profile load-balancer up -d nginx-lb

# Configure load balancer
cat > config/nginx/nginx.conf << 'EOF'
upstream backend {
    server simple-php:80;
    server laravel-app:80;
    server symfony-app:80;
    server slim-framework:80;
    server codeigniter-app:80;
}

server {
    listen 80;
    location / {
        proxy_pass http://backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
EOF

# Restart load balancer
docker-compose restart nginx-lb
```

## 🔗 Integration Examples

### Example 1: CI/CD Pipeline

```bash
# Create GitHub Actions workflow
mkdir -p .github/workflows
cat > .github/workflows/ci.yml << 'EOF'
name: CI/CD Pipeline

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup
      run: make setup
    
    - name: Run Tests
      run: make test-cli
    
    - name: Deploy
      if: github.ref == 'refs/heads/main'
      run: make start
EOF
```

### Example 2: Monitoring Integration

```bash
# Add Prometheus monitoring
cat > docker-compose.monitoring.yml << 'EOF'
version: '3.8'
services:
  prometheus:
    image: prom/prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./config/prometheus.yml:/etc/prometheus/prometheus.yml
    networks:
      - apm-network

  grafana:
    image: grafana/grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
    networks:
      - apm-network
EOF

# Start with monitoring
docker-compose -f docker-compose.yml -f docker-compose.monitoring.yml up -d
```

### Example 3: External API Integration

```bash
# Create external API test
cat > test-external-apis.php << 'EOF'
<?php
$apis = [
    'simple-php' => 'http://localhost:8080/fetch-api-data',
    'laravel' => 'http://localhost:8081/fetch-api-data',
    'symfony' => 'http://localhost:8082/fetch-api-data',
    'slim' => 'http://localhost:8083/fetch-api-data',
    'codeigniter' => 'http://localhost:8084/apm/fetchApiData'
];

foreach ($apis as $framework => $url) {
    echo "Testing $framework API integration:\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "  ✅ Success: " . count($data['data'] ?? []) . " API responses\n";
    } else {
        echo "  ❌ Failed: HTTP $httpCode\n";
    }
    echo "\n";
}
EOF

# Run API integration test
php test-external-apis.php
```

## 🔧 Custom Extensions

### Example 1: Adding New Framework

```bash
# Create new framework directory
mkdir my-framework

# Copy structure from existing framework
cp -r slim-framework/* my-framework/

# Modify for your framework
# Edit my-framework/composer.json
# Edit my-framework/public/index.php
# Edit my-framework/src/Controllers/

# Add to docker-compose.yml
cat >> docker-compose.yml << 'EOF'
  my-framework:
    build: ./my-framework
    ports:
      - "8085:80"
    volumes:
      - ./my-framework:/var/www/html
      - ./shared:/var/www/html/shared
    networks:
      - apm-network
EOF
```

### Example 2: Custom Monitoring

```bash
# Create custom health check
cat > scripts/custom-health-check.sh << 'EOF'
#!/bin/bash

echo "Custom Health Check Report"
echo "========================="

# Check application response times
for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do
    port=$(docker-compose port $app 80 2>/dev/null | cut -d: -f2)
    if [ -n "$port" ]; then
        response_time=$(curl -o /dev/null -s -w "%{time_total}" http://localhost:$port/health)
        echo "$app: ${response_time}s"
    fi
done

# Check database connections
echo ""
echo "Database Status:"
docker-compose exec mysql mysqladmin ping -h localhost -u root -prootpassword 2>/dev/null && echo "MySQL: OK" || echo "MySQL: FAILED"
docker-compose exec postgres pg_isready -U postgres 2>/dev/null && echo "PostgreSQL: OK" || echo "PostgreSQL: FAILED"
docker-compose exec redis redis-cli ping 2>/dev/null && echo "Redis: OK" || echo "Redis: FAILED"
EOF

chmod +x scripts/custom-health-check.sh
./scripts/custom-health-check.sh
```

### Example 3: Custom Deployment Script

```bash
# Create deployment script for specific environment
cat > scripts/deploy-staging.sh << 'EOF'
#!/bin/bash
set -e

echo "Deploying to Staging Environment"
echo "================================"

# Set staging configuration
cat > config/deployment.env << 'STAGING_EOF'
PHP_VERSION=8.4
DEPLOYMENT_TYPE=nginx-fpm
NETWORK_INTERFACE=0.0.0.0
APP_ENV=staging
APP_DEBUG=false
SSL_ENABLED=true
OPCACHE_ENABLED=true
REDIS_CACHE_ENABLED=true
STAGING_EOF

# Generate configuration
./scripts/generate-docker-compose.sh

# Deploy
make clean
make setup
make start

# Run tests
make test-cli

# Notify deployment
echo "Staging deployment completed successfully!"
echo "Applications available at:"
make endpoints
EOF

chmod +x scripts/deploy-staging.sh
./scripts/deploy-staging.sh
```

## 📊 Performance Benchmarking

### Example: Framework Performance Comparison

```bash
# Create benchmark script
cat > benchmark-frameworks.sh << 'EOF'
#!/bin/bash

echo "Framework Performance Benchmark"
echo "==============================="

frameworks=(
    "simple-php:8080"
    "laravel:8081"
    "symfony:8082"
    "slim:8083"
    "codeigniter:8084"
)

for framework in "${frameworks[@]}"; do
    name=$(echo $framework | cut -d: -f1)
    port=$(echo $framework | cut -d: -f2)
    
    echo "Benchmarking $name..."
    ab -n 1000 -c 10 -q http://localhost:$port/ > /tmp/bench_$name.txt
    
    requests_per_sec=$(grep "Requests per second" /tmp/bench_$name.txt | awk '{print $4}')
    time_per_request=$(grep "Time per request" /tmp/bench_$name.txt | head -1 | awk '{print $4}')
    
    echo "$name: $requests_per_sec req/sec, $time_per_request ms/req"
done
EOF

chmod +x benchmark-frameworks.sh
./benchmark-frameworks.sh
```

## 📚 Next Steps

- [Deployment Guide](../deployment/README.md)
- [PHP CLI Usage Guide](../php-cli/README.md)
- [Troubleshooting Guide](../troubleshooting/README.md)
- Explore individual application documentation in each framework directory
