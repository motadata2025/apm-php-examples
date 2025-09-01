#!/bin/bash

# Production Scaling Configuration Script
# APM PHP Examples - Slim Framework Application
# Optimizes for 200+ concurrent users

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration files
CONFIG_DIR="config"
APP_CONFIG_FILE="$CONFIG_DIR/app.env"

# Function to load configuration
load_configuration() {
    # Use default values for CodeIgniter production scaling
    # CodeIgniter uses config/app.env for configuration, not shell variables
    echo -e "${BLUE}Using default configuration for CodeIgniter production scaling${NC}"

    # Set default values for production scaling
    PHP_VERSION="8.4"
    DEPLOYMENT_TYPE="apache-fpm"
    APP_PORT="8002"

    # Note: CodeIgniter configuration is handled through config/app.env
    # No need to source shell variables
}

# Function to configure PHP-FPM for production scaling
configure_php_fpm_scaling() {
    echo -e "\n${PURPLE}🐘 Configuring PHP-FPM for Production Scaling${NC}"
    
    local fpm_pool_file="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
    local backup_file="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf.backup"
    
    # Create backup
    if [ ! -f "$backup_file" ]; then
        echo -e "  ${BLUE}Creating backup of original PHP-FPM configuration...${NC}"
        sudo cp "$fpm_pool_file" "$backup_file"
    fi
    
    echo -e "  ${BLUE}Applying production PHP-FPM settings for 200+ concurrent users...${NC}"
    
    # Apply production settings
    sudo tee "$fpm_pool_file" > /dev/null << EOF
; Production PHP-FPM Pool Configuration
; Optimized for 200+ concurrent users
[www]

; Unix user/group of processes
user = www-data
group = www-data

; The address on which to accept FastCGI requests
listen = /run/php/php${PHP_VERSION}-fpm.sock

; Set listen(2) backlog
listen.backlog = 511

; Set permissions for unix socket
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Choose how the process manager will control the number of child processes
pm = dynamic

; The number of child processes to be created when pm is set to 'static' and the
; maximum number of child processes when pm is set to 'dynamic' or 'ondemand'
pm.max_children = 50

; The number of child processes created on startup
pm.start_servers = 10

; The desired minimum number of idle server processes
pm.min_spare_servers = 5

; The desired maximum number of idle server processes
pm.max_spare_servers = 15

; The number of seconds after which an idle process will be killed
pm.process_idle_timeout = 30s

; The number of requests each child process should execute before respawning
pm.max_requests = 1000

; The URI to view the FPM status page
pm.status_path = /fpm-status

; The ping URI to call the monitoring page of FPM
ping.path = /fpm-ping

; This directive may be used to customize the response of a ping request
ping.response = pong

; The access log file
access.log = /var/log/php${PHP_VERSION}-fpm.access.log

; Redirect worker stdout and stderr into main error log
catch_workers_output = yes

; Clear environment in FPM workers
clear_env = no

; Limits the extensions of the main script FPM will allow to parse
security.limit_extensions = .php .php3 .php4 .php5 .php7

; Production PHP settings
php_admin_value[error_log] = /var/log/php${PHP_VERSION}-fpm.error.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path]    = /var/lib/php/sessions
php_value[soap.wsdl_cache_dir]  = /var/lib/php/wsdlcache

; Memory and execution limits for production
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 60
php_admin_value[max_input_time] = 60
php_admin_value[post_max_size] = 50M
php_admin_value[upload_max_filesize] = 50M
php_admin_value[max_file_uploads] = 20

; OPcache settings for production
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.enable_cli] = 0
php_admin_value[opcache.memory_consumption] = 256
php_admin_value[opcache.interned_strings_buffer] = 16
php_admin_value[opcache.max_accelerated_files] = 10000
php_admin_value[opcache.validate_timestamps] = 0
php_admin_value[opcache.save_comments] = 0
php_admin_value[opcache.fast_shutdown] = 1
EOF

    echo -e "  ${GREEN}✅ PHP-FPM production configuration applied${NC}"
    
    # Restart PHP-FPM
    if sudo systemctl restart "php${PHP_VERSION}-fpm"; then
        echo -e "  ${GREEN}✅ PHP-FPM restarted with new configuration${NC}"
    else
        echo -e "  ${RED}❌ Failed to restart PHP-FPM${NC}"
        return 1
    fi
}

