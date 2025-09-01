# Simple PHP - Configuration Standard

## 📋 Overview

This document defines the configuration standard for the Simple PHP application, establishing `config/runtime.env` as the single source of truth for all application settings.

## 🎯 Single Source of Truth

**Primary Configuration File**: `config/runtime.env`

This file contains all configuration variables used by:
- Makefiles and build scripts
- Docker Compose services
- Application runtime
- Web server configurations
- Development and production environments

## 📁 Configuration Structure

```
simple-php/
├── config/
│   ├── runtime.env              # SINGLE SOURCE OF TRUTH
│   ├── app.env.example          # Template for new deployments
│   └── backup-YYYYMMDD-HHMMSS/  # Automatic backups during migration
├── Makefile                     # Reads from runtime.env
├── docker-compose.yml           # Uses runtime.env variables
└── scripts/                     # All scripts source runtime.env
```

## ⚙️ Configuration Variables

### **Application Settings**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `APP_NAME` | "Simple PHP" | Application display name | ✅ |
| `APP_PORT` | 8000 | Application HTTP port | ✅ |
| `NETWORK_INTERFACE` | "127.0.0.1" | Network binding interface | ✅ |
| `NETWORK_DISPLAY` | "localhost" | Display name for interface | ❌ |
| `PUBLIC_IP` | "auto-detected" | Public IP address | ❌ |
| `PHP_VERSION` | "8.4" | PHP version to use | ✅ |
| `DEPLOYMENT_TYPE` | "php-cli" | Deployment method | ✅ |
| `DEPLOYMENT_DESC` | "PHP Built-in Server" | Deployment description | ❌ |

### **Environment Configuration**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `APP_ENV` | production | Application environment | ✅ |
| `APP_DEBUG` | false | Debug mode flag | ✅ |

### **Database Configuration**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `MYSQL_HOST` | localhost | MySQL server host | ✅ |
| `MYSQL_PORT` | 3307 | MySQL server port (isolated) | ✅ |
| `MYSQL_DATABASE` | simple_php_db | MySQL database name | ✅ |
| `MYSQL_USERNAME` | root | MySQL username | ✅ |
| `MYSQL_PASSWORD` | rootpassword | MySQL password | ✅ |
| `POSTGRES_HOST` | localhost | PostgreSQL server host | ✅ |
| `POSTGRES_PORT` | 5433 | PostgreSQL server port (isolated) | ✅ |
| `POSTGRES_DATABASE` | simple_php_db | PostgreSQL database name | ✅ |
| `POSTGRES_USERNAME` | postgres | PostgreSQL username | ✅ |
| `POSTGRES_PASSWORD` | postgrespassword | PostgreSQL password | ✅ |
| `REDIS_HOST` | localhost | Redis server host | ✅ |
| `REDIS_PORT` | 6380 | Redis server port (isolated) | ✅ |

### **Web Server Configuration**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `APACHE_APP_DIR` | "/var/www/simple-php" | Apache document root | ❌ |
| `NGINX_APP_DIR` | "/var/www/simple-php" | Nginx document root | ❌ |
| `PHP_FPM_SOCKET` | "/run/php/php8.4-fpm.sock" | PHP-FPM socket path | ❌ |

### **Docker Configuration**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `DOCKER_COMPOSE_CMD` | "docker compose" | Docker Compose command | ❌ |
| `DOCKER_NETWORK` | "simple_php_network" | Docker network name | ❌ |
| `DOCKER_MYSQL_PORT` | 3307 | Docker MySQL port mapping | ✅ |
| `DOCKER_POSTGRES_PORT` | 5433 | Docker PostgreSQL port mapping | ✅ |
| `DOCKER_REDIS_PORT` | 6380 | Docker Redis port mapping | ✅ |

### **Security Configuration**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `SECURE_HEADERS` | true | Enable security headers | ❌ |
| `CSRF_PROTECTION` | true | Enable CSRF protection | ❌ |
| `XSS_PROTECTION` | true | Enable XSS protection | ❌ |
| `ALLOW_PUBLIC` | false | Allow public network access | ❌ |

