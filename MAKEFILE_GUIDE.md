# APM PHP Examples - Makefile Operations Guide

## Overview

This guide provides comprehensive documentation for the Makefile-based automation system across all PHP framework applications in the APM PHP Examples project. The system is designed around a **single entry point philosophy** where all operations must be executed through `make` commands, abstracting the complexity of underlying scripts and ensuring consistency across all frameworks.

## Architecture Philosophy

### Core Principles
- **Single Entry Point**: Everything executed via Makefile commands only
- **Abstracted Complexity**: Complex operations hidden behind simple `make` commands
- **Consistency**: Identical workflow across all frameworks (Simple PHP, Symfony, Laravel, CodeIgniter, Slim)
- **No Manual Steps**: Complete automation without direct script execution
- **Dynamic Configuration**: Automatic adaptation to system changes (IP, ports, services)

### Framework Applications & Port Allocation

| Application | Default Port | MySQL | PostgreSQL | Redis | Database Names |
|-------------|--------------|-------|------------|-------|----------------|
| simple-php | 8000 | 3307 | 5433 | 6380 | simple_php_db |
| symfony-app | 8002 | 3308 | 5434 | 6381 | symfony_app_db |
| slim-framework | 8001 | 3309 | 5435 | 6382 | slim_framework_db |
| codeigniter-app | 8003 | 3310 | 5436 | 6383 | codeigniter_app_db |
| laravel-app | 8004 | 3311 | 5437 | 6384 | laravel_app_db |

**Configuration Standardization**: All applications now use consistent configuration management with standardized ports, database names, and environment variables. The `config/app.env` file serves as the single source of truth for each application's configuration. See `DOCKER_PORTS.md` for detailed port allocation and troubleshooting information.

## Per-App Centralized Configuration

### Configuration Architecture
Each application maintains its own **centralized configuration structure**:

```
{app-name}/
├── config/
│   ├── app.env                 # SINGLE SOURCE OF TRUTH (runtime config)
│   └── app.env.example         # Template for new deployments
├── docker-compose.yml          # Reads from config/app.env
├── Makefile                    # Sources config/app.env
└── scripts/*.sh               # All scripts source config/app.env
```

### Configuration Flow
1. **Template**: `config/app.env.example` provides default values and documentation
2. **Generation**: `make compile` creates `config/app.env` with user-selected settings
3. **Integration**: All services (Docker, Makefiles, scripts) read from `config/app.env`
4. **Validation**: `make status` verifies configuration consistency

### Single Source of Truth Benefits
- **No Duplication**: All settings in one file per application
- **Consistency**: All services use identical configuration values
- **Easy Management**: One file to edit for all application changes
- **Conflict Prevention**: Clear port allocation prevents inter-app conflicts
- **Framework Agnostic**: Same pattern works across all PHP frameworks

## Core Makefile Commands

### Primary Operations

#### `make setup`
**Purpose**: System requirements validation and initial environment setup
**Internal Flow**:
1. Calls `./scripts/check-system.sh`
2. Validates PHP versions (8.1-8.4) availability
3. Checks web server installations (Apache, Nginx)
4. Verifies Docker and Docker Compose
5. Validates required system packages
6. Reports system readiness status

**Output**: System compatibility report with recommendations for missing components

#### `make compile`
**Purpose**: Application compilation and deployment configuration
**Internal Flow**:
1. Calls `./scripts/compile-app.sh`
2. **Application State Check**: Verifies no running instances
3. **Network Configuration**:
   - Presents network options (localhost, public IP, all interfaces, custom)
   - Handles dynamic IP detection and management
   - Configures firewall rules (UFW/iptables) for public access
4. **Port Management**:
   - Checks default port availability
   - Auto-assigns alternative ports if conflicts exist
   - Range: 8000-9000 for applications
5. **PHP Version Selection**:
   - Displays available PHP versions (8.1-8.4)
   - Allows version switching with dependency validation
   - Updates Composer dependencies for selected version