# Function to configure Apache for production scaling
configure_apache_scaling() {
    echo -e "\n${PURPLE}🌐 Configuring Apache for Production Scaling${NC}"
    
    local apache_conf="/etc/apache2/conf-available/production-scaling.conf"
    
    echo -e "  ${BLUE}Creating Apache production configuration...${NC}"
    
    sudo tee "$apache_conf" > /dev/null << EOF
# Apache Production Scaling Configuration
# Optimized for 200+ concurrent users

# MPM Event Configuration (recommended for high concurrency)
<IfModule mpm_event_module>
    ServerLimit             4
    MaxRequestWorkers       400
    ThreadsPerChild         100
    ThreadLimit             100
    StartServers            2
    MinSpareThreads         25
    MaxSpareThreads         75
    ListenBacklog           511
    AsyncRequestWorkerFactor 2
</IfModule>

# MPM Prefork Configuration (for mod_php)
<IfModule mpm_prefork_module>
    StartServers            8
    MinSpareServers         5
    MaxSpareServers         20
    MaxRequestWorkers       150
    MaxConnectionsPerChild  1000
    ServerLimit             150
</IfModule>

# Connection and timeout settings
Timeout 60
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

# Security and performance headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Compression
<IfModule mod_deflate.c>
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.pdf$ no-gzip dont-vary
    BrowserMatch ^Mozilla/4 gzip-only-text/html
    BrowserMatch ^Mozilla/4\.0[678] no-gzip
    BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
</IfModule>

# Cache control for static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/ico "access plus 1 month"
    ExpiresByType image/icon "access plus 1 month"
    ExpiresByType text/plain "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
</IfModule>

# Disable server signature for security
ServerTokens Prod
ServerSignature Off

# Log format for performance monitoring
LogFormat "%h %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\" %D" combined_with_time
EOF

    # Enable the configuration
    if sudo a2enconf production-scaling 2>/dev/null; then
        echo -e "  ${GREEN}✅ Apache production configuration enabled${NC}"
    else
        echo -e "  ${YELLOW}⚠️  Apache production configuration already enabled${NC}"
    fi
    
    # Enable required modules
    local modules=("headers" "expires" "deflate" "rewrite")
    for module in "${modules[@]}"; do
        if sudo a2enmod "$module" 2>/dev/null; then
            echo -e "  ${GREEN}✅ Module $module enabled${NC}"
        else
            echo -e "  ${YELLOW}⚠️  Module $module already enabled${NC}"
        fi
    done
}

# Function to configure database connection pooling
configure_database_scaling() {
    echo -e "\n${PURPLE}🗄️ Configuring Database Connection Scaling${NC}"
    
    local env_file=".env"
    local env_backup=".env.backup"
    
    # Create backup
    if [ ! -f "$env_backup" ]; then
        cp "$env_file" "$env_backup"
        echo -e "  ${BLUE}Created backup of .env file${NC}"
    fi
    
    echo -e "  ${BLUE}Adding production database connection settings...${NC}"
    
    # Add production database settings
    cat >> "$env_file" << EOF

# Production Database Connection Scaling
# Optimized for 200+ concurrent users

# MySQL Connection Pool Settings
MYSQL_MAX_CONNECTIONS=100
MYSQL_CONNECT_TIMEOUT=10
MYSQL_WAIT_TIMEOUT=600
MYSQL_INTERACTIVE_TIMEOUT=600

# PostgreSQL Connection Pool Settings
POSTGRES_MAX_CONNECTIONS=200
POSTGRES_SHARED_BUFFERS=256MB
POSTGRES_EFFECTIVE_CACHE_SIZE=1GB
POSTGRES_WORK_MEM=4MB

# Redis Connection Pool Settings
REDIS_MAX_CONNECTIONS=50
REDIS_TIMEOUT=5
REDIS_TCP_KEEPALIVE=60

# Application Performance Settings
APP_CACHE_DRIVER=redis
APP_SESSION_DRIVER=redis
APP_QUEUE_DRIVER=redis
APP_LOG_LEVEL=warning
EOF

    echo -e "  ${GREEN}✅ Database scaling configuration added to .env${NC}"
}