### **Resource Limits**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `PHP_MEMORY_LIMIT` | "256M" | PHP memory limit | ❌ |
| `PHP_MAX_EXECUTION_TIME` | 30 | PHP max execution time | ❌ |
| `COMPOSER_MEMORY_LIMIT` | "2G" | Composer memory limit | ❌ |

### **Timeout Settings**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `DOCKER_HEALTH_TIMEOUT` | 120 | Container health check timeout | ❌ |
| `CONTAINER_START_TIMEOUT` | 60 | Container startup timeout | ❌ |
| `APPLICATION_START_TIMEOUT` | 30 | Application startup timeout | ❌ |

### **Cache and Cleanup Settings**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `CLEAR_CACHE` | false | Clear cache during operations | ❌ |
| `REMOVE_VOLUMES` | false | Remove Docker volumes on cleanup | ❌ |
| `CACHE_TIMEOUT` | 3600 | Cache timeout in seconds | ❌ |

### **Logging Configuration**
| Variable | Default | Description | Required |
|----------|---------|-------------|----------|
| `LOG_LEVEL` | info | Application log level | ❌ |
| `LOG_CHANNEL` | stack | Log channel configuration | ❌ |
| `MONITORING_ENABLED` | true | Enable monitoring features | ❌ |
| `LOG_FILE` | "logs/application.log" | Application log file path | ❌ |

## 🔧 Configuration Management

### **Initial Setup**
```bash
# Copy template (if needed)
cp config/app.env.example config/runtime.env

# Run interactive configuration
make compile
```

### **Migration from Legacy Config**
```bash
# Automatic migration during setup
make setup

# Manual migration
./scripts/migrate-config.sh

# Dry run migration
DRY_RUN=1 ./scripts/migrate-config.sh
```

### **Environment-Specific Overrides**
```bash
# Development with debug enabled
echo "APP_DEBUG=true" >> config/local.env

# Production with specific PHP version
export PHP_VERSION="8.3"
make compile
```

### **Validation**
```bash
# Validate current configuration
make status

# Check configuration file
cat config/runtime.env

# Verify Docker integration
docker compose config
```

## 🔒 Security Considerations

### **Sensitive Data**
- Database passwords should be changed from defaults in production
- Use environment variables for sensitive values in CI/CD
- Add `config/local.env` to `.gitignore` for local overrides

### **Network Security**
- Default binding is localhost only (`127.0.0.1`)
- Set `ALLOW_PUBLIC=true` only when external access is required
- Use firewall rules for production deployments

### **File Permissions**
- Ensure `config/runtime.env` is readable by application user
- Protect sensitive configuration files from unauthorized access

## 🚀 Best Practices

### **Development Workflow**
1. Use `make compile` for configuration changes
2. Test with `make status` after changes
3. Use `VERBOSE=1` for detailed logging
4. Keep local overrides in `config/local.env`

### **Production Deployment**
1. Review all default passwords and secrets
2. Set appropriate `APP_ENV=production`
3. Configure proper network interfaces
4. Enable security features (`SECURE_HEADERS=true`)
5. Set resource limits based on server capacity

### **Troubleshooting**
1. Check configuration with `make status`
2. Validate syntax: `bash -n config/runtime.env`
3. Compare with template: `diff config/runtime.env config/app.env.example`
4. Check logs: `tail -f logs/application.log`

## 📋 Configuration Metadata

The configuration file includes metadata for tracking:
- `CONFIG_VERSION`: Configuration schema version
- `CONFIG_GENERATED_BY`: Tool that generated the configuration
- `CONFIG_LAST_UPDATED`: Last modification timestamp
- `CONFIG_SOURCE`: Source file name
- `CONFIG_MIGRATED_FROM`: Legacy files used in migration

This metadata helps with configuration management and troubleshooting.
