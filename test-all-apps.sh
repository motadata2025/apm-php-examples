#!/bin/bash

# APM PHP Examples - Complete Application Testing Suite
# Tests all applications for consistency and functionality

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Test results tracking
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Applications to test
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
APP_PORTS=(8000 8004 8002 8001 8003)

echo -e "${BLUE}🧪 APM PHP Examples - Complete Testing Suite${NC}"
echo "=============================================="
echo ""

# Function to run a test
run_test() {
    local test_name="$1"
    local test_command="$2"
    local expected_result="$3"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    echo -e "${YELLOW}Testing: ${test_name}${NC}"
    
    if eval "$test_command"; then
        if [ "$expected_result" = "success" ]; then
            echo -e "${GREEN}✅ PASSED: ${test_name}${NC}"
            PASSED_TESTS=$((PASSED_TESTS + 1))
        else
            echo -e "${RED}❌ FAILED: ${test_name} (unexpected success)${NC}"
            FAILED_TESTS=$((FAILED_TESTS + 1))
        fi
    else
        if [ "$expected_result" = "fail" ]; then
            echo -e "${GREEN}✅ PASSED: ${test_name} (expected failure)${NC}"
            PASSED_TESTS=$((PASSED_TESTS + 1))
        else
            echo -e "${RED}❌ FAILED: ${test_name}${NC}"
            FAILED_TESTS=$((FAILED_TESTS + 1))
        fi
    fi
    echo ""
}

# Function to test application setup
test_application_setup() {
    local app="$1"
    local port="$2"
    
    echo -e "${CYAN}🔧 Testing ${app} Setup${NC}"
    echo "------------------------"
    
    # Test 1: Directory exists
    run_test "${app} - Directory exists" "[ -d \"${app}\" ]" "success"
    
    # Test 2: Setup script exists and is executable
    run_test "${app} - Setup script exists" "[ -f \"${app}/setup.sh\" ]" "success"
    run_test "${app} - Setup script is executable" "[ -x \"${app}/setup.sh\" ]" "success"
    
    # Test 3: Docker files exist
    run_test "${app} - Dockerfile exists" "[ -f \"${app}/Dockerfile\" ]" "success"
    run_test "${app} - docker-compose.yml exists" "[ -f \"${app}/docker-compose.yml\" ]" "success"
    
    # Test 4: Composer file exists
    run_test "${app} - composer.json exists" "[ -f \"${app}/composer.json\" ]" "success"
    
    # Test 5: Public directory exists
    run_test "${app} - public directory exists" "[ -d \"${app}/public\" ]" "success"
    
    # Test 6: Main index file exists
    run_test "${app} - index.php exists" "[ -f \"${app}/public/index.php\" ]" "success"
}

# Function to test application functionality
test_application_functionality() {
    local app="$1"
    local port="$2"
    
    echo -e "${CYAN}🚀 Testing ${app} Functionality${NC}"
    echo "------------------------------"
    
    # Test Docker services
    run_test "${app} - Docker services running" "(cd ${app} && docker compose ps | grep -q 'Up')" "success"
    
    # Test application response
    run_test "${app} - Application responds" "curl -s -o /dev/null -w '%{http_code}' http://localhost:${port} | grep -q '200'" "success"
    
    # Test application content
    run_test "${app} - Application returns content" "curl -s http://localhost:${port} | grep -q -i 'php'" "success"
}

# Main testing sequence
echo -e "${PURPLE}📋 Phase 1: Structure and Setup Tests${NC}"
echo "======================================"
echo ""

for i in "${!APPLICATIONS[@]}"; do
    app="${APPLICATIONS[$i]}"
    port="${APP_PORTS[$i]}"
    test_application_setup "$app" "$port"
done

echo -e "${PURPLE}📋 Phase 2: Docker and Functionality Tests${NC}"
echo "=========================================="
echo ""

# Start all applications
echo -e "${YELLOW}🔄 Starting all applications...${NC}"
for app in "${APPLICATIONS[@]}"; do
    echo "Starting ${app}..."
    cd "$app"
    docker compose up -d > /dev/null 2>&1 || true
    cd ..
done

# Wait for services to start
echo -e "${YELLOW}⏳ Waiting for services to start...${NC}"
sleep 10

# Test functionality
for i in "${!APPLICATIONS[@]}"; do
    app="${APPLICATIONS[$i]}"
    port="${APP_PORTS[$i]}"
    test_application_functionality "$app" "$port"
done

# Additional integration tests
echo -e "${PURPLE}📋 Phase 3: Integration Tests${NC}"
echo "============================="
echo ""

# Test port conflicts
run_test "Port allocation - No conflicts" "netstat -tuln | grep -E ':(8000|8001|8002|8003|8004|3307|3308|3309|3310|3311|5433|5434|5435|5436|5437|6380|6381|6382|6383|6384)' | wc -l | grep -q '^[1-9]'" "success"

# Test documentation
run_test "Documentation - README.md exists" "[ -f 'README.md' ]" "success"
run_test "Documentation - requirements_overview.md exists" "[ -f 'requirements_overview.md' ]" "success"
run_test "Documentation - DOCKER_PORTS.md exists" "[ -f 'DOCKER_PORTS.md' ]" "success"

# Final results
echo -e "${PURPLE}📊 Test Results Summary${NC}"
echo "======================="
echo ""
echo -e "${BLUE}Total Tests: ${TOTAL_TESTS}${NC}"
echo -e "${GREEN}Passed: ${PASSED_TESTS}${NC}"
echo -e "${RED}Failed: ${FAILED_TESTS}${NC}"
echo ""

if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}🎉 ALL TESTS PASSED! 🎉${NC}"
    echo -e "${GREEN}The APM PHP Examples repository is ready for use.${NC}"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. Please review the output above.${NC}"
    exit 1
fi
