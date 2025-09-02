#!/bin/bash

# APM PHP Examples - Comprehensive Performance Benchmarking
# Tests performance across all applications with detailed metrics

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}⚡ APM PHP Examples - Performance Benchmarking${NC}"
echo "=============================================="
echo ""

# Applications and their ports
declare -A APPLICATIONS=(
    ["simple-php"]="8000"
    ["slim-framework"]="8001"
    ["symfony-app"]="8002"
    ["codeigniter-app"]="8003"
    ["laravel-app"]="8004"
)

# Benchmark configuration
WARMUP_REQUESTS=10
BENCHMARK_REQUESTS=100
CONCURRENT_USERS=10
BENCHMARK_DURATION=30

# Results storage
RESULTS_DIR="benchmark_results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_FILE="${RESULTS_DIR}/benchmark_${TIMESTAMP}.json"

# Create results directory
mkdir -p "$RESULTS_DIR"

# Function to check if application is running
check_application() {
    local app="$1"
    local port="$2"
    local url="http://localhost:${port}"
    
    echo -e "${YELLOW}Checking $app at $url...${NC}"
    
    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200"; then
        echo -e "${GREEN}✅ $app is running${NC}"
        return 0
    else
        echo -e "${RED}❌ $app is not running${NC}"
        return 1
    fi
}

# Function to start application if not running
start_application() {
    local app="$1"
    local port="$2"
    
    echo -e "${YELLOW}Starting $app...${NC}"
    cd "$app"
    
    # Start Docker services
    docker compose up -d > /dev/null 2>&1
    
    # Start application based on framework
    case $app in
        "simple-php")
            php -S localhost:${port} -t public > /dev/null 2>&1 &
            ;;
        "laravel-app")
            php artisan serve --port=${port} > /dev/null 2>&1 &
            ;;
        "symfony-app")
            php -S localhost:${port} -t public > /dev/null 2>&1 &
            ;;
        "slim-framework")
            php -S localhost:${port} -t public > /dev/null 2>&1 &
            ;;
        "codeigniter-app")
            php spark serve --port=${port} > /dev/null 2>&1 &
            ;;
    esac
    
    # Wait for application to start
    sleep 5
    cd ..
}

# Function to warm up application
warmup_application() {
    local app="$1"
    local port="$2"
    local url="http://localhost:${port}"
    
    echo -e "${YELLOW}Warming up $app...${NC}"
    
    for i in $(seq 1 $WARMUP_REQUESTS); do
        curl -s "$url" > /dev/null
    done
    
    echo -e "${GREEN}✅ Warmup complete${NC}"
}

# Function to benchmark single request performance
benchmark_single_request() {
    local app="$1"
    local port="$2"
    local url="http://localhost:${port}"
    
    echo -e "${CYAN}📊 Single Request Benchmark for $app${NC}"
    
    # Use curl to measure timing
    local result=$(curl -s -o /dev/null -w "%{time_total},%{time_connect},%{time_starttransfer},%{http_code},%{size_download}" "$url")
    
    IFS=',' read -r total_time connect_time start_transfer_time http_code size_download <<< "$result"
    
    echo "  Total Time: ${total_time}s"
    echo "  Connect Time: ${connect_time}s"
    echo "  Time to First Byte: ${start_transfer_time}s"
    echo "  HTTP Code: $http_code"
    echo "  Response Size: $size_download bytes"
    
    # Store results
    echo "{
        \"app\": \"$app\",
        \"port\": $port,
        \"single_request\": {
            \"total_time\": $total_time,
            \"connect_time\": $connect_time,
            \"time_to_first_byte\": $start_transfer_time,
            \"http_code\": $http_code,
            \"response_size\": $size_download
        }
    }" >> "${RESULTS_FILE}.tmp"
}

# Function to benchmark concurrent requests using Apache Bench
benchmark_concurrent_requests() {
    local app="$1"
    local port="$2"
    local url="http://localhost:${port}"
    
    echo -e "${CYAN}📊 Concurrent Requests Benchmark for $app${NC}"
    
    if command -v ab > /dev/null; then
        echo "Using Apache Bench (ab)..."
        
        # Run Apache Bench
        local ab_output=$(ab -n $BENCHMARK_REQUESTS -c $CONCURRENT_USERS "$url" 2>/dev/null)
        
        # Parse results
        local requests_per_second=$(echo "$ab_output" | grep "Requests per second" | awk '{print $4}')
        local time_per_request=$(echo "$ab_output" | grep "Time per request" | head -1 | awk '{print $4}')
        local transfer_rate=$(echo "$ab_output" | grep "Transfer rate" | awk '{print $3}')
        
        echo "  Requests per second: $requests_per_second"
        echo "  Time per request: ${time_per_request}ms"
        echo "  Transfer rate: ${transfer_rate} KB/sec"
        
    else
        echo "Apache Bench not available, using curl-based benchmark..."
        
        # Fallback to curl-based concurrent testing
        local start_time=$(date +%s.%N)
        
        for i in $(seq 1 $BENCHMARK_REQUESTS); do
            curl -s "$url" > /dev/null &
            
            # Limit concurrent requests
            if (( i % CONCURRENT_USERS == 0 )); then
                wait
            fi
        done
        wait
        
        local end_time=$(date +%s.%N)
        local total_duration=$(echo "$end_time - $start_time" | bc)
        local requests_per_second=$(echo "scale=2; $BENCHMARK_REQUESTS / $total_duration" | bc)
        
        echo "  Total Duration: ${total_duration}s"
        echo "  Requests per second: $requests_per_second"
    fi
}

