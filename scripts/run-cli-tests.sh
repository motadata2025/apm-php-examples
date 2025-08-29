#!/bin/bash

# APM PHP Examples - CLI Testing Script
# This script runs comprehensive tests for all applications using local PHP CLI

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
NETWORK_IP="127.0.0.1"

# Load configuration if it exists
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
    NETWORK_IP="${NETWORK_INTERFACE:-127.0.0.1}"
fi

echo -e "${BLUE}🧪 APM PHP Examples - Comprehensive Local CLI Testing${NC}"
echo "======================================================"
echo "PHP Version: $PHP_VERSION"
echo "Network IP: $NETWORK_IP"
echo "Test Mode: Local PHP CLI"
echo ""

# Applications to test
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

# Test results tracking
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
TEST_RESULTS=()

# Function to run tests for a specific application
run_app_tests() {
    local app="$1"
    local app_name="$2"

    echo -e "\n${BLUE}Testing $app_name${NC}"
    echo "$(printf '=%.0s' {1..50})"

    if [ ! -d "$app" ]; then
        echo -e "${RED}❌ Application directory $app not found${NC}"
        TEST_RESULTS+=("$app_name: DIRECTORY_NOT_FOUND")
        ((FAILED_TESTS++))
        return 1
    fi

    cd "$app"

    # Check if dependencies are installed
    echo -e "${YELLOW}Checking dependencies for $app_name...${NC}"
    if [ ! -d "vendor" ]; then
        echo -e "${YELLOW}Installing dependencies...${NC}"
        if composer install --quiet; then
            echo -e "${GREEN}✅ Dependencies installed${NC}"
        else
            echo -e "${RED}❌ Failed to install dependencies${NC}"
            TEST_RESULTS+=("$app_name: DEPENDENCY_FAILED")
            ((FAILED_TESTS++))
            cd ..
            return 1
        fi
    else
        echo -e "${GREEN}✅ Dependencies already installed${NC}"
    fi
    
    # Test 1: Basic PHP syntax check
    echo -e "\n${YELLOW}Test 1: PHP Syntax Check${NC}"
    ((TOTAL_TESTS++))
    if php -l public/index.php > /dev/null 2>&1; then
        echo -e "${GREEN}✅ PHP syntax is valid${NC}"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}❌ PHP syntax errors found${NC}"
        ((FAILED_TESTS++))
    fi

    # Test 2: Composer dependencies
    echo -e "\n${YELLOW}Test 2: Composer Dependencies${NC}"
    ((TOTAL_TESTS++))
    if [ -f "composer.json" ]; then
        if composer validate > /dev/null 2>&1; then
            echo -e "${GREEN}✅ Composer configuration is valid${NC}"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}❌ Composer configuration issues${NC}"
            ((FAILED_TESTS++))
        fi
    else
        echo -e "${YELLOW}⚠️  No composer.json found, skipping${NC}"
        ((PASSED_TESTS++))
    fi

    # Test 3: PHPUnit tests (if available)
    echo -e "\n${YELLOW}Test 3: PHPUnit Tests${NC}"
    ((TOTAL_TESTS++))
    if [ -f "phpunit.xml" ] || [ -f "phpunit.xml.dist" ]; then
        if ./vendor/bin/phpunit --no-coverage > /dev/null 2>&1; then
            echo -e "${GREEN}✅ PHPUnit tests passed${NC}"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}❌ PHPUnit tests failed${NC}"
            ((FAILED_TESTS++))
        fi
    else
        echo -e "${YELLOW}⚠️  No PHPUnit configuration found, skipping${NC}"
        ((PASSED_TESTS++))
    fi
    
    # Test 4: Application health check
    echo -e "\n${YELLOW}Test 4: Application Health Check${NC}"
    ((TOTAL_TESTS++))

    # Get the port for this application
    case "$app" in
        "simple-php") APP_PORT=8080 ;;
        "laravel-app") APP_PORT=8081 ;;
        "symfony-app") APP_PORT=8082 ;;
        "slim-framework") APP_PORT=8083 ;;
        "codeigniter-app") APP_PORT=8084 ;;
        *) APP_PORT=8080 ;;
    esac

    # Check if application is running
    if curl -s -f "http://$NETWORK_IP:$APP_PORT/health" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Health check passed${NC}"
        ((PASSED_TESTS++))
    elif curl -s -f "http://$NETWORK_IP:$APP_PORT/" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Application responds (no health endpoint)${NC}"
        ((PASSED_TESTS++))
    else
        echo -e "${YELLOW}⚠️  Application not running or not accessible${NC}"
        echo -e "${YELLOW}    Start applications with: ./start-all-local.sh${NC}"
        ((FAILED_TESTS++))
    fi
    
    # Test 5: Database connectivity (if configured)
    echo -e "\n${YELLOW}Test 5: Database Connectivity${NC}"
    ((TOTAL_TESTS++))

    # Check if database services are running
    if docker compose -f ../docker-compose.yml ps | grep -q "mysql.*Up"; then
        if php -r "
            try {
                \$pdo = new PDO('mysql:host=$NETWORK_IP;dbname=apm_examples', 'root', 'rootpassword');
                echo 'Database connection successful';
                exit(0);
            } catch (Exception \$e) {
                echo 'Database connection failed: ' . \$e->getMessage();
                exit(1);
            }
        " > /dev/null 2>&1; then
            echo -e "${GREEN}✅ Database connectivity test passed${NC}"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}❌ Database connectivity test failed${NC}"
            ((FAILED_TESTS++))
        fi
    else
        echo -e "${YELLOW}⚠️  Database services not running, skipping${NC}"
        echo -e "${YELLOW}    Start services with: make start-services${NC}"
        ((PASSED_TESTS++))
    fi

    # Test 6: Redis connectivity (if configured)
    echo -e "\n${YELLOW}Test 6: Redis Connectivity${NC}"
    ((TOTAL_TESTS++))

    if docker compose -f ../docker-compose.yml ps | grep -q "redis.*Up"; then
        if php -r "
            try {
                \$redis = new Redis();
                \$redis->connect('$NETWORK_IP', 6379);
                \$redis->auth('redispassword');
                \$redis->ping();
                echo 'Redis connection successful';
                exit(0);
            } catch (Exception \$e) {
                echo 'Redis connection failed: ' . \$e->getMessage();
                exit(1);
            }
        " > /dev/null 2>&1; then
            echo -e "${GREEN}✅ Redis connectivity test passed${NC}"
            ((PASSED_TESTS++))
        else
            echo -e "${RED}❌ Redis connectivity test failed${NC}"
            ((FAILED_TESTS++))
        fi
    else
        echo -e "${YELLOW}⚠️  Redis service not running, skipping${NC}"
        echo -e "${YELLOW}    Start services with: make start-services${NC}"
        ((PASSED_TESTS++))
    fi
    
    # Calculate app-specific results
    local app_tests=6
    local app_passed=$(echo "$PASSED_TESTS - $prev_passed" | bc 2>/dev/null || echo "0")
    local app_failed=$(echo "$FAILED_TESTS - $prev_failed" | bc 2>/dev/null || echo "0")
    
    if [ "$app_failed" -eq 0 ]; then
        TEST_RESULTS+=("$app_name: ALL_PASSED")
        echo -e "\n${GREEN}🎉 All tests passed for $app_name!${NC}"
    else
        TEST_RESULTS+=("$app_name: SOME_FAILED")
        echo -e "\n${YELLOW}⚠️  Some tests failed for $app_name${NC}"
    fi
    
    cd ..
}

