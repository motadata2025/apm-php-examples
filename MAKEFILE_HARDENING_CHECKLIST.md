# APM PHP Examples - Makefile Hardening Checklist

## 📋 Overview

This document summarizes the comprehensive hardening improvements made to the Makefile-driven workflow across all PHP framework applications. The hardening ensures robust cross-platform compatibility, enhanced error handling, and production-ready reliability.

## 🎯 Hardening Objectives Achieved

### ✅ **A. Enhanced Error Handling & Messaging**
- **Consistent Exit Codes**: All scripts use standardized exit codes (0=success, 1=general error, 126=cannot execute, 127=command not found, 128=invalid argument)
- **Structured Error Messages**: Format: `❌ [target] failed: <reason>` with suggested next steps
- **Success Verification**: Clear success messages with verification tips
- **Verbose Mode**: `VERBOSE=1` environment variable for detailed logging
- **Comprehensive Logging**: All operations logged to `logs/<script>.log` with timestamps

### ✅ **B. Cross-Platform Portability**
- **OS Detection**: Automatic detection of Ubuntu/Debian, RHEL/CentOS/Alma, Arch Linux
- **Architecture Support**: CPU architecture detection via `uname -m`
- **Package Manager Integration**: Automatic detection and use of apt, yum, pacman
- **Command Availability**: `command -v` checks with fallback suggestions
- **POSIX Compatibility**: Shell scripts use POSIX-compatible features where possible

### ✅ **C. Docker Robustness**
- **Compose Version Detection**: Automatic preference for `docker compose` v2 with v1 fallback
- **Daemon Health Checks**: `docker info` validation with actionable guidance
- **Port Conflict Detection**: Pre-flight port availability checks with conflict resolution
- **Health Monitoring**: Container health polling with configurable timeouts (default 120s)
- **Resource Monitoring**: Container resource usage reporting via `docker stats`

### ✅ **D. Centralized Configuration**
- **Single Source of Truth**: `config/runtime.env` for all application settings
- **Migration Utility**: Automatic migration from legacy `.env` and `config/app.env` files
- **Configuration Validation**: Syntax and required variable validation
- **Backup System**: Automatic configuration backups during migration
- **Metadata Tracking**: Configuration version, source, and generation tracking

### ✅ **E. PHP & Composer Robustness**
- **Version Detection**: Support for PHP 8.1-8.4 with availability reporting
- **Extension Validation**: Required extension checking with installation guidance
- **Composer Integration**: Robust composer error handling with platform requirements
- **Memory Management**: Configurable memory limits for Composer operations
- **Platform Configuration**: Automatic `composer config platform.php` management

### ✅ **F. Framework Compatibility**
- **Version Mapping**: Framework-specific version compatibility checking
- **Dependency Validation**: Required package and extension validation per framework
- **Cache Management**: Framework-specific cache clearing (Laravel, Symfony, CodeIgniter)
- **Runtime Optimization**: Framework-appropriate performance settings

### ✅ **G. Resource Monitoring**
- **Process Tracking**: PID-based process monitoring with resource usage
- **Container Stats**: Real-time Docker container resource reporting
- **Port Monitoring**: Active port usage and binding information
- **System Resources**: CPU, memory, and disk usage reporting

### ✅ **H. Enhanced Cleanup**
- **Selective Cleanup**: `REMOVE_VOLUMES=1` for volume removal
- **Cache Clearing**: `CLEAR_CACHE=1` for application cache cleanup
- **Verification**: Post-cleanup validation and reporting
- **Force Options**: Comprehensive cleanup with orphan container removal

## 🔧 Implementation Status by Application

### **Simple PHP** ✅ **COMPLETED**
- ✅ Common functions library (`scripts/common-functions.sh`)
- ✅ Hardened system check (`scripts/check-system-hardened.sh`)
- ✅ Configuration migration (`scripts/migrate-config.sh`)
- ✅ Centralized config (`config/runtime.env`)
- ✅ Enhanced Makefile with error handling
- ✅ Documentation (`CONFIG_STANDARD.md`, `OPERATION.md`)

### **Symfony App** 🔄 **IN PROGRESS**
- ⏳ Adapting common functions for Symfony
- ⏳ Framework-specific cache management
- ⏳ Symfony console integration

### **Laravel App** 📋 **PLANNED**
- 📋 Artisan command integration
- 📋 Laravel-specific optimizations
- 📋 Queue and cache management

### **CodeIgniter App** 📋 **PLANNED**
- 📋 Spark CLI integration
- 📋 CodeIgniter-specific configurations
- 📋 Framework cache handling