6. **Web Server Deployment Selection**:
   - **php-cli**: PHP Built-in Server (always available)
   - **apache-mod-php**: Apache with mod_php (requires enabling)
   - **apache-fpm**: Apache with PHP-FPM (requires service start)
   - **nginx-fpm**: Nginx with PHP-FPM (requires service start)
7. **Validation & Configuration**:
   - Validates deployment compatibility
   - Configures web server settings
   - Saves configuration to `config/app.env`

**Key Scripts Called**:
- `./scripts/network-manager.sh` - Network and IP management
- `./scripts/php-version-manager.sh` - PHP version handling
- `./scripts/webserver-manager.sh` - Web server configuration
- `./scripts/docker-helper.sh` - Docker service management

#### `make start`
**Purpose**: Application startup with full service orchestration
**Internal Flow**:
1. Calls `./scripts/start-app.sh`
2. **Pre-flight Checks**:
   - Verifies no existing application instances
   - Loads configuration from `config/app.env`
   - Checks for IP address changes since last run
3. **Service Startup**:
   - Starts Docker services (MySQL, PostgreSQL, Redis) via `docker-compose.services.yml`
   - Waits for service health checks to pass
   - Validates database connectivity
4. **Application Deployment**:
   - Deploys based on selected deployment type:
     - **PHP-CLI**: Starts built-in server with configured IP/port
     - **Apache**: Configures virtual hosts and starts/restarts Apache
     - **Nginx**: Configures server blocks and starts/restarts Nginx
   - Creates PID files for process tracking
   - Validates application accessibility

**Service Dependencies**: All applications require MySQL, PostgreSQL, and Redis services to be healthy before application start.

### State Management Commands

#### `make disable`
**Purpose**: Temporarily disable application while preserving configuration
**Internal Flow**:
1. Calls `./scripts/disable-app.sh`
2. Stops application processes (preserves PID files)
3. Disables web server configurations (Apache/Nginx)
4. Keeps Docker services running
5. Maintains all configuration files

#### `make enable`
**Purpose**: Re-enable previously disabled application
**Internal Flow**:
1. Calls `./scripts/enable-app.sh`
2. Re-enables web server configurations
3. Restarts application with existing configuration
4. Validates service connectivity

#### `make stop`
**Purpose**: Complete application shutdown
**Internal Flow**:
1. Calls `./scripts/stop-app.sh`
2. Stops application processes
3. Stops Docker services
4. Cleans up PID files
5. Preserves configuration for future starts

#### `make down` (cleanup)
**Purpose**: Complete environment cleanup
**Internal Flow**:
1. Calls `./scripts/cleanup.sh`
2. Stops all processes and services
3. Removes Docker containers and volumes
4. Deletes configuration files
5. Cleans up temporary files and logs

### Status and Monitoring Commands

#### `make status`
**Purpose**: Comprehensive application status report
**Internal Flow**:
1. Calls `./scripts/status.sh`
2. Reports application process status
3. Shows Docker service health
4. Displays current configuration
5. Network and port status
6. Recent log entries

#### `make php-status` (Simple PHP, Symfony only)
**Purpose**: PHP version and compatibility status
**Internal Flow**:
1. Calls `./scripts/php-version-manager.sh status`
2. Shows installed PHP versions
3. Reports PHP-FPM service status
4. Displays current configuration compatibility

#### `make network-status` (Simple PHP, Symfony only)
**Purpose**: Network configuration and IP change detection
**Internal Flow**:
1. Calls `./scripts/network-manager.sh status`
2. Shows current IP configuration
3. Reports IP change history
4. Validates network accessibility

## Deployment Types Deep Dive

### PHP Built-in Server (php-cli)
- **Command**: `php8.x -S {interface}:{port}`
- **Pros**: Always available, no configuration required
- **Cons**: Development only, single-threaded
- **Use Case**: Development, testing, quick demos

### Apache mod_php
- **Configuration**: Loads PHP as Apache module
- **Requirements**: `sudo a2enmod php8.x`
- **Pros**: High performance, shared memory
- **Cons**: Single PHP version per Apache instance
- **Use Case**: Traditional hosting environments

