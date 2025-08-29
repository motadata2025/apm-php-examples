#!/bin/bash

# APM PHP Examples - Deployment Configuration Script
# This script configures the deployment environment based on user preferences

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration file path
CONFIG_FILE="config/deployment.env"
DOCKER_COMPOSE_TEMPLATE="docker-compose.template.yml"
DOCKER_COMPOSE_FILE="docker-compose.yml"

echo -e "${BLUE}🚀 APM PHP Examples - Deployment Configuration${NC}"
echo "=================================================="

# Function to get user input with default value
get_input() {
    local prompt="$1"
    local default="$2"
    local var_name="$3"
    
    echo -e "${YELLOW}${prompt}${NC}"
    if [ -n "$default" ]; then
        echo -e "Press Enter for default: ${GREEN}${default}${NC}"
    fi
    read -r input
    
    if [ -z "$input" ]; then
        input="$default"
    fi
    
    eval "$var_name='$input'"
}

# Function to check if port is available
check_port() {
    local port=$1
    if command -v netstat >/dev/null 2>&1; then
        ! netstat -tuln | grep -q ":$port "
    elif command -v ss >/dev/null 2>&1; then
        ! ss -tuln | grep -q ":$port "
    else
        # Fallback: assume port is available
        true
    fi
}

# Function to find available port
find_available_port() {
    local start_port=$1
    local end_port=$2
    
    for port in $(seq $start_port $end_port); do
        if check_port $port; then
            echo $port
            return
        fi
    done
    
    echo "No available ports in range $start_port-$end_port" >&2
    exit 1
}

# Get network interface configuration
echo -e "\n${BLUE}Network Configuration${NC}"
echo "====================="
echo "Detecting your network interface IP..."
./scripts/detect-network-ip.sh
source config/deployment.env
NETWORK_INTERFACE=$(grep "^NETWORK_INTERFACE=" config/deployment.env | cut -d= -f2)

# Get PHP version
echo -e "\n${BLUE}PHP Version Configuration${NC}"
echo "========================="
echo "Available PHP versions: 8.1, 8.2, 8.3, 8.4"
get_input "Enter PHP version:" "8.4" "PHP_VERSION"

# Validate PHP version
if [[ ! "$PHP_VERSION" =~ ^8\.[1-4]$ ]]; then
    echo -e "${RED}Error: Invalid PHP version. Supported versions: 8.1, 8.2, 8.3, 8.4${NC}"
    exit 1
fi

# Get deployment type
echo -e "\n${BLUE}Deployment Type Configuration${NC}"
echo "============================="
echo "Applications will run on your local machine with the following options:"
echo "1. apache-mod-php (Local Apache with mod_php)"
echo "2. apache-fpm (Local Apache with PHP-FPM)"
echo "3. nginx-fpm (Local Nginx with PHP-FPM)"
echo "4. php-cli (PHP CLI built-in server)"
echo "5. custom (Custom local setup)"
get_input "Enter deployment type (1-5 or full name):" "4" "DEPLOYMENT_TYPE_INPUT"

# Convert numeric input to deployment type
case "$DEPLOYMENT_TYPE_INPUT" in
    1|apache-mod-php) DEPLOYMENT_TYPE="local-apache-mod-php" ;;
    2|apache-fpm) DEPLOYMENT_TYPE="local-apache-fpm" ;;
    3|nginx-fpm) DEPLOYMENT_TYPE="local-nginx-fpm" ;;
    4|php-cli) DEPLOYMENT_TYPE="local-php-cli" ;;
    5|custom) DEPLOYMENT_TYPE="local-custom" ;;
    *) echo -e "${RED}Error: Invalid deployment type${NC}"; exit 1 ;;
esac

# Get port configuration
echo -e "\n${BLUE}Port Configuration${NC}"
echo "=================="
get_input "Enter starting port range:" "8080" "PORT_RANGE_START"
get_input "Enter ending port range:" "8099" "PORT_RANGE_END"

# Find available ports for each application
echo -e "\n${YELLOW}Finding available ports...${NC}"
SIMPLE_PHP_PORT=$(find_available_port $PORT_RANGE_START $PORT_RANGE_END)
LARAVEL_PORT=$(find_available_port $((SIMPLE_PHP_PORT + 1)) $PORT_RANGE_END)
SYMFONY_PORT=$(find_available_port $((LARAVEL_PORT + 1)) $PORT_RANGE_END)
SLIM_PORT=$(find_available_port $((SYMFONY_PORT + 1)) $PORT_RANGE_END)
CODEIGNITER_PORT=$(find_available_port $((SLIM_PORT + 1)) $PORT_RANGE_END)