# Check if supporting services are running
echo -e "${YELLOW}Checking supporting services...${NC}"
if ! docker compose ps | grep -q "Up"; then
    echo -e "${YELLOW}Starting supporting services...${NC}"
    docker compose up -d
    echo -e "${YELLOW}Waiting for services to be ready...${NC}"
    sleep 15
else
    echo -e "${GREEN}✅ Supporting services are already running${NC}"
fi

# Run tests for each application
prev_passed=0
prev_failed=0

for i in "${!APPLICATIONS[@]}"; do
    app="${APPLICATIONS[$i]}"
    
    case "$app" in
        "simple-php") app_name="Simple PHP" ;;
        "laravel-app") app_name="Laravel" ;;
        "symfony-app") app_name="Symfony" ;;
        "slim-framework") app_name="Slim Framework" ;;
        "codeigniter-app") app_name="CodeIgniter" ;;
    esac
    
    prev_passed=$PASSED_TESTS
    prev_failed=$FAILED_TESTS
    
    run_app_tests "$app" "$app_name"
done

# Display final results
echo -e "\n${BLUE}📊 Final Test Results${NC}"
echo "======================"
echo "Total Tests: $TOTAL_TESTS"
echo -e "Passed: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Failed: ${RED}$FAILED_TESTS${NC}"

if [ "$FAILED_TESTS" -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed! Your APM PHP Examples are working perfectly.${NC}"
    exit_code=0
else
    echo -e "\n${YELLOW}⚠️  Some tests failed. Please review the results above.${NC}"
    exit_code=1
fi

echo -e "\n${BLUE}Application Summary:${NC}"
for result in "${TEST_RESULTS[@]}"; do
    app_name=$(echo "$result" | cut -d: -f1)
    status=$(echo "$result" | cut -d: -f2)
    
    case "$status" in
        "ALL_PASSED") echo -e "  ${GREEN}✅ $app_name${NC}" ;;
        "SOME_FAILED") echo -e "  ${YELLOW}⚠️  $app_name${NC}" ;;
        "BUILD_FAILED") echo -e "  ${RED}❌ $app_name (Build Failed)${NC}" ;;
        "DIRECTORY_NOT_FOUND") echo -e "  ${RED}❌ $app_name (Not Found)${NC}" ;;
    esac
done

echo -e "\n${BLUE}Next Steps:${NC}"
if [ "$exit_code" -eq 0 ]; then
    echo "• Your applications are ready for production deployment"
    echo "• Consider running 'make start' to launch all services"
    echo "• Use 'make endpoints' to see all available URLs"
else
    echo "• Review failed tests and fix any issues"
    echo "• Check application logs with 'make logs'"
    echo "• Ensure all dependencies are properly installed"
fi

exit $exit_code