### Apache PHP-FPM
- **Configuration**: Apache proxy to PHP-FPM service
- **Requirements**: PHP-FPM service running, proxy modules enabled
- **Pros**: Multiple PHP versions, better resource management
- **Cons**: More complex configuration
- **Use Case**: Production environments, multi-version support

### Nginx PHP-FPM
- **Configuration**: Nginx fastcgi_pass to PHP-FPM
- **Requirements**: Nginx, PHP-FPM service running
- **Pros**: High performance, efficient resource usage
- **Cons**: Requires Nginx configuration knowledge
- **Use Case**: High-traffic production environments

## Configuration Management

### Single Source of Truth: `config/app.env`
Each application maintains its configuration in `config/app.env`, which serves as the authoritative source for:
- **Application Settings**: Name, port, network interface, deployment type
- **Database Configuration**: Host, port, database name, credentials (per application)
- **Environment Variables**: Debug mode, environment type, PHP version
- **Deployment Settings**: Apache directory, web server configuration

### Configuration Structure
```bash
# Application Configuration
APP_NAME="Application Name"
APP_PORT=8000
NETWORK_INTERFACE="0.0.0.0"
PHP_VERSION="8.4"
DEPLOYMENT_TYPE="apache-fpm"

# Database Configuration (Standardized Ports)
MYSQL_HOST=localhost
MYSQL_PORT=3307  # Application-specific port
MYSQL_DATABASE=app_specific_db
POSTGRES_HOST=localhost
POSTGRES_PORT=5433  # Application-specific port
REDIS_HOST=localhost
REDIS_PORT=6380  # Application-specific port
```

### Dynamic Configuration Generation
- **Generated During**: `make compile` process
- **Updated When**: Network changes detected, PHP version changes, deployment type changes
- **Persistence**: Survives application restarts, cleared by `make down`
- **Validation**: Automatic consistency checks between configuration and actual services

## Framework-Specific Differences

### Command Variations
| Command | Simple PHP | Symfony | Laravel | CodeIgniter | Slim |
|---------|------------|---------|---------|-------------|------|
| `make test` | phpunit | phpunit | artisan test | phpunit | phpunit |
| `make dev` | N/A | N/A | artisan serve | spark serve | built-in server |
| `make status` | ✅ Extended | ✅ Extended | ✅ Basic | ✅ Basic | ✅ Basic |
| `make php-status` | ✅ Available | ✅ Available | ❌ Not available | ❌ Not available | ❌ Not available |
| `make network-status` | ✅ Available | ✅ Available | ❌ Not available | ❌ Not available | ❌ Not available |
| Framework CLI | N/A | console | artisan | spark | N/A |

### Unique Features by Framework

#### **Simple PHP & Symfony** (Extended Features)
- **`make php-status`**: Detailed PHP version compatibility and service status
- **`make network-status`**: Network configuration and IP change detection
- **Extended Status**: Comprehensive application and service monitoring
- **Advanced Validation**: PHP version consistency checks, web server validation

#### **Laravel** (Framework Integration)
- **`make artisan`**: Direct access to Laravel's Artisan CLI
- **`make dev`**: Laravel development server with framework-specific optimizations
- **Framework Testing**: `php artisan test` instead of direct PHPUnit
- **Dependency Management**: Laravel-specific Composer optimizations

#### **CodeIgniter** (Spark CLI Integration)
- **`make spark`**: Access to CodeIgniter's Spark CLI tool
- **`make dev`**: CodeIgniter development server
- **Framework Testing**: Standard PHPUnit with CodeIgniter bootstrap
- **Database Integration**: CodeIgniter-specific database configuration

#### **Slim Framework** (Minimal Configuration)
- **Lightweight Setup**: Minimal dependencies and configuration
- **Standard Testing**: Direct PHPUnit integration
- **Flexible Deployment**: Supports all deployment types with minimal overhead
- **Twig Integration**: Template engine configuration and optimization

## Detailed Workflow Analysis

### Complete Setup → Compile → Start Workflow