# Function to benchmark memory usage
benchmark_memory_usage() {
    local app="$1"
    local port="$2"
    
    echo -e "${CYAN}📊 Memory Usage Benchmark for $app${NC}"
    
    # Get PHP process memory usage
    local php_processes=$(pgrep -f "php.*${port}")
    local total_memory=0
    
    if [ -n "$php_processes" ]; then
        while IFS= read -r pid; do
            local memory=$(ps -o rss= -p "$pid" 2>/dev/null || echo "0")
            total_memory=$((total_memory + memory))
        done <<< "$php_processes"
        
        echo "  Total Memory Usage: ${total_memory} KB"
        echo "  Total Memory Usage: $(echo "scale=2; $total_memory / 1024" | bc) MB"
    else
        echo "  No PHP processes found for port $port"
    fi
}

# Function to test database performance
benchmark_database_performance() {
    local app="$1"
    local port="$2"
    
    echo -e "${CYAN}📊 Database Performance for $app${NC}"
    
    # Test database endpoints if they exist
    local db_endpoints=("/api/users" "/users" "/test-db")
    
    for endpoint in "${db_endpoints[@]}"; do
        local url="http://localhost:${port}${endpoint}"
        local response=$(curl -s -o /dev/null -w "%{http_code},%{time_total}" "$url")
        
        IFS=',' read -r http_code response_time <<< "$response"
        
        if [ "$http_code" = "200" ]; then
            echo "  $endpoint: ${response_time}s (HTTP $http_code)"
        fi
    done
}

# Function to generate performance report
generate_report() {
    echo -e "${PURPLE}📋 Generating Performance Report${NC}"
    echo "=================================="
    
    local report_file="${RESULTS_DIR}/performance_report_${TIMESTAMP}.md"
    
    cat > "$report_file" << EOF
# APM PHP Examples - Performance Report

**Generated:** $(date)
**Benchmark Configuration:**
- Warmup Requests: $WARMUP_REQUESTS
- Benchmark Requests: $BENCHMARK_REQUESTS
- Concurrent Users: $CONCURRENT_USERS
- Benchmark Duration: ${BENCHMARK_DURATION}s

## Performance Summary

| Application | Port | Status | Response Time | Memory Usage |
|-------------|------|--------|---------------|--------------|
EOF

    for app in "${!APPLICATIONS[@]}"; do
        local port="${APPLICATIONS[$app]}"
        local status="❌ Not Running"
        
        if check_application "$app" "$port" > /dev/null 2>&1; then
            status="✅ Running"
        fi
        
        echo "| $app | $port | $status | - | - |" >> "$report_file"
    done
    
    cat >> "$report_file" << EOF

## Detailed Results

See individual benchmark files in the \`$RESULTS_DIR\` directory for detailed metrics.

## Performance Targets

- **Response Time**: < 200ms for health checks
- **Memory Usage**: < 128MB per application
- **Requests per Second**: > 100 RPS
- **Database Queries**: < 100ms average

## Recommendations

1. Monitor memory usage during peak load
2. Optimize database queries for better performance
3. Implement caching strategies where appropriate
4. Consider horizontal scaling for high-traffic scenarios
EOF

    echo -e "${GREEN}✅ Report generated: $report_file${NC}"
}

# Main benchmarking process
main() {
    echo -e "${PURPLE}Starting comprehensive performance benchmarking...${NC}"
    echo ""
    
    # Initialize results file
    echo "[]" > "$RESULTS_FILE"
    
    # Process each application
    for app in "${!APPLICATIONS[@]}"; do
        local port="${APPLICATIONS[$app]}"
        
        echo -e "${BLUE}Benchmarking $app (port $port)...${NC}"
        echo "=================================="
        
        if ! check_application "$app" "$port"; then
            echo -e "${YELLOW}Attempting to start $app...${NC}"
            start_application "$app" "$port"
            
            if ! check_application "$app" "$port"; then
                echo -e "${RED}❌ Failed to start $app, skipping...${NC}"
                continue
            fi
        fi
        
        # Run benchmarks
        warmup_application "$app" "$port"
        benchmark_single_request "$app" "$port"
        benchmark_concurrent_requests "$app" "$port"
        benchmark_memory_usage "$app" "$port"
        benchmark_database_performance "$app" "$port"
        
        echo ""
    done
    
    # Generate final report
    generate_report
    
    echo -e "${GREEN}🎉 Performance benchmarking complete!${NC}"
    echo ""
    echo -e "${YELLOW}Results saved to: $RESULTS_DIR${NC}"
    echo -e "${YELLOW}View report: ${RESULTS_DIR}/performance_report_${TIMESTAMP}.md${NC}"
}

# Run main function
main "$@"
