#!/bin/bash

# CodeIgniter App Endpoint Validation Script
# Tests all API endpoints with curl and verifies responses

set -e

BASE_URL="http://127.0.0.1:8082"
TIMEOUT=15
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/endpoint_validation.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Initialize log file
echo "=== CodeIgniter App Endpoint Validation - $(date) ===" > "$LOG_FILE"

# Function to log messages
log() {
    echo "$1" | tee -a "$LOG_FILE"
}

# Function to test endpoint
test_endpoint() {
    local method="$1"
    local endpoint="$2"
    local expected_field="$3"
    local description="$4"
    
    log ""
    log "Testing: $description"
    log "Endpoint: $method $BASE_URL$endpoint"
    
    # Make the request
    if [ "$method" = "GET" ]; then
        response=$(curl -s --max-time $TIMEOUT -w "HTTPSTATUS:%{http_code}" "$BASE_URL$endpoint" 2>&1)
    else
        response=$(curl -s --max-time $TIMEOUT -X POST -H "Content-Type: application/json" -w "HTTPSTATUS:%{http_code}" "$BASE_URL$endpoint" 2>&1)
    fi
    
    # Extract HTTP status and body
    http_status=$(echo "$response" | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
    body=$(echo "$response" | sed 's/HTTPSTATUS:[0-9]*$//')
    
    log "HTTP Status: $http_status"
    log "Response Body: $body"
    
    # Check HTTP status
    if [ "$http_status" != "200" ]; then
        log "${RED}❌ FAILED: HTTP status $http_status (expected 200)${NC}"
        return 1
    fi
    
    # Check if response is valid JSON
    if ! echo "$body" | jq . >/dev/null 2>&1; then
        log "${RED}❌ FAILED: Response is not valid JSON${NC}"
        return 1
    fi
    
    # Check for expected field if provided
    if [ -n "$expected_field" ]; then
        if echo "$body" | jq -e "has(\"$expected_field\")" >/dev/null 2>&1; then
            log "${GREEN}✅ PASSED: Found expected field '$expected_field'${NC}"
        else
            log "${RED}❌ FAILED: Expected field '$expected_field' not found${NC}"
            return 1
        fi
    else
        log "${GREEN}✅ PASSED: Valid JSON response received${NC}"
    fi
    
    return 0
}

# Function to test dashboard (HTML response)
test_dashboard() {
    log ""
    log "Testing: Dashboard UI"
    log "Endpoint: GET $BASE_URL/"
    
    response=$(curl -s --max-time $TIMEOUT -w "HTTPSTATUS:%{http_code}" "$BASE_URL/" 2>&1)
    
    # Extract HTTP status and body
    http_status=$(echo "$response" | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
    body=$(echo "$response" | sed 's/HTTPSTATUS:[0-9]*$//')
    
    log "HTTP Status: $http_status"
    
    # Check HTTP status
    if [ "$http_status" != "200" ]; then
        log "${RED}❌ FAILED: HTTP status $http_status (expected 200)${NC}"
        return 1
    fi
    
    # Check for expected content
    if echo "$body" | grep -q "CodeIgniter" && echo "$body" | grep -q "PHP"; then
        log "${GREEN}✅ PASSED: Dashboard contains expected content (CodeIgniter and PHP)${NC}"
        return 0
    else
        log "${RED}❌ FAILED: Dashboard missing expected content${NC}"
        log "Body preview: $(echo "$body" | head -5)"
        return 1
    fi
}

# Main validation function
main() {
    log "Starting CodeIgniter App endpoint validation..."
    log "Base URL: $BASE_URL"
    log "Timeout: ${TIMEOUT}s"
    
    local failed_tests=0
    local total_tests=0
    
    # Test dashboard
    total_tests=$((total_tests + 1))
    if ! test_dashboard; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Test external API endpoint
    total_tests=$((total_tests + 1))
    if ! test_endpoint "POST" "/api/external" "ok" "External API Call"; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Test database connection check
    total_tests=$((total_tests + 1))
    if ! test_endpoint "POST" "/api/db/connection" "mysql" "Database Connection Check"; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Test database CRUD operations
    total_tests=$((total_tests + 1))
    if ! test_endpoint "POST" "/api/db/crud" "mysql" "Database CRUD Operations"; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Test Redis insert batch
    total_tests=$((total_tests + 1))
    if ! test_endpoint "POST" "/api/redis/insert-batch" "ok" "Redis Insert Batch"; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Test Redis insert one
    total_tests=$((total_tests + 1))
    if ! test_endpoint "POST" "/api/redis/insert-one" "ok" "Redis Insert One"; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Test Redis pop
    total_tests=$((total_tests + 1))
    if ! test_endpoint "POST" "/api/redis/pop" "ok" "Redis Pop Message"; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Test Redis clear
    total_tests=$((total_tests + 1))
    if ! test_endpoint "POST" "/api/redis/clear" "ok" "Redis Clear Queue"; then
        failed_tests=$((failed_tests + 1))
    fi
    
    # Summary
    log ""
    log "=== VALIDATION SUMMARY ==="
    log "Total tests: $total_tests"
    log "Passed: $((total_tests - failed_tests))"
    log "Failed: $failed_tests"
    
    if [ $failed_tests -eq 0 ]; then
        log "${GREEN}🎉 ALL TESTS PASSED!${NC}"
        return 0
    else
        log "${RED}❌ $failed_tests TEST(S) FAILED${NC}"
        return 1
    fi
}

# Check dependencies
if ! command -v curl >/dev/null 2>&1; then
    log "${RED}Error: curl is required but not installed${NC}"
    exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
    log "${YELLOW}Warning: jq not found, JSON validation will be limited${NC}"
fi

# Run main function
main "$@"