#### **Phase 1: `make setup` - System Validation**
```bash
# Internal Script Flow: ./scripts/check-system.sh
1. fix_script_permissions()     # Ensures all scripts are executable
2. check_php_versions()         # Validates PHP 8.1-8.4 availability
3. install_php_extensions()     # Optional: installs missing PHP extensions
4. check_web_servers()          # Validates Apache, Nginx, PHP-FPM availability
5. check_docker()               # Validates Docker and Docker Compose
6. start_docker_services()      # Starts MySQL, PostgreSQL, Redis containers
7. show_summary()               # Reports system readiness
```

**Key Validations**:
- **PHP Versions**: Checks for PHP 8.1, 8.2, 8.3, 8.4 availability
- **Web Servers**: Apache (mod_php, PHP-FPM), Nginx (PHP-FPM), PHP CLI
- **Docker Services**: Container health checks, port availability
- **System Dependencies**: Required packages and permissions

#### **Phase 2: `make compile` - Application Configuration**
```bash
# Internal Script Flow: ./scripts/compile-app.sh
1. check_application_state()    # Ensures no running instances
2. network_configuration()      # IP detection, interface selection, firewall setup
3. port_management()            # Port availability, conflict resolution
4. php_version_selection()      # PHP version choice, dependency validation
5. deployment_type_selection()  # Web server choice, service validation
6. configuration_generation()   # Creates config/app.env with all settings
7. validation_checks()          # Ensures configuration consistency
```

**Configuration Generation Process**:
- **Network Detection**: Automatic IP detection, interface binding options
- **Port Allocation**: Uses application-specific ports, validates availability
- **PHP Integration**: Version-specific configuration, extension validation
- **Web Server Setup**: Deployment-specific configuration, service validation

#### **Phase 3: `make start` - Application Deployment**
```bash
# Internal Script Flow: ./scripts/start-app.sh
1. load_configuration()         # Reads config/app.env settings
2. validate_services()          # Checks Docker container health
3. network_validation()         # Validates IP changes, updates configuration
4. deployment_execution()       # Starts application based on deployment type
5. health_checks()              # Validates application accessibility
6. process_tracking()           # Creates PID files, monitors processes
```

**Deployment Types Execution**:
- **PHP-CLI**: `php8.x -S {interface}:{port} -t public/`
- **Apache mod_php**: Virtual host configuration, Apache restart
- **Apache PHP-FPM**: Proxy configuration, PHP-FPM service start
- **Nginx PHP-FPM**: Server block configuration, Nginx restart

### Advanced Configuration Management

#### **Dynamic Configuration Files**
- **Primary Source**: `config/app.env` (single source of truth per application)
- **Generation**: Automatically created during `make compile`
- **Updates**: Dynamic updates on network/configuration changes
- **Validation**: Consistency checks between config and running services
- **Persistence**: Survives restarts, cleared only by `make down`

#### **Network State Management**
- **IP Change Detection**: Automatic detection via `./scripts/network-manager.sh`
- **Dynamic Updates**: Configuration updates without service restart
- **Firewall Integration**: Automatic UFW/iptables rule management
- **Interface Options**: localhost (127.0.0.1), public IP, all interfaces (0.0.0.0)

#### **Port Conflict Resolution**
- **Automatic Detection**: Scans for port availability before assignment
- **Conflict Resolution**: Auto-assigns alternative ports in 8000-9000 range
- **Service Isolation**: Each application uses dedicated port ranges
- **Validation**: Continuous monitoring of port availability

## Complete Command Reference

### Primary Operations (Available in All Applications)

#### **`make setup`** - System Requirements and Service Initialization
**Purpose**: Validates system requirements and starts Docker services
**Script**: `./scripts/check-system.sh`
**Duration**: 30-60 seconds
**Requirements**: Docker, Docker Compose
**Output**: System compatibility report, Docker service status

