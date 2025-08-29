#!/bin/bash

# APM PHP Examples - Local PHP Environment Setup
# This script sets up PHP applications to run on the local machine

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
CONFIG_FILE="config/deployment.env"
PHP_VERSION="8.4"

# Load configuration if it exists
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
fi

echo -e "${BLUE}🐘 Setting up Local PHP Environment${NC}"
echo "===================================="
echo "PHP Version: $PHP_VERSION"
echo "Deployment Type: ${DEPLOYMENT_TYPE:-local-php-cli}"
echo ""

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check PHP version
check_php_version() {
    if command_exists php; then
        local current_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
        local required_version=$(echo "$PHP_VERSION" | cut -d. -f1,2)
        
        if [ "$current_version" = "$required_version" ]; then
            echo -e "${GREEN}✅ PHP $current_version is installed${NC}"
            return 0
        else
            echo -e "${YELLOW}⚠️  PHP $current_version is installed, but PHP $required_version is required${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ PHP is not installed${NC}"
        return 1
    fi
}

# Function to check PHP extensions
check_php_extensions() {
    local required_extensions=("pdo" "pdo_mysql" "pdo_pgsql" "mbstring" "gd" "zip" "redis" "intl")
    local missing_extensions=()
    
    echo -e "${BLUE}Checking PHP extensions...${NC}"
    
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -iq "^$ext$"; then
            echo -e "${GREEN}✅ $ext${NC}"
        else
            echo -e "${RED}❌ $ext${NC}"
            missing_extensions+=("$ext")
        fi
    done
    
    if [ ${#missing_extensions[@]} -gt 0 ]; then
        echo -e "\n${YELLOW}Missing extensions: ${missing_extensions[*]}${NC}"
        return 1
    else
        echo -e "\n${GREEN}✅ All required PHP extensions are installed${NC}"
        return 0
    fi
}

# Function to check Composer
check_composer() {
    if command_exists composer; then
        echo -e "${GREEN}✅ Composer is installed${NC}"
        composer --version
        return 0
    else
        echo -e "${RED}❌ Composer is not installed${NC}"
        return 1
    fi
}

# Function to install dependencies for all applications
install_dependencies() {
    local applications=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
    
    echo -e "\n${BLUE}Installing dependencies for all applications...${NC}"
    
    for app in "${applications[@]}"; do
        if [ -d "$app" ] && [ -f "$app/composer.json" ]; then
            echo -e "\n${YELLOW}Installing dependencies for $app...${NC}"
            cd "$app"
            
            # Install dependencies
            if composer install --optimize-autoloader; then
                echo -e "${GREEN}✅ Dependencies installed for $app${NC}"
            else
                echo -e "${RED}❌ Failed to install dependencies for $app${NC}"
            fi
            
            cd ..
        else
            echo -e "${YELLOW}⚠️  Skipping $app (no composer.json found)${NC}"
        fi
    done
}

# Function to create environment files
create_env_files() {
    local applications=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
    
    echo -e "\n${BLUE}Creating environment files...${NC}"
    
    # Get network interface IP
    local network_ip="${NETWORK_INTERFACE:-127.0.0.1}"
    
    for app in "${applications[@]}"; do
        if [ -d "$app" ]; then
            echo -e "${YELLOW}Creating .env for $app...${NC}"
            
            # Create .env file based on application type
            case "$app" in
                "laravel-app")
                    cat > "$app/.env" << EOF
APP_NAME=Laravel-APM-Example
APP_ENV=${APP_ENV:-local}
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=http://$network_ip:8081

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=$network_ip
DB_PORT=3306
DB_DATABASE=apm_examples
DB_USERNAME=root
DB_PASSWORD=rootpassword

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=$network_ip
REDIS_PASSWORD=redispassword
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF
                    ;;
                "symfony-app")
                    cat > "$app/.env.local" << EOF
APP_ENV=${APP_ENV:-dev}
APP_SECRET=$(openssl rand -hex 16)
DATABASE_URL="mysql://root:rootpassword@$network_ip:3306/apm_examples?serverVersion=8.0&charset=utf8mb4"
REDIS_URL=redis://redispassword@$network_ip:6379
EOF
                    ;;
                *)
                    # For other applications, create a simple .env
                    cat > "$app/.env" << EOF
APP_ENV=${APP_ENV:-development}
APP_DEBUG=${APP_DEBUG:-true}

# Database Configuration
DB_HOST=$network_ip
DB_PORT=3306
DB_DATABASE=apm_examples
DB_USERNAME=root
DB_PASSWORD=rootpassword

# PostgreSQL Configuration
POSTGRES_HOST=$network_ip
POSTGRES_PORT=5432
POSTGRES_DATABASE=apm_examples
POSTGRES_USERNAME=postgres
POSTGRES_PASSWORD=postgrespassword



# Redis Configuration
REDIS_HOST=$network_ip
REDIS_PORT=6379
REDIS_PASSWORD=redispassword
EOF
                    ;;
            esac
            
            echo -e "${GREEN}✅ Created .env for $app${NC}"
        fi
    done
}

