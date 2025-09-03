#!/bin/bash
# validate_endpoints.sh - Test all CodeIgniter APM endpoints
# Uses curl to validate HTTP responses and JSON structure

set -euo pipefail

BASE_URL="http://127.0.0.1:8082"
TIMEOUT=15
FAILED_TESTS=0
TOTAL_TESTS=0

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test helper function
test_endpoint() {
    local method="$1"
    local endpoint="$2"
    local description="$3"
    local expected_fields="$4"
    
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    echo -n "Testing $description... "
    
    # Make the request
    local response
    local http_code
    
    if ! response=$(curl -s --max-time "$TIMEOUT" -w "%{http_code}" -X "$method" "$BASE_URL$endpoint" 2>/dev/null); then
        echo -e "${RED}FAILED${NC} (curl error)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
    
    # Extract HTTP code (last 3 characters)
    http_code="${response: -3}"
    response="${response%???}"
    
    # Check HTTP status
    if [[ "$http_code" != "200" ]]; then
        echo -e "${RED}FAILED${NC} (HTTP $http_code)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
    
    # Check if response is valid JSON
    if ! echo "$response" | jq . >/dev/null 2>&1; then
        echo -e "${RED}FAILED${NC} (invalid JSON)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
    
    # Check for expected fields
    local missing_fields=""
    for field in $expected_fields; do
        if ! echo "$response" | jq -e ".$field" >/dev/null 2>&1; then
            missing_fields="$missing_fields $field"
        fi
    done
    
    if [[ -n "$missing_fields" ]]; then
        echo -e "${RED}FAILED${NC} (missing fields:$missing_fields)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
    
    echo -e "${GREEN}PASSED${NC}"
    return 0
}

# Test web UI (GET request)
test_web_ui() {
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    
    echo -n "Testing Web UI (GET /)... "
    
    local response
    local http_code
    
    if ! response=$(curl -s --max-time "$TIMEOUT" -w "%{http_code}" "$BASE_URL/" 2>/dev/null); then
        echo -e "${RED}FAILED${NC} (curl error)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
    
    # Extract HTTP code
    http_code="${response: -3}"
    response="${response%???}"
    
    # Check HTTP status
    if [[ "$http_code" != "200" ]]; then
        echo -e "${RED}FAILED${NC} (HTTP $http_code)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
    
    # Check for expected content
    if [[ "$response" == *"CodeIgniter"* ]] && [[ "$response" == *"PHP"* ]]; then
        echo -e "${GREEN}PASSED${NC}"
        return 0
    else
        echo -e "${RED}FAILED${NC} (missing expected content)"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# Check if jq is available
if ! command -v jq >/dev/null 2>&1; then
    echo -e "${YELLOW}Warning: jq not found. JSON validation will be skipped.${NC}"
    # Simple JSON check function
    jq() {
        if [[ "$1" == "." ]]; then
            python3 -m json.tool >/dev/null 2>&1
        elif [[ "$1" == "-e" ]]; then
            python3 -c "import json, sys; data=json.load(sys.stdin); print('true' if '$2' in str(data) else 'false')" 2>/dev/null | grep -q true
        fi
    }
fi

echo "CodeIgniter APM Endpoint Validation"
echo "===================================="
echo "Base URL: $BASE_URL"
echo "Timeout: ${TIMEOUT}s"
echo ""

# Test web UI
test_web_ui

# Test API endpoints
test_endpoint "POST" "/api/external" "External API" "ok"
test_endpoint "POST" "/api/db/connection" "Database Connection" "mysql pg"
test_endpoint "POST" "/api/db/crud" "Database CRUD" "mysql pg"
test_endpoint "POST" "/api/redis/insert-batch" "Redis Insert Batch" "ok"
test_endpoint "POST" "/api/redis/insert-one" "Redis Insert One" "ok"
test_endpoint "POST" "/api/redis/pop" "Redis Pop" "ok"
test_endpoint "POST" "/api/redis/clear" "Redis Clear" "ok"

echo ""
echo "Test Summary"
echo "============"
echo "Total tests: $TOTAL_TESTS"
echo "Passed: $((TOTAL_TESTS - FAILED_TESTS))"
echo "Failed: $FAILED_TESTS"

if [[ $FAILED_TESTS -eq 0 ]]; then
    echo -e "${GREEN}All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some tests failed.${NC}"
    exit 1
fi