**Internal Process**:
1. **Permission Fix**: Makes all scripts executable (`chmod +x scripts/*`)
2. **PHP Validation**: Checks PHP 8.1-8.4 availability, reports missing versions
3. **Extension Check**: Validates required PHP extensions (optional installation)
4. **Web Server Check**: Tests Apache, Nginx, PHP-FPM availability
5. **Docker Validation**: Verifies Docker daemon, Docker Compose functionality
6. **Service Startup**: Launches MySQL, PostgreSQL, Redis containers
7. **Health Verification**: Waits for all services to reach healthy state

#### **`make compile`** - Application Configuration and Deployment Preparation
**Purpose**: Configures application for specific deployment environment
**Script**: `./scripts/compile-app.sh`
**Duration**: 2-5 minutes (includes user interaction)
**Requirements**: Running Docker services
**Output**: `config/app.env` file with complete configuration

**Interactive Process**:
1. **Application State Check**: Ensures no running instances
2. **Network Configuration**:
   - IP detection and interface selection
   - Firewall rule configuration for public access
   - Port availability validation
3. **PHP Version Selection**:
   - Available version display (8.1-8.4)
   - Dependency compatibility validation
   - Composer dependency updates
4. **Deployment Type Selection**:
   - php-cli (always available)
   - apache-mod-php (requires mod_php enabled)
   - apache-fpm (requires PHP-FPM service)
   - nginx-fpm (requires Nginx and PHP-FPM)
5. **Configuration Generation**: Creates `config/app.env` with all settings
6. **Validation**: Ensures configuration consistency and service compatibility

#### **`make start`** - Application Deployment and Startup
**Purpose**: Deploys and starts the application with configured settings
**Script**: `./scripts/start-app.sh`
**Duration**: 30-90 seconds
**Requirements**: Completed `make compile`, healthy Docker services
**Output**: Running application accessible via configured network interface

**Deployment Process**:
1. **Configuration Loading**: Reads `config/app.env` settings
2. **Service Validation**: Verifies Docker container health
3. **Network Updates**: Detects IP changes, updates configuration if needed
4. **Application Deployment**:
   - **PHP-CLI**: Starts built-in server with specified interface/port
   - **Apache**: Configures virtual host, enables site, restarts Apache
   - **Nginx**: Configures server block, enables site, restarts Nginx
5. **Process Tracking**: Creates PID files for process management
6. **Health Validation**: Verifies application responds to HTTP requests

### State Management Operations

#### **`make disable`** - Temporary Application Shutdown
**Purpose**: Stops application while preserving configuration and Docker services
**Script**: `./scripts/disable-app.sh`
**Use Case**: Temporary maintenance, testing other applications

#### **`make enable`** - Application Restart
**Purpose**: Restarts previously disabled application with existing configuration
**Script**: `./scripts/enable-app.sh`
**Use Case**: Resume after maintenance, quick restart

#### **`make stop`** - Complete Application Shutdown
**Purpose**: Stops application and Docker services, preserves configuration
**Script**: `./scripts/stop-app.sh`
**Use Case**: End of development session, resource cleanup

#### **`make down`** - Complete Environment Cleanup
**Purpose**: Removes all containers, volumes, and configuration files
**Script**: `./scripts/cleanup.sh`
**Use Case**: Fresh start, complete reset, troubleshooting

### Monitoring and Status Operations

#### **`make status`** - Application Status Report
**Purpose**: Comprehensive status of application and services
**Script**: `./scripts/status.sh`
**Output**: Process status, configuration, network info, recent logs

#### **`make php-status`** (Simple PHP, Symfony only)
**Purpose**: PHP version compatibility and service status
**Script**: `./scripts/php-version-manager.sh status`
**Output**: Installed PHP versions, PHP-FPM status, compatibility report

#### **`make network-status`** (Simple PHP, Symfony only)
**Purpose**: Network configuration and change detection
**Script**: `./scripts/network-manager.sh status`
**Output**: Current IP, interface binding, firewall rules, change history

## Error Handling and Troubleshooting

### Common Issues and Resolutions

#### **1. Port Conflicts**
**Symptoms**: "Port already in use", container startup failures
**Resolution**:
- Automatic port reassignment within 8000-9000 range
- Manual port specification in `config/app.env`
- Service conflict detection and resolution