# Function to create startup scripts
create_startup_scripts() {
    local applications=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
    local ports=(8080 8081 8082 8083 8084)
    
    echo -e "\n${BLUE}Creating startup scripts...${NC}"
    
    # Create individual startup scripts
    for i in "${!applications[@]}"; do
        local app="${applications[$i]}"
        local port="${ports[$i]}"
        
        if [ -d "$app" ]; then
            cat > "$app/start-local.sh" << EOF
#!/bin/bash

# Start $app locally
echo "Starting $app on http://${NETWORK_INTERFACE:-127.0.0.1}:$port"

cd "\$(dirname "\$0")"

# Check if dependencies are installed
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install
fi

# Start the application based on type
case "$app" in
    "laravel-app")
        php artisan serve --host=${NETWORK_INTERFACE:-127.0.0.1} --port=$port
        ;;
    "symfony-app")
        if command -v symfony >/dev/null 2>&1; then
            symfony server:start --host=${NETWORK_INTERFACE:-127.0.0.1} --port=$port --no-tls
        else
            php -S ${NETWORK_INTERFACE:-127.0.0.1}:$port -t public
        fi
        ;;
    *)
        php -S ${NETWORK_INTERFACE:-127.0.0.1}:$port -t public
        ;;
esac
EOF
            chmod +x "$app/start-local.sh"
            echo -e "${GREEN}✅ Created startup script for $app${NC}"
        fi
    done
    
    # Create master startup script
    cat > "start-all-local.sh" << 'EOF'
#!/bin/bash

# Start all APM PHP applications locally
echo "Starting all APM PHP applications..."

# Array of applications and their ports
declare -A apps=(
    ["simple-php"]=8080
    ["laravel-app"]=8081
    ["symfony-app"]=8082
    ["slim-framework"]=8083
    ["codeigniter-app"]=8084
)

# Function to start application in background
start_app() {
    local app="$1"
    local port="$2"
    
    if [ -d "$app" ] && [ -f "$app/start-local.sh" ]; then
        echo "Starting $app on port $port..."
        cd "$app"
        ./start-local.sh &
        local pid=$!
        echo "$pid" > "../.$app.pid"
        cd ..
        echo "✅ $app started (PID: $pid)"
    else
        echo "⚠️  Skipping $app (not found or no startup script)"
    fi
}

# Start all applications
for app in "${!apps[@]}"; do
    port="${apps[$app]}"
    start_app "$app" "$port"
    sleep 2
done

echo ""
echo "🎉 All applications started!"
echo ""
echo "Access your applications at:"
for app in "${!apps[@]}"; do
    port="${apps[$app]}"
    echo "  $app: http://NETWORK_IP:$port"
done

echo ""
echo "To stop all applications, run: ./stop-all-local.sh"
EOF
    
    # Replace NETWORK_IP placeholder
    sed -i "s/NETWORK_IP/${NETWORK_INTERFACE:-127.0.0.1}/g" "start-all-local.sh"
    chmod +x "start-all-local.sh"
    
    # Create stop script
    cat > "stop-all-local.sh" << 'EOF'
#!/bin/bash

# Stop all APM PHP applications
echo "Stopping all APM PHP applications..."

# Array of applications
apps=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

for app in "${apps[@]}"; do
    if [ -f ".$app.pid" ]; then
        pid=$(cat ".$app.pid")
        if kill -0 "$pid" 2>/dev/null; then
            echo "Stopping $app (PID: $pid)..."
            kill "$pid"
            rm ".$app.pid"
            echo "✅ $app stopped"
        else
            echo "⚠️  $app was not running"
            rm ".$app.pid"
        fi
    else
        echo "⚠️  No PID file found for $app"
    fi
done

echo ""
echo "🛑 All applications stopped!"
EOF
    chmod +x "stop-all-local.sh"
    
    echo -e "${GREEN}✅ Created master startup and stop scripts${NC}"
}

# Main setup process
echo -e "${BLUE}Step 1: Checking PHP installation${NC}"
if ! check_php_version; then
    echo -e "\n${YELLOW}Please install PHP $PHP_VERSION with the following extensions:${NC}"
    echo "  • pdo, pdo_mysql, pdo_pgsql"
    echo "  • mbstring, gd, zip"
    echo "  • redis, intl"
    echo ""
    echo "On Ubuntu/Debian:"
    echo "  sudo apt update"
    echo "  sudo apt install php$PHP_VERSION php$PHP_VERSION-{cli,fpm,mysql,pgsql,mbstring,gd,zip,redis,intl}"
    echo ""
    echo "On macOS (with Homebrew):"
    echo "  brew install php@$PHP_VERSION"
    echo "  brew install php-redis"
    echo ""
    exit 1
fi

echo -e "\n${BLUE}Step 2: Checking PHP extensions${NC}"
if ! check_php_extensions; then
    echo -e "\n${YELLOW}Please install the missing PHP extensions listed above${NC}"
    exit 1
fi

echo -e "\n${BLUE}Step 3: Checking Composer${NC}"
if ! check_composer; then
    echo -e "\n${YELLOW}Please install Composer from https://getcomposer.org/${NC}"
    exit 1
fi

echo -e "\n${BLUE}Step 4: Installing application dependencies${NC}"
install_dependencies

echo -e "\n${BLUE}Step 5: Creating environment files${NC}"
create_env_files

echo -e "\n${BLUE}Step 6: Creating startup scripts${NC}"
create_startup_scripts

echo -e "\n${GREEN}🎉 Local PHP environment setup completed!${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo "1. Start supporting services: make start-services"
echo "2. Start all applications: ./start-all-local.sh"
echo "3. Access your applications:"
echo "   • Simple PHP:      http://${NETWORK_INTERFACE:-127.0.0.1}:8080"
echo "   • Laravel:         http://${NETWORK_INTERFACE:-127.0.0.1}:8081"
echo "   • Symfony:         http://${NETWORK_INTERFACE:-127.0.0.1}:8082"
echo "   • Slim Framework:  http://${NETWORK_INTERFACE:-127.0.0.1}:8083"
echo "   • CodeIgniter:     http://${NETWORK_INTERFACE:-127.0.0.1}:8084"
echo ""
echo "4. Stop all applications: ./stop-all-local.sh"
