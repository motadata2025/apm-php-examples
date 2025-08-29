# APM PHP Examples - Deployment Guide

This comprehensive guide covers all deployment options for the APM PHP Examples project, including multiple web server configurations, PHP versions, and deployment environments.

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Deployment Types](#deployment-types)
3. [PHP Version Selection](#php-version-selection)
4. [Network Configuration](#network-configuration)
5. [Step-by-Step Deployment](#step-by-step-deployment)
6. [Production Deployment](#production-deployment)
7. [Troubleshooting](#troubleshooting)

## 🚀 Quick Start

### Prerequisites

- Docker 20.10+ and Docker Compose 2.0+
- Git
- Make (optional, but recommended)
- Minimum 4GB RAM and 10GB disk space

### Fastest Setup

```bash
# Clone the repository
git clone <repository-url>
cd apm-php-examples

# Configure deployment (interactive)
make configure

# Setup and start all applications
make setup
make start

# View all endpoints
make endpoints
```

## 🏗️ Deployment Types

The project supports four different deployment configurations:

### 1. Apache mod_php (Default)
- **Best for**: Traditional hosting environments
- **Performance**: Good for small to medium traffic
- **Memory usage**: Moderate
- **Configuration**: Simple

```bash
make deploy-apache-mod-php
```

### 2. Apache PHP-FPM
- **Best for**: High-traffic applications
- **Performance**: Better than mod_php
- **Memory usage**: Lower
- **Configuration**: Moderate complexity

```bash
make deploy-apache-fpm
```

### 3. Nginx PHP-FPM
- **Best for**: High-performance production environments
- **Performance**: Excellent
- **Memory usage**: Lowest
- **Configuration**: More complex

```bash
make deploy-nginx-fpm
```

### 4. PHP CLI
- **Best for**: Development and testing
- **Performance**: Good for development
- **Memory usage**: Minimal
- **Configuration**: Simple

```bash
make deploy-php-cli
```

## 🐘 PHP Version Selection

### Supported Versions

- **PHP 8.1** - LTS version (supported until November 2024)
- **PHP 8.2** - Stable version (supported until December 2025)
- **PHP 8.3** - Latest stable (supported until November 2026)
- **PHP 8.4** - Latest version (supported until November 2027) **[Recommended]**

### Interactive Selection

```bash
make select-php
```

### Manual Configuration

```bash
# Edit config/deployment.env
PHP_VERSION=8.4

# Rebuild containers
make clean
make setup
```

### Version-Specific Commands

```bash
make compile-81  # Build with PHP 8.1
make compile-82  # Build with PHP 8.2
make compile-83  # Build with PHP 8.3
make compile-84  # Build with PHP 8.4
```

## 🌐 Network Configuration

### Local Development (Default)
```bash
NETWORK_INTERFACE=127.0.0.1
```

### Public Access (Internet-accessible)
```bash
NETWORK_INTERFACE=0.0.0.0
```

### Dynamic Port Configuration

The system automatically finds available ports in the specified range:

```bash
PORT_RANGE_START=8080
PORT_RANGE_END=8099
```

Applications will be assigned ports automatically:
- Simple PHP: First available port
- Laravel: Next available port
- Symfony: Next available port
- Slim Framework: Next available port
- CodeIgniter: Next available port

## 📝 Step-by-Step Deployment

### Step 1: Initial Configuration

```bash
# Run interactive configuration
./scripts/configure-deployment.sh
```

This will prompt you for:
- Network interface (local/public)
- PHP version
- Deployment type
- Port configuration
- SSL settings (optional)

### Step 2: Generate Docker Configuration

```bash
# Generate Docker Compose files based on configuration
./scripts/generate-docker-compose.sh
```

### Step 3: Build Applications

```bash
# Build all applications with selected configuration
make build-all
```

### Step 4: Start Services

```bash
# Start all applications and services
make start
```

### Step 5: Verify Deployment

```bash
# Check application status
make status

# View all endpoints
make endpoints

# Run comprehensive tests
make test-cli
```

## 🏭 Production Deployment

### Security Configuration

1. **Enable SSL**:
```bash
SSL_ENABLED=true
SSL_CERT_PATH=/etc/ssl/certs
SSL_KEY_PATH=/etc/ssl/private
```

2. **Security Headers**:
```bash
SECURITY_HEADERS_ENABLED=true
```

3. **Rate Limiting**:
```bash
RATE_LIMITING_ENABLED=true
RATE_LIMIT_REQUESTS_PER_MINUTE=60
```

### Performance Optimization

1. **OPcache**:
```bash
OPCACHE_ENABLED=true
```

2. **Redis Caching**:
```bash
REDIS_CACHE_ENABLED=true
```

3. **Gzip Compression**:
```bash
GZIP_COMPRESSION_ENABLED=true
```

### Monitoring

1. **Health Checks**:
```bash
MONITORING_ENABLED=true
HEALTH_CHECK_INTERVAL=30
```

2. **Logging**:
```bash
LOG_LEVEL=info
LOG_RETENTION_DAYS=30
```

### Load Balancer Setup

```bash
# Enable load balancer
docker-compose --profile load-balancer up -d nginx-lb
```

## 🔧 Advanced Configuration

### Custom Environment Variables

Create a `.env.local` file for custom overrides:

```bash
# Custom database settings
DB_HOST=custom-mysql-host
DB_PORT=3307

# Custom Redis settings
REDIS_HOST=custom-redis-host
REDIS_PORT=6380

# Custom application settings
APP_ENV=staging
APP_DEBUG=false
```

### Multi-Environment Setup

```bash
# Development
cp config/deployment.env config/deployment.dev.env

# Staging
cp config/deployment.env config/deployment.staging.env

# Production
cp config/deployment.env config/deployment.prod.env

# Use specific environment
ln -sf deployment.prod.env config/deployment.env
```

## 📊 Monitoring and Maintenance

### Health Checks

All applications include health check endpoints:

```bash
curl http://localhost:8080/health  # Simple PHP
curl http://localhost:8081/health  # Laravel
curl http://localhost:8082/health  # Symfony
curl http://localhost:8083/health  # Slim Framework
curl http://localhost:8084/health  # CodeIgniter
```

### Log Management

```bash
# View all logs
make logs

# View specific application logs
docker-compose logs -f simple-php
docker-compose logs -f laravel-app
```

### Backup and Recovery

```bash
# Enable automated backups
BACKUP_ENABLED=true
BACKUP_SCHEDULE="0 2 * * *"  # Daily at 2 AM
BACKUP_RETENTION_DAYS=7
```

## 🚨 Troubleshooting

### Common Issues

1. **Port Already in Use**:
   - The system automatically finds available ports
   - Check `make show-config` for assigned ports

2. **Docker Build Failures**:
   ```bash
   # Clean and rebuild
   make clean
   make setup
   ```

3. **Database Connection Issues**:
   ```bash
   # Check database services
   docker-compose ps
   
   # Restart database services
   docker-compose restart mysql postgres redis
   ```

4. **Permission Issues**:
   ```bash
   # Fix file permissions
   sudo chown -R $USER:$USER .
   chmod +x scripts/*.sh
   ```

### Debug Mode

```bash
# Enable debug mode
APP_DEBUG=true
LOG_LEVEL=debug

# Rebuild with debug settings
make clean
make setup
```

### Getting Help

- Check the [Troubleshooting Guide](../troubleshooting/README.md)
- View application logs: `make logs`
- Run diagnostics: `make test-cli`
- Check container status: `make status`

## 📚 Next Steps

- [PHP CLI Usage Guide](../php-cli/README.md)
- [Application Examples](../examples/README.md)
- [Troubleshooting Guide](../troubleshooting/README.md)