#### **2. Docker Service Issues**
**Symptoms**: "Container unhealthy", database connection failures
**Resolution**:
- `make setup` to restart services
- `docker ps` to check container status
- `docker logs <container_name>` for detailed error information

#### **3. PHP Version Conflicts**
**Symptoms**: Extension missing, version incompatibility
**Resolution**:
- Automatic validation during `make compile`
- Version-specific dependency management
- Extension installation prompts

#### **4. Network Configuration Issues**
**Symptoms**: Application not accessible, IP change detection
**Resolution**:
- Automatic IP change detection and configuration updates
- Firewall rule management for public access
- Interface binding validation

#### **5. Permission Issues**
**Symptoms**: Script execution failures, Apache/Nginx configuration errors
**Resolution**:
- Automatic script permission fixing
- Sudo prompts for system-level changes
- Directory permission management for web servers

### Validation Layers and Safety Checks

#### **System Level Validation**
1. **PHP Availability**: Validates all required PHP versions (8.1-8.4)
2. **Web Server Compatibility**: Checks Apache, Nginx, PHP-FPM availability
3. **Docker Functionality**: Verifies Docker daemon and Docker Compose
4. **Port Availability**: Scans for conflicts before assignment

#### **Service Level Validation**
1. **Container Health**: Monitors MySQL, PostgreSQL, Redis health status
2. **Database Connectivity**: Tests actual database connections
3. **Service Dependencies**: Ensures all required services are running
4. **Network Accessibility**: Validates application reachability

#### **Configuration Level Validation**
1. **PHP Version Consistency**: Ensures PHP version matches deployment type
2. **Web Server Configuration**: Validates deployment type compatibility
3. **Network Configuration**: Checks interface binding and firewall rules
4. **Application Readiness**: Verifies application startup and response

## Best Practices and Production Considerations

### Development Workflow
```bash
# Initial Setup (once per environment)
make setup                    # Validate system, start Docker services

# Development Cycle
make compile                  # Configure for development (php-cli recommended)
make start                    # Start application
# ... development work ...
make status                   # Check application health
make stop                     # Clean shutdown when done

# Quick Restart
make disable                  # Temporary stop
make enable                   # Quick restart with existing config

# Fresh Start
make down                     # Complete cleanup
make setup                    # Reinitialize environment
```

### Production Deployment Workflow
```bash
# Production Environment Setup
make setup                    # Validate production environment
make compile                  # Configure for production (Apache/Nginx FPM recommended)
make start                    # Deploy with full service stack
make status                   # Validate deployment health

# Production Monitoring
make status                   # Regular health checks
make php-status              # PHP service monitoring (Simple PHP, Symfony)
make network-status          # Network configuration monitoring (Simple PHP, Symfony)

# Production Maintenance
make disable                  # Maintenance mode (preserves config)
# ... perform maintenance ...
make enable                   # Resume operations
```

### Multi-Environment Management
- **Application Isolation**: Each application maintains independent configuration
- **No Cross-Dependencies**: Applications can run simultaneously without conflicts
- **Isolated Networks**: Application-specific Docker networks with unique subnets
- **Port Allocation**: Unique port ranges prevent conflicts (3307-3311, 5433-5437, 6380-6384)
- **Configuration Management**: Single source of truth per application (`config/app.env`)

### Performance Optimization

#### **Deployment Type Selection Guidelines**
- **Development**: `php-cli` (fastest setup, single-threaded, development only)
- **Testing**: `apache-fpm` (production-like, multi-threaded, better performance)
- **Production**: `nginx-fpm` (highest performance, efficient resource usage)
- **Legacy Systems**: `apache-mod-php` (traditional hosting, single PHP version)

#### **Resource Management**
- **Docker Services**: Shared across applications (MySQL, PostgreSQL, Redis)
- **Application Processes**: Independent per application
- **Port Allocation**: Efficient use of port ranges
- **Network Isolation**: Prevents interference between applications

### Security Considerations

