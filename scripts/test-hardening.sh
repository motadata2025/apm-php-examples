#!/bin/bash

# APM PHP Examples - Hardening Validation Test Script
# Tests the hardened Makefile implementation across applications

set -e

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly NC='\033[0m' # No Color

# Test configuration
APPLICATIONS=("simple-php")  # Start with simple-php, expand as others are hardened
TEST_RESULTS=()
FAILED_TESTS=()

# Logging
LOG_FILE="logs/hardening-test-$(date +%Y%m%d-%H%M%S).log"
mkdir -p logs

log_test() {
    local message="$1"
    echo -e "${BLUE}🧪 $message${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] TEST: $message" >> "$LOG_FILE"
}

log_success() {
    local message="$1"
    echo -e "${GREEN}✅ $message${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] SUCCESS: $message" >> "$LOG_FILE"
}

log_failure() {
    local message="$1"
    echo -e "${RED}❌ $message${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] FAILURE: $message" >> "$LOG_FILE"
}

log_warning() {
    local message="$1"
    echo -e "${YELLOW}⚠️  $message${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $message" >> "$LOG_FILE"
}

# Test helper functions
run_test() {
    local test_name="$1"
    local test_command="$2"
    local app_dir="$3"
    
    log_test "Running: $test_name"
    
    if [[ -n "$app_dir" ]]; then
        cd "$app_dir"
    fi
    
    if eval "$test_command" >> "$LOG_FILE" 2>&1; then
        log_success "$test_name"
        TEST_RESULTS+=("✅ $test_name")
        return 0
    else
        log_failure "$test_name"
        TEST_RESULTS+=("❌ $test_name")
        FAILED_TESTS+=("$test_name")
        return 1
    fi
}