# Function to create monitoring and health check endpoints
create_monitoring_endpoints() {
    echo -e "\n${PURPLE}📊 Creating Production Monitoring Endpoints${NC}"
    
    local monitoring_file="public/monitoring.php"
    
    cat > "$monitoring_file" << 'EOF'
<?php
// Production Monitoring Endpoints
// For load balancers and monitoring systems

header('Content-Type: application/json');

$endpoint = $_GET['endpoint'] ?? 'status';

switch ($endpoint) {
    case 'health':
        // Detailed health check
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'uptime' => $_SERVER['REQUEST_TIME'] - $_SERVER['REQUEST_TIME_FLOAT'],
            'load_average' => sys_getloadavg(),
            'services' => []
        ];
        
        // Check Redis
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6382);
            $health['services']['redis'] = 'healthy';
            $redis->close();
        } catch (Exception $e) {
            $health['services']['redis'] = 'unhealthy';
        }
        
        // Check MySQL
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3309', 'root', 'rootpassword');
            $health['services']['mysql'] = 'healthy';
        } catch (Exception $e) {
            $health['services']['mysql'] = 'unhealthy';
        }
        
        echo json_encode($health);
        break;
        
    case 'metrics':
        // Performance metrics for monitoring
        $metrics = [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'opcache_enabled' => function_exists('opcache_get_status'),
            'request_time' => $_SERVER['REQUEST_TIME_FLOAT'],
            'server_load' => sys_getloadavg()[0]
        ];
        
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            $metrics['opcache_hit_rate'] = round($opcache['opcache_statistics']['opcache_hit_rate'], 2);
            $metrics['opcache_memory_usage'] = round($opcache['memory_usage']['used_memory'] / 1024 / 1024, 2);
        }
        
        echo json_encode($metrics);
        break;
        
    case 'ready':
        // Simple readiness check for load balancers
        echo json_encode(['status' => 'ready', 'timestamp' => time()]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Unknown endpoint']);
}
EOF

    echo -e "  ${GREEN}✅ Monitoring endpoints created at /monitoring.php${NC}"
    echo -e "  ${BLUE}Available endpoints:${NC}"
    echo -e "    ${YELLOW}?endpoint=health${NC}  - Detailed health check"
    echo -e "    ${YELLOW}?endpoint=metrics${NC} - Performance metrics"
    echo -e "    ${YELLOW}?endpoint=ready${NC}   - Simple readiness check"
}

# Function to show scaling summary
show_scaling_summary() {
    echo -e "\n${PURPLE}📊 Production Scaling Configuration Complete${NC}"
    echo -e "=================================================="
    
    echo -e "\n${GREEN}✅ Scaling optimizations applied for 200+ concurrent users!${NC}"
    
    echo -e "\n${BLUE}PHP-FPM Configuration:${NC}"
    echo -e "  ${GREEN}Max Children: 50${NC}"
    echo -e "  ${GREEN}Start Servers: 10${NC}"
    echo -e "  ${GREEN}Memory Limit: 256MB per process${NC}"
    echo -e "  ${GREEN}OPcache: Enabled and optimized${NC}"
    
    echo -e "\n${BLUE}Apache Configuration:${NC}"
    echo -e "  ${GREEN}Max Request Workers: 400 (Event) / 150 (Prefork)${NC}"
    echo -e "  ${GREEN}Compression: Enabled${NC}"
    echo -e "  ${GREEN}Caching: Static assets cached${NC}"
    echo -e "  ${GREEN}Security Headers: Applied${NC}"
    
    echo -e "\n${BLUE}Database Scaling:${NC}"
    echo -e "  ${GREEN}MySQL Max Connections: 100${NC}"
    echo -e "  ${GREEN}PostgreSQL Max Connections: 200${NC}"
    echo -e "  ${GREEN}Redis Connection Pool: 50${NC}"
    
    echo -e "\n${BLUE}Monitoring:${NC}"
    echo -e "  ${GREEN}Health Check: /monitoring.php?endpoint=health${NC}"
    echo -e "  ${GREEN}Metrics: /monitoring.php?endpoint=metrics${NC}"
    echo -e "  ${GREEN}Readiness: /monitoring.php?endpoint=ready${NC}"
    
    echo -e "\n${YELLOW}⚠️  Important Notes:${NC}"
    echo -e "  ${BLUE}• Restart Apache to apply all changes: sudo systemctl restart apache2${NC}"
    echo -e "  ${BLUE}• Monitor server resources under load${NC}"
    echo -e "  ${BLUE}• Adjust settings based on your server specifications${NC}"
    echo -e "  ${BLUE}• Consider using a load balancer for multiple servers${NC}"
}

# Main execution
main() {
    echo -e "${BLUE}🚀 Production Scaling Configuration${NC}"
    echo -e "===================================="
    
    echo -e "\n${YELLOW}⚠️  This will apply production optimizations for 200+ concurrent users${NC}"
    read -t 15 -p "Continue with production scaling configuration? (y/n): " confirm || confirm="n"
    
    if [[ "$confirm" =~ ^[Yy]$ ]]; then
        load_configuration
        
        case "$DEPLOYMENT_TYPE" in
            "apache-fpm")
                configure_php_fpm_scaling
                configure_apache_scaling
                ;;
            "apache-mod-php")
                configure_apache_scaling
                ;;
            "php-cli")
                echo -e "  ${YELLOW}⚠️  PHP built-in server is not recommended for production${NC}"
                echo -e "  ${BLUE}Consider using Apache or Nginx for production deployment${NC}"
                ;;
            *)
                echo -e "  ${YELLOW}⚠️  Unknown deployment type: $DEPLOYMENT_TYPE${NC}"
                ;;
        esac
        
        configure_database_scaling
        create_monitoring_endpoints
        show_scaling_summary
    else
        echo -e "\n${YELLOW}⚠️  Production scaling configuration cancelled${NC}"
    fi
}

# Execute main function
main "$@"