#### **Firewall Management**
- **Automatic Rules**: UFW/iptables rules created for public access
- **Port-Specific**: Only required ports opened, no blanket access
- **Cleanup**: Automatic rule removal on application shutdown
- **Validation**: Firewall status checks during deployment

#### **Service Isolation**
- **Docker Networks**: Application-specific networks with unique subnets
- **Database Isolation**: Separate database instances per application
- **Credential Management**: Application-specific database credentials
- **Process Isolation**: Independent application processes

#### **Configuration Security**
- **Environment-Specific**: Settings tailored to deployment environment
- **No Hardcoded Credentials**: All credentials in environment variables
- **Secure Defaults**: Production-ready defaults for security settings
- **Validation**: Configuration consistency and security checks

## Troubleshooting Guide

### Quick Diagnostic Commands
```bash
# Check overall system status
make status

# Verify Docker services
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Check application logs
tail -f logs/application.log

# Verify network configuration
make network-status  # (Simple PHP, Symfony only)

# Check PHP configuration
make php-status      # (Simple PHP, Symfony only)
```

### Common Problem Resolution

#### **Application Won't Start**
1. **Check Docker Services**: `docker ps` - ensure all containers healthy
2. **Verify Configuration**: `cat config/app.env` - check settings
3. **Check Port Conflicts**: `netstat -tuln | grep <port>` - verify port availability
4. **Restart Services**: `make stop && make setup && make start`

#### **Database Connection Issues**
1. **Verify Container Health**: `docker ps` - check database container status
2. **Check Port Configuration**: Ensure `config/app.env` matches Docker ports
3. **Test Connectivity**: `telnet localhost <db_port>`
4. **Restart Database Services**: `make stop && make setup`

#### **Network Access Issues**
1. **Check IP Configuration**: Verify interface binding in `config/app.env`
2. **Firewall Rules**: Ensure ports are open for public access
3. **IP Change Detection**: `make network-status` for IP change history
4. **Reconfigure Network**: `make compile` to update network settings

#### **PHP Version Issues**
1. **Check Available Versions**: `make php-status` (Simple PHP, Symfony)
2. **Verify Installation**: `php8.x --version` for each version
3. **Extension Validation**: `php8.x -m` to check loaded extensions
4. **Reconfigure PHP**: `make compile` to select different version

## Summary and Architecture Overview

### System Architecture
The APM PHP Examples project implements a **microservice-like architecture** where each PHP framework application operates as an independent service with:

- **Isolated Configuration**: Each application has its own `config/app.env`
- **Dedicated Ports**: Unique port allocation prevents conflicts
- **Independent Deployment**: Applications can be deployed separately
- **Shared Infrastructure**: Common Docker services (MySQL, PostgreSQL, Redis)
- **Unified Management**: Consistent Makefile interface across all applications

### Key Design Principles
1. **Single Entry Point**: All operations through `make` commands
2. **Configuration Consistency**: Standardized environment variable management
3. **Service Isolation**: Independent application deployment and operation
4. **Dynamic Adaptation**: Automatic configuration updates for network changes
5. **Production Readiness**: Comprehensive validation and error handling

### Operational Excellence
- **Comprehensive Validation**: Multi-layer validation ensures reliable deployment
- **Automatic Recovery**: Self-healing configuration and service management
- **Monitoring Integration**: Built-in status reporting and health checks
- **Documentation**: Complete operational documentation and troubleshooting guides
- **Scalability**: Pattern established for adding new framework applications

This system eliminates manual command execution and provides a **production-grade automation framework** for PHP application deployment and management across multiple frameworks while maintaining complete isolation and consistency.

## Security Considerations

### Firewall Management
- Automatic UFW/iptables rule creation for public access
- Port-specific rules (no blanket access)
- Cleanup on application shutdown

### Service Isolation
- Application-specific Docker networks
- Isolated database instances
- Unique credentials per application

### Configuration Security
- Environment-specific settings
- No hardcoded credentials in scripts
- Secure defaults for production deployments

---

**Note**: This system is designed to eliminate manual command execution. All operations should be performed through the documented `make` commands to ensure consistency and proper error handling across all environments.