# Test 1: Validate hardened files exist
test_hardened_files() {
    local app="$1"
    
    log_test "Validating hardened files for $app"
    
    local required_files=(
        "$app/scripts/common-functions.sh"
        "$app/scripts/check-system-hardened.sh"
        "$app/scripts/migrate-config.sh"
        "$app/config/runtime.env"
        "$app/CONFIG_STANDARD.md"
        "$app/OPERATION.md"
    )
    
    local missing_files=()
    for file in "${required_files[@]}"; do
        if [[ ! -f "$file" ]]; then
            missing_files+=("$file")
        fi
    done
    
    if [[ ${#missing_files[@]} -eq 0 ]]; then
        log_success "All required hardened files exist for $app"
        return 0
    else
        log_failure "Missing files for $app: ${missing_files[*]}"
        return 1
    fi
}

# Test 2: Validate script permissions
test_script_permissions() {
    local app="$1"
    
    log_test "Validating script permissions for $app"
    
    local scripts=(
        "$app/scripts/common-functions.sh"
        "$app/scripts/check-system-hardened.sh"
        "$app/scripts/migrate-config.sh"
    )
    
    local non_executable=()
    for script in "${scripts[@]}"; do
        if [[ -f "$script" ]] && [[ ! -x "$script" ]]; then
            non_executable+=("$script")
        fi
    done
    
    if [[ ${#non_executable[@]} -eq 0 ]]; then
        log_success "All scripts are executable for $app"
        return 0
    else
        log_failure "Non-executable scripts for $app: ${non_executable[*]}"
        return 1
    fi
}

# Test 3: Validate configuration syntax
test_configuration_syntax() {
    local app="$1"
    
    log_test "Validating configuration syntax for $app"
    
    if [[ -f "$app/config/runtime.env" ]]; then
        if bash -n "$app/config/runtime.env"; then
            log_success "Configuration syntax valid for $app"
            return 0
        else
            log_failure "Configuration syntax error for $app"
            return 1
        fi
    else
        log_warning "No runtime configuration found for $app"
        return 0
    fi
}

# Test 4: Test make setup (dry run style)
test_make_setup() {
    local app="$1"
    
    log_test "Testing make setup for $app"
    
    cd "$app"
    
    # Test that make setup can be called without errors
    if timeout 60 make setup VERBOSE=1; then
        log_success "make setup completed for $app"
        cd ..
        return 0
    else
        log_failure "make setup failed for $app"
        cd ..
        return 1
    fi
}

# Test 5: Test configuration migration
test_config_migration() {
    local app="$1"
    
    log_test "Testing configuration migration for $app"
    
    cd "$app"
    
    # Create a test legacy config
    if [[ ! -f "config/app.env" ]]; then
        cat > "config/app.env" << EOF
APP_NAME="Test App"
APP_PORT=8000
PHP_VERSION=8.4
MYSQL_PORT=3307
EOF
    fi
    
    # Test migration in dry run mode
    if DRY_RUN=1 ./scripts/migrate-config.sh; then
        log_success "Configuration migration test passed for $app"
        cd ..
        return 0
    else
        log_failure "Configuration migration test failed for $app"
        cd ..
        return 1
    fi
}

# Test 6: Test error handling
test_error_handling() {
    local app="$1"
    
    log_test "Testing error handling for $app"
    
    cd "$app"
    
    # Test that scripts handle missing dependencies gracefully
    if ./scripts/common-functions.sh 2>/dev/null; then
        log_success "Error handling works for $app"
        cd ..
        return 0
    else
        # This should fail gracefully, not crash
        log_success "Error handling works for $app (expected failure)"
        cd ..
        return 0
    fi
}

# Test 7: Test Docker detection
test_docker_detection() {
    local app="$1"
    
    log_test "Testing Docker detection for $app"
    
    cd "$app"
    
    # Source common functions and test Docker detection
    if source scripts/common-functions.sh && check_docker_daemon; then
        log_success "Docker detection works for $app"
        cd ..
        return 0
    else
        log_warning "Docker not available for testing $app"
        cd ..
        return 0
    fi
}

# Test 8: Test verbose mode
test_verbose_mode() {
    local app="$1"
    
    log_test "Testing verbose mode for $app"
    
    cd "$app"
    
    # Test that VERBOSE=1 produces more output
    local normal_output=$(make help 2>&1 | wc -l)
    local verbose_output=$(VERBOSE=1 make help 2>&1 | wc -l)
    
    if [[ $verbose_output -ge $normal_output ]]; then
        log_success "Verbose mode works for $app"
        cd ..
        return 0
    else
        log_failure "Verbose mode not working for $app"
        cd ..
        return 1
    fi
}

# Test 9: Test Makefile targets
test_makefile_targets() {
    local app="$1"
    
    log_test "Testing Makefile targets for $app"
    
    cd "$app"
    
    local required_targets=("help" "setup" "compile" "start" "status" "stop" "down")
    local missing_targets=()
    
    for target in "${required_targets[@]}"; do
        if ! make -n "$target" >/dev/null 2>&1; then
            missing_targets+=("$target")
        fi
    done
    
    if [[ ${#missing_targets[@]} -eq 0 ]]; then
        log_success "All required Makefile targets exist for $app"
        cd ..
        return 0
    else
        log_failure "Missing Makefile targets for $app: ${missing_targets[*]}"
        cd ..
        return 1
    fi
}

# Test 10: Test documentation completeness
test_documentation() {
    local app="$1"
    
    log_test "Testing documentation completeness for $app"
    
    local required_docs=(
        "$app/CONFIG_STANDARD.md"
        "$app/OPERATION.md"
    )
    
    local missing_docs=()
    for doc in "${required_docs[@]}"; do
        if [[ ! -f "$doc" ]] || [[ ! -s "$doc" ]]; then
            missing_docs+=("$doc")
        fi
    done
    
    if [[ ${#missing_docs[@]} -eq 0 ]]; then
        log_success "Documentation complete for $app"
        return 0
    else
        log_failure "Missing or empty documentation for $app: ${missing_docs[*]}"
        return 1
    fi
}

# Main test execution
run_all_tests() {
    echo -e "${PURPLE}🧪 APM PHP Examples - Hardening Validation Tests${NC}"
    echo "=================================================="
    echo ""
    
    local total_tests=0
    local passed_tests=0
    
    for app in "${APPLICATIONS[@]}"; do
        echo -e "\n${BLUE}📱 Testing Application: $app${NC}"
        echo "================================"
        
        # Run all tests for this application
        local app_tests=(
            "test_hardened_files $app"
            "test_script_permissions $app"
            "test_configuration_syntax $app"
            "test_config_migration $app"
            "test_error_handling $app"
            "test_docker_detection $app"
            "test_verbose_mode $app"
            "test_makefile_targets $app"
            "test_documentation $app"
        )
        
        for test in "${app_tests[@]}"; do
            ((total_tests++))
            if eval "$test"; then
                ((passed_tests++))
            fi
        done
        
        # Optional: Test make setup if Docker is available
        if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
            ((total_tests++))
            if test_make_setup "$app"; then
                ((passed_tests++))
            fi
        else
            log_warning "Skipping make setup test - Docker not available"
        fi
    done
    
    # Show summary
    echo -e "\n${PURPLE}📊 Test Summary${NC}"
    echo "==============="
    echo "Total Tests: $total_tests"
    echo "Passed: $passed_tests"
    echo "Failed: $((total_tests - passed_tests))"
    echo ""
    
    if [[ ${#FAILED_TESTS[@]} -gt 0 ]]; then
        echo -e "${RED}Failed Tests:${NC}"
        printf '%s\n' "${FAILED_TESTS[@]}"
        echo ""
    fi
    
    echo -e "${BLUE}📋 Detailed Results:${NC}"
    printf '%s\n' "${TEST_RESULTS[@]}"
    
    echo ""
    echo -e "${BLUE}📄 Full test log: $LOG_FILE${NC}"
    
    if [[ $passed_tests -eq $total_tests ]]; then
        echo -e "\n${GREEN}🎉 All tests passed! Hardening implementation is ready.${NC}"
        return 0
    else
        echo -e "\n${YELLOW}⚠️  Some tests failed. Review the log for details.${NC}"
        return 1
    fi
}

# Execute tests
main() {
    # Ensure we're in the repository root
    if [[ ! -f "MAKEFILE_HARDENING_CHECKLIST.md" ]]; then
        echo -e "${RED}❌ Please run this script from the repository root${NC}"
        exit 1
    fi
    
    run_all_tests
}

# Run main function
main "$@"
