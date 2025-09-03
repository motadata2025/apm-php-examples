#!/bin/bash

set -e

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Create validation directory
mkdir -p augment/validation-simple-php
mkdir -p augment/logs

# Timestamp for this validation run
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "Starting UI validation at $(date)"

# Check if server is running, start if needed
if [ ! -f augment/php-server.pid ] || ! ps -p $(cat augment/php-server.pid) > /dev/null 2>&1; then
    echo "Starting PHP server..."
    bash start.sh
    sleep 2
fi

# Base URL
BASE_URL="http://127.0.0.1:8080"

# Function to test endpoint
test_endpoint() {
    local endpoint="$1"
    local method="$2"
    local expected_keys="$3"
    local filename="${TIMESTAMP}-$(echo $endpoint | sed 's/[^a-zA-Z0-9]/_/g').json"
    
    echo "Testing $method $endpoint..."
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" "$BASE_URL$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL$endpoint")
    fi
    
    # Extract HTTP code and body
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n -1)
    
    # Save response
    echo "$body" > "augment/validation-simple-php/$filename"
    
    # Check HTTP code
    if [ "$http_code" != "200" ]; then
        echo "ERROR: $endpoint returned HTTP $http_code"
        echo "HTTP $http_code for $endpoint" >> augment/logs/validate-failure.log
        return 1
    fi
    
    # Check if response is valid JSON
    if ! echo "$body" | jq . > /dev/null 2>&1; then
        echo "ERROR: $endpoint returned invalid JSON"
        echo "Invalid JSON for $endpoint" >> augment/logs/validate-failure.log
        return 1
    fi
    
    # Check for expected keys
    if [ -n "$expected_keys" ]; then
        for key in $expected_keys; do
            if ! echo "$body" | jq -e ".$key" > /dev/null 2>&1; then
                echo "ERROR: $endpoint missing expected key: $key"
                echo "Missing key $key for $endpoint" >> augment/logs/validate-failure.log
                return 1
            fi
        done
    fi
    
    echo "✓ $endpoint passed"
    return 0
}

# Function to test HTML endpoint
test_html_endpoint() {
    local endpoint="$1"
    local filename="${TIMESTAMP}-html-root.html"
    
    echo "Testing GET $endpoint (HTML)..."
    
    response=$(curl -s -w "\n%{http_code}" "$BASE_URL$endpoint")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n -1)
    
    # Save response
    echo "$body" > "augment/validation-simple-php/$filename"
    
    if [ "$http_code" != "200" ]; then
        echo "ERROR: $endpoint returned HTTP $http_code"
        echo "HTTP $http_code for $endpoint" >> augment/logs/validate-failure.log
        return 1
    fi
    
    # Check if it contains expected HTML elements
    if ! echo "$body" | grep -q "Simple PHP" || ! echo "$body" | grep -q "External API" || ! echo "$body" | grep -q "Redis Queue"; then
        echo "ERROR: $endpoint missing expected HTML content"
        echo "Missing HTML content for $endpoint" >> augment/logs/validate-failure.log
        return 1
    fi
    
    echo "✓ $endpoint (HTML) passed"
    return 0
}

# Clear previous failure log
rm -f augment/logs/validate-failure.log

# Test HTML root
test_html_endpoint "/"

# Test API endpoints
test_endpoint "/api/php-version" "GET" "ok php_version"
test_endpoint "/api/external" "POST" "ok"
test_endpoint "/api/db/check" "POST" "ok mysql postgres"
test_endpoint "/api/db/crud" "POST" "ok mysql postgres"
test_endpoint "/api/redis/insert-multiple" "POST" "ok"
test_endpoint "/api/redis/insert-single" "POST" "ok"
test_endpoint "/api/redis/read-single" "POST" "ok"
test_endpoint "/api/redis/clear" "POST" "ok"

# Additional validation checks
echo "Performing additional validation checks..."

# Check that DB connections are successful
db_check_response=$(curl -s -X POST "$BASE_URL/api/db/check")
mysql_ok=$(echo "$db_check_response" | jq -r '.mysql.ok')
postgres_ok=$(echo "$db_check_response" | jq -r '.postgres.ok')

if [ "$mysql_ok" != "true" ]; then
    echo "ERROR: MySQL connection failed"
    echo "MySQL connection failed" >> augment/logs/validate-failure.log
    exit 1
fi

if [ "$postgres_ok" != "true" ]; then
    echo "ERROR: PostgreSQL connection failed"
    echo "PostgreSQL connection failed" >> augment/logs/validate-failure.log
    exit 1
fi

# Check Redis insert multiple returns proper length
redis_insert_response=$(curl -s -X POST "$BASE_URL/api/redis/insert-multiple?count=3")
redis_ok=$(echo "$redis_insert_response" | jq -r '.ok')
redis_length=$(echo "$redis_insert_response" | jq -r '.new_length')

if [ "$redis_ok" != "true" ]; then
    echo "ERROR: Redis insert multiple failed"
    echo "Redis insert multiple failed" >> augment/logs/validate-failure.log
    exit 1
fi

if [ "$redis_length" -lt 3 ]; then
    echo "ERROR: Redis insert multiple returned length < 3"
    echo "Redis insert multiple returned length < 3" >> augment/logs/validate-failure.log
    exit 1
fi

# Check if validation failure log exists
if [ -f augment/logs/validate-failure.log ]; then
    echo "VALIDATION FAILED - see augment/logs/validate-failure.log for details"
    exit 1
fi

echo "✓ All validation checks passed!"
echo "Validation completed successfully at $(date)"
echo "Results saved in augment/validation-simple-php/"