echo -e "${GREEN}Assigned ports:${NC}"
echo "  Simple PHP: $SIMPLE_PHP_PORT"
echo "  Laravel: $LARAVEL_PORT"
echo "  Symfony: $SYMFONY_PORT"
echo "  Slim Framework: $SLIM_PORT"
echo "  CodeIgniter: $CODEIGNITER_PORT"

# Get environment configuration
echo -e "\n${BLUE}Environment Configuration${NC}"
echo "========================="
get_input "Enter environment (development/production):" "production" "APP_ENV"
get_input "Enable debug mode? (true/false):" "false" "APP_DEBUG"

# Get SSL configuration
echo -e "\n${BLUE}SSL Configuration${NC}"
echo "================="
get_input "Enable SSL? (true/false):" "false" "SSL_ENABLED"

if [ "$SSL_ENABLED" = "true" ]; then
    get_input "SSL certificate path:" "/etc/ssl/certs" "SSL_CERT_PATH"
    get_input "SSL private key path:" "/etc/ssl/private" "SSL_KEY_PATH"
fi

# Write configuration to file
echo -e "\n${YELLOW}Writing configuration to $CONFIG_FILE...${NC}"

cat > "$CONFIG_FILE" << EOF
# APM PHP Examples - Deployment Configuration
# Generated on $(date)

# Network Configuration
NETWORK_INTERFACE=$NETWORK_INTERFACE

# Port Configuration
SIMPLE_PHP_PORT=$SIMPLE_PHP_PORT
LARAVEL_PORT=$LARAVEL_PORT
SYMFONY_PORT=$SYMFONY_PORT
SLIM_PORT=$SLIM_PORT
CODEIGNITER_PORT=$CODEIGNITER_PORT
PORT_RANGE_START=$PORT_RANGE_START
PORT_RANGE_END=$PORT_RANGE_END

# PHP Version Configuration
PHP_VERSION=$PHP_VERSION

# Deployment Type Configuration
DEPLOYMENT_TYPE=$DEPLOYMENT_TYPE

# Environment Configuration
APP_ENV=$APP_ENV
APP_DEBUG=$APP_DEBUG

# SSL Configuration
SSL_ENABLED=$SSL_ENABLED
EOF

if [ "$SSL_ENABLED" = "true" ]; then
    cat >> "$CONFIG_FILE" << EOF
SSL_CERT_PATH=$SSL_CERT_PATH
SSL_KEY_PATH=$SSL_KEY_PATH
EOF
fi

# Add remaining default configurations
cat >> "$CONFIG_FILE" << EOF

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
POSTGRES_HOST=postgres
POSTGRES_PORT=5432

REDIS_HOST=redis
REDIS_PORT=6379

# Performance Configuration
OPCACHE_ENABLED=true
REDIS_CACHE_ENABLED=true
GZIP_COMPRESSION_ENABLED=true

# Security Configuration
SECURITY_HEADERS_ENABLED=true
RATE_LIMITING_ENABLED=true
RATE_LIMIT_REQUESTS_PER_MINUTE=60

# Monitoring Configuration
MONITORING_ENABLED=true
HEALTH_CHECK_INTERVAL=30

# Logging Configuration
LOG_LEVEL=info
LOG_RETENTION_DAYS=30
EOF

echo -e "${GREEN}✅ Configuration saved to $CONFIG_FILE${NC}"

# Generate Docker Compose file based on configuration
echo -e "\n${YELLOW}Generating Docker Compose configuration...${NC}"
./scripts/generate-docker-compose.sh

echo -e "\n${GREEN}🎉 Deployment configuration completed!${NC}"
echo -e "${BLUE}Next steps:${NC}"
echo "1. Review the configuration in $CONFIG_FILE"
echo "2. Run 'make setup' to build the applications"
echo "3. Run 'make start' to start the services"
echo "4. Access your applications at:"
echo "   - Simple PHP: http://$NETWORK_INTERFACE:$SIMPLE_PHP_PORT"
echo "   - Laravel: http://$NETWORK_INTERFACE:$LARAVEL_PORT"
echo "   - Symfony: http://$NETWORK_INTERFACE:$SYMFONY_PORT"
echo "   - Slim Framework: http://$NETWORK_INTERFACE:$SLIM_PORT"
echo "   - CodeIgniter: http://$NETWORK_INTERFACE:$CODEIGNITER_PORT"