### **Slim Framework** 📋 **PLANNED**
- 📋 Slim-specific optimizations
- 📋 Minimal framework adaptations
- 📋 Performance tuning

## 🚀 Developer Usage Guide

### **Environment Variables for Enhanced Control**

```bash
# Verbose logging for all operations
VERBOSE=1 make setup
VERBOSE=1 make compile
VERBOSE=1 make start

# Automatic extension installation
AUTO_INSTALL_EXTENSIONS=1 make setup

# Cache management
CLEAR_CACHE=1 make compile
CLEAR_CACHE=1 make down

# Volume management
REMOVE_VOLUMES=1 make down

# Timeout configuration
DOCKER_HEALTH_TIMEOUT=300 make setup

# Non-interactive mode
AUTO_CONFIRM=1 ./scripts/migrate-config.sh
```

### **Essential Workflow Commands**

```bash
# Initial setup with full validation
make setup

# Interactive configuration
make compile

# Start with resource monitoring
make start

# Comprehensive status check
make status

# Clean shutdown preserving data
make stop

# Complete cleanup with volumes
REMOVE_VOLUMES=1 make down
```

### **Troubleshooting Commands**

```bash
# Detailed system diagnostics
make php-status
make network-status

# Configuration validation
cat config/runtime.env
bash -n config/runtime.env

# Docker diagnostics
docker ps -a
docker compose logs

# Log analysis
tail -f logs/check-system-hardened.log
tail -f logs/application.log
```

### **Migration from Legacy Configuration**

```bash
# Automatic migration during setup
make setup

# Manual migration with preview
./scripts/migrate-config.sh

# Dry run migration
DRY_RUN=1 ./scripts/migrate-config.sh

# Force migration
AUTO_CONFIRM=1 ./scripts/migrate-config.sh
```

## 🔍 Validation Procedures

### **System Requirements Validation**
```bash
# Verify all requirements are met
make setup

# Check specific components
make php-status        # PHP versions and extensions
make network-status    # Network configuration
docker ps              # Container health
```

### **Configuration Validation**
```bash
# Validate configuration syntax
bash -n config/runtime.env

# Check configuration completeness
make status

# Verify Docker integration
docker compose config
```

### **Application Validation**
```bash
# End-to-end validation
make setup && make compile && make start && make status

# Performance validation
docker stats --no-stream
make status  # Shows resource usage
```

## 📊 Monitoring and Maintenance

### **Health Monitoring**
- Container health checks with 120s timeout
- Process monitoring with PID tracking
- Resource usage reporting (CPU, memory, network)
- Port binding validation

### **Log Management**
- Centralized logging in `logs/` directory
- Timestamped log entries
- Verbose mode for detailed debugging
- Log rotation recommendations

### **Configuration Management**
- Single source of truth in `config/runtime.env`
- Automatic backup during migrations
- Version tracking and metadata
- Validation and syntax checking

## 🔒 Security Enhancements

### **Network Security**
- Default localhost binding (`127.0.0.1`)
- Explicit public access control (`ALLOW_PUBLIC=false`)
- Port conflict detection and resolution
- Firewall integration guidance

### **Configuration Security**
- Sensitive data protection recommendations
- Environment variable override support
- Local configuration file support (`.gitignore`d)
- Production security settings

## 📈 Performance Optimizations

### **Resource Management**
- Configurable memory limits for PHP and Composer
- Container resource monitoring
- Process tracking and optimization
- Cache management strategies

### **Startup Optimization**
- Parallel container startup
- Health check optimization
- Dependency caching
- Configuration validation caching

## 🎯 Next Steps for Remaining Applications

### **Priority Order**
1. **Laravel App** - High usage, complex framework requirements
2. **Symfony App** - Complex console integration, cache management
3. **Slim Framework** - Lightweight, minimal changes needed
4. **CodeIgniter** - Moderate complexity, Spark CLI integration

### **Implementation Plan**
1. Copy and adapt common functions library
2. Update system check scripts with framework-specific requirements
3. Implement configuration migration utilities
4. Update Makefiles with enhanced error handling
5. Create framework-specific documentation
6. Validate end-to-end workflows

## 📋 Acceptance Criteria Status

- ✅ **Simple PHP**: `make setup` completes with healthy containers
- ✅ **Simple PHP**: `make compile` completes with PHP version selection
- ✅ **Simple PHP**: `make down` reliably removes containers
- ✅ **Simple PHP**: `CONFIG_STANDARD.md` and `OPERATION.md` created
- ✅ **Simple PHP**: Comprehensive error handling implemented
- 🔄 **Remaining Apps**: Implementation in progress

The hardening implementation provides a robust, production-ready foundation for PHP application development and deployment across diverse Linux environments.
