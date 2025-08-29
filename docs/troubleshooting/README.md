# APM PHP Examples - Troubleshooting Guide

This guide helps you diagnose and resolve common issues with the APM PHP Examples project.

## 📋 Table of Contents

1. [Quick Diagnostics](#quick-diagnostics)
2. [Common Issues](#common-issues)
3. [Docker Issues](#docker-issues)
4. [Network Issues](#network-issues)
5. [Database Issues](#database-issues)
6. [Application-Specific Issues](#application-specific-issues)
7. [Performance Issues](#performance-issues)
8. [Getting Help](#getting-help)

## 🔍 Quick Diagnostics

### System Check

```bash
# Check system requirements
docker --version
docker-compose --version
make --version

# Check current configuration
make show-config

# Check container status
make status

# Run comprehensive tests
make test-cli
```

### Health Check

```bash
# Check all application health endpoints
curl -f http://localhost:8080/health  # Simple PHP
curl -f http://localhost:8081/health  # Laravel
curl -f http://localhost:8082/health  # Symfony
curl -f http://localhost:8083/health  # Slim Framework
curl -f http://localhost:8084/health  # CodeIgniter
```

### Log Analysis

```bash
# View all logs
make logs

# View specific application logs
docker-compose logs simple-php
docker-compose logs laravel-app
docker-compose logs mysql
docker-compose logs redis
```

## 🚨 Common Issues

### Issue: Port Already in Use

**Symptoms:**
- Error: "Port is already allocated"
- Cannot start containers

**Solutions:**

```bash
# Check what's using the port
sudo netstat -tulpn | grep :8080
# or
sudo ss -tulpn | grep :8080

# Kill process using the port
sudo kill -9 <PID>

# Or use different ports
./scripts/configure-deployment.sh
# Select different port range

# Or let the system auto-assign ports
make clean
make setup
```

### Issue: Docker Build Failures

**Symptoms:**
- Build process fails
- "No space left on device"
- Package installation errors

**Solutions:**

```bash
# Clean Docker system
docker system prune -a -f
docker volume prune -f

# Free up space
docker image prune -a -f

# Rebuild from scratch
make clean
make setup

# Check disk space
df -h
```

### Issue: Permission Denied

**Symptoms:**
- Cannot write to files
- Permission denied errors
- Container startup failures

**Solutions:**

```bash
# Fix file ownership
sudo chown -R $USER:$USER .

# Make scripts executable
chmod +x scripts/*.sh

# Fix Docker socket permissions (Linux)
sudo usermod -aG docker $USER
# Log out and log back in

# Fix SELinux issues (if applicable)
sudo setsebool -P container_manage_cgroup true
```

### Issue: Database Connection Failed

**Symptoms:**
- "Connection refused" errors
- Database tests failing
- Applications cannot connect to database

**Solutions:**

```bash
# Check database containers
docker-compose ps | grep -E "(mysql|postgres|redis)"

# Restart database services
docker-compose restart mysql postgres redis

# Check database logs
docker-compose logs mysql
docker-compose logs postgres

# Test database connectivity
docker run --rm --network apm-php-examples_apm-network mysql:8.0 mysql -h mysql -u root -prootpassword -e "SELECT 1"

# Reset database
docker-compose down -v
docker-compose up -d mysql postgres redis
```

## 🐳 Docker Issues

### Issue: Docker Daemon Not Running

**Symptoms:**
- "Cannot connect to the Docker daemon"
- Docker commands fail

**Solutions:**

```bash
# Start Docker service (Linux)
sudo systemctl start docker
sudo systemctl enable docker

# Start Docker Desktop (macOS/Windows)
# Open Docker Desktop application

# Check Docker status
docker info
```

### Issue: Docker Compose Version Issues

**Symptoms:**
- "Unsupported Compose file version"
- Syntax errors in docker-compose.yml

**Solutions:**

```bash
# Check Docker Compose version
docker-compose --version

# Update Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Use legacy version if needed
docker-compose --compatibility up
```

### Issue: Container Memory Issues

**Symptoms:**
- Containers being killed
- Out of memory errors
- Slow performance

**Solutions:**

```bash
# Check Docker memory limits
docker stats

# Increase Docker memory (Docker Desktop)
# Settings > Resources > Memory > Increase limit

# Optimize container memory usage
# Edit config/deployment.env
PHP_MEMORY_LIMIT=256M
OPCACHE_MEMORY_CONSUMPTION=64

# Restart with new settings
make clean
make setup
```

## 🌐 Network Issues

### Issue: Cannot Access Applications

**Symptoms:**
- "Connection refused" when accessing URLs
- Applications not responding

**Solutions:**

```bash
# Check if containers are running
make status

# Check port mappings
docker-compose ps

# Check network configuration
docker network ls
docker network inspect apm-php-examples_apm-network

# Test with curl
curl -v http://localhost:8080/

# Check firewall settings
sudo ufw status  # Ubuntu
sudo firewall-cmd --list-all  # CentOS/RHEL
```

### Issue: DNS Resolution Problems

**Symptoms:**
- Cannot resolve container names
- External API calls failing

**Solutions:**

```bash
# Check DNS resolution inside containers
docker run --rm --network apm-php-examples_apm-network alpine nslookup mysql
docker run --rm --network apm-php-examples_apm-network alpine nslookup google.com

# Restart Docker daemon
sudo systemctl restart docker

# Use IP addresses instead of hostnames
# Edit application configuration to use container IPs
```

### Issue: SSL/TLS Certificate Problems

**Symptoms:**
- SSL certificate errors
- HTTPS not working

**Solutions:**

```bash
# Generate self-signed certificates
mkdir -p config/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout config/ssl/server.key \
    -out config/ssl/server.crt \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"

# Update configuration
SSL_ENABLED=true
SSL_CERT_PATH=/etc/ssl/certs/server.crt
SSL_KEY_PATH=/etc/ssl/private/server.key

# Rebuild containers
make clean
make setup
```

## 🗄️ Database Issues

### Issue: MySQL Connection Refused

**Symptoms:**
- "Connection refused" to MySQL
- MySQL container not starting

**Solutions:**

```bash
# Check MySQL container logs
docker-compose logs mysql

# Check MySQL process
docker-compose exec mysql mysqladmin ping -h localhost -u root -prootpassword

# Reset MySQL data
docker-compose down -v
docker volume rm apm-php-examples_mysql_data
docker-compose up -d mysql

# Wait for MySQL to initialize
sleep 30
```

### Issue: PostgreSQL Authentication Failed

**Symptoms:**
- "Authentication failed" for PostgreSQL
- Cannot connect to PostgreSQL

**Solutions:**

```bash
# Check PostgreSQL logs
docker-compose logs postgres

# Test connection
docker-compose exec postgres psql -U postgres -d apm_examples -c "SELECT 1"

# Reset PostgreSQL
docker-compose down -v
docker volume rm apm-php-examples_postgres_data
docker-compose up -d postgres
```



### Issue: Redis Connection Problems

**Symptoms:**
- Redis connection timeouts
- Cache not working

**Solutions:**

```bash
# Check Redis logs
docker-compose logs redis

# Test Redis connection
docker-compose exec redis redis-cli ping

# Clear Redis data
docker-compose exec redis redis-cli FLUSHALL

# Restart Redis
docker-compose restart redis
```

## 🔧 Application-Specific Issues

### Laravel Issues

```bash
# Clear Laravel caches
docker-compose exec laravel-app php artisan cache:clear
docker-compose exec laravel-app php artisan config:clear
docker-compose exec laravel-app php artisan route:clear

# Generate application key
docker-compose exec laravel-app php artisan key:generate

# Run migrations
docker-compose exec laravel-app php artisan migrate

# Check Laravel logs
docker-compose exec laravel-app tail -f storage/logs/laravel.log
```

### Symfony Issues

```bash
# Clear Symfony cache
docker-compose exec symfony-app php bin/console cache:clear

# Check Symfony requirements
docker-compose exec symfony-app php bin/console about

# Run Symfony migrations
docker-compose exec symfony-app php bin/console doctrine:migrations:migrate

# Check Symfony logs
docker-compose exec symfony-app tail -f var/log/dev.log
```

### CodeIgniter Issues

```bash
# Clear CodeIgniter cache
docker-compose exec codeigniter-app rm -rf writable/cache/*

# Check CodeIgniter environment
docker-compose exec codeigniter-app php spark env

# Run CodeIgniter migrations
docker-compose exec codeigniter-app php spark migrate

# Check CodeIgniter logs
docker-compose exec codeigniter-app tail -f writable/logs/log-*.php
```

## ⚡ Performance Issues

### Issue: Slow Application Response

**Symptoms:**
- Long response times
- Timeouts

**Solutions:**

```bash
# Enable OPcache
OPCACHE_ENABLED=true

# Increase PHP memory limit
PHP_MEMORY_LIMIT=512M

# Enable Redis caching
REDIS_CACHE_ENABLED=true

# Use production deployment type
make deploy-nginx-fpm

# Monitor performance
docker stats
```

### Issue: High Memory Usage

**Symptoms:**
- Containers using too much memory
- System becoming unresponsive

**Solutions:**

```bash
# Check memory usage
docker stats --no-stream

# Optimize PHP-FPM settings
# Edit Dockerfile.apache-fpm or Dockerfile.nginx-fpm
pm.max_children = 25
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 10

# Reduce container memory limits
docker run --memory=256m ...

# Use lighter deployment type
make deploy-php-cli
```

## 🆘 Getting Help

### Collect Diagnostic Information

```bash
# Create diagnostic report
cat > diagnostic-report.txt << EOF
System Information:
==================
OS: $(uname -a)
Docker: $(docker --version)
Docker Compose: $(docker-compose --version)
Make: $(make --version | head -1)

Configuration:
=============
$(cat config/deployment.env 2>/dev/null || echo "No configuration found")

Container Status:
================
$(docker-compose ps)

Recent Logs:
===========
$(docker-compose logs --tail=50)

Disk Usage:
==========
$(df -h)

Memory Usage:
============
$(free -h)
EOF

echo "Diagnostic report saved to diagnostic-report.txt"
```

### Debug Mode

```bash
# Enable debug mode
APP_DEBUG=true
LOG_LEVEL=debug

# Rebuild with debug settings
make clean
make setup

# Check debug logs
make logs
```

### Community Support

1. **Check Documentation**: Review all documentation in the `docs/` directory
2. **Search Issues**: Look for similar issues in the project repository
3. **Create Issue**: If you can't find a solution, create a detailed issue report
4. **Include Information**:
   - Operating system and version
   - Docker and Docker Compose versions
   - Configuration file contents
   - Error messages and logs
   - Steps to reproduce the issue

### Professional Support

For production deployments or complex issues:

1. **Performance Optimization**: Consider professional PHP performance tuning
2. **Security Audit**: Get a security review for production deployments
3. **Custom Development**: Extend the examples for your specific needs
4. **Training**: PHP and Docker training for your team

## 📚 Additional Resources

- [Deployment Guide](../deployment/README.md)
- [PHP CLI Usage Guide](../php-cli/README.md)
- [Application Examples](../examples/README.md)
- [Docker Documentation](https://docs.docker.com/)
- [PHP Documentation](https://www.php.net/docs.php)
