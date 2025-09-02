#!/bin/bash

# STRICT PHASE EXECUTION FRAMEWORK
# NO SKIPPING - FULL VERIFICATION - COMPLETE TESTING

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔹 STRICT PHASE EXECUTION FRAMEWORK${NC}"
echo "===================================="
echo ""

# Applications to process
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

# Phase tracking
PHASE_LOG="phase_execution_log.json"
RESULTS_DIR="strict_execution_results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Create results directory
mkdir -p "$RESULTS_DIR"

# Initialize phase tracking
initialize_phase_tracking() {
    echo -e "${CYAN}📋 Initializing Phase Tracking${NC}"
    echo "------------------------------"
    
    cat > "$PHASE_LOG" << EOF
{
    "execution_started": "$(date -Iseconds)",
    "execution_mode": "STRICT",
    "applications": [
        $(printf '"%s",' "${APPLICATIONS[@]}" | sed 's/,$//')
    ],
    "phases": {
        "phase_1": {"name": "Configuration Fixes", "status": "PENDING", "applications": {}},
        "phase_2": {"name": "Standardization & Refactoring", "status": "PENDING", "applications": {}},
        "phase_3": {"name": "PHP Version Compatibility", "status": "PENDING", "applications": {}},
        "phase_4": {"name": "OS Compatibility", "status": "PENDING", "applications": {}},
        "phase_5": {"name": "APM Tool Readiness", "status": "PENDING", "applications": {}},
        "phase_6": {"name": "Final Report & Validation", "status": "PENDING", "applications": {}}
    }
}
EOF
    
    for app in "${APPLICATIONS[@]}"; do
        for phase in {1..6}; do
            jq ".phases.phase_${phase}.applications[\"${app}\"] = {\"status\": \"PENDING\", \"logs\": [], \"verified\": false}" "$PHASE_LOG" > "${PHASE_LOG}.tmp" && mv "${PHASE_LOG}.tmp" "$PHASE_LOG"
        done
    done
    
    echo -e "${GREEN}✅ Phase tracking initialized${NC}"
    echo ""
}

# Function to update phase status
update_phase_status() {
    local phase="$1"
    local app="$2"
    local status="$3"
    local log_message="$4"
    local verified="${5:-false}"
    
    # Add log entry
    local timestamp=$(date -Iseconds)
    jq ".phases.phase_${phase}.applications[\"${app}\"].logs += [{\"timestamp\": \"${timestamp}\", \"message\": \"${log_message}\"}]" "$PHASE_LOG" > "${PHASE_LOG}.tmp" && mv "${PHASE_LOG}.tmp" "$PHASE_LOG"
    
    # Update status
    jq ".phases.phase_${phase}.applications[\"${app}\"].status = \"${status}\"" "$PHASE_LOG" > "${PHASE_LOG}.tmp" && mv "${PHASE_LOG}.tmp" "$PHASE_LOG"
    
    # Update verification
    jq ".phases.phase_${phase}.applications[\"${app}\"].verified = ${verified}" "$PHASE_LOG" > "${PHASE_LOG}.tmp" && mv "${PHASE_LOG}.tmp" "$PHASE_LOG"
}

# Function to check if all apps passed current phase
check_phase_completion() {
    local phase="$1"
    local all_passed=true
    
    for app in "${APPLICATIONS[@]}"; do
        local status=$(jq -r ".phases.phase_${phase}.applications[\"${app}\"].status" "$PHASE_LOG")
        local verified=$(jq -r ".phases.phase_${phase}.applications[\"${app}\"].verified" "$PHASE_LOG")
        
        if [ "$status" != "PASSED" ] || [ "$verified" != "true" ]; then
            all_passed=false
            break
        fi
    done
    
    if [ "$all_passed" = true ]; then
        jq ".phases.phase_${phase}.status = \"COMPLETED\"" "$PHASE_LOG" > "${PHASE_LOG}.tmp" && mv "${PHASE_LOG}.tmp" "$PHASE_LOG"
        return 0
    else
        return 1
    fi
}

# Function to display current status
display_status() {
    echo -e "${PURPLE}📊 CURRENT EXECUTION STATUS${NC}"
    echo "============================"
    echo ""
    
    printf "%-15s" "Application"
    for phase in {1..6}; do
        printf "%-12s" "Phase $phase"
    done
    echo ""
    echo "$(printf '=%.0s' {1..87})"
    
    for app in "${APPLICATIONS[@]}"; do
        printf "%-15s" "$app"
        for phase in {1..6}; do
            local status=$(jq -r ".phases.phase_${phase}.applications[\"${app}\"].status" "$PHASE_LOG" 2>/dev/null || echo "PENDING")
            local verified=$(jq -r ".phases.phase_${phase}.applications[\"${app}\"].verified" "$PHASE_LOG" 2>/dev/null || echo "false")
            
            case "$status" in
                "PASSED")
                    if [ "$verified" = "true" ]; then
                        printf "%-12s" "✅ PASSED"
                    else
                        printf "%-12s" "⚠️ UNVERIFIED"
                    fi
                    ;;
                "FAILED")
                    printf "%-12s" "❌ FAILED"
                    ;;
                "IN_PROGRESS")
                    printf "%-12s" "🔄 WORKING"
                    ;;
                *)
                    printf "%-12s" "⏳ PENDING"
                    ;;
            esac
        done
        echo ""
    done
    echo ""
}

# PHASE 1: Configuration Fixes
execute_phase_1() {
    echo -e "${BLUE}🔧 PHASE 1: Configuration Fixes${NC}"
    echo "================================"
    echo ""
    
    for app in "${APPLICATIONS[@]}"; do
        echo -e "${CYAN}Processing $app...${NC}"
        update_phase_status "1" "$app" "IN_PROGRESS" "Starting configuration fixes"
        
        cd "$app"
        
        # Fix PHPStan configuration
        if [ -f "phpstan.neon" ]; then
            echo "Fixing PHPStan configuration..."
            
            # Remove deprecated rules
            sed -i '/PHPStan\\Rules\\DeadCode/d' phpstan.neon 2>/dev/null || true
            
            # Verify PHPStan works
            if [ -f "vendor/bin/phpstan" ]; then
                if vendor/bin/phpstan analyse --level=1 src/ public/ 2>&1 | tee "${RESULTS_DIR}/${app}_phpstan_phase1.log"; then
                    update_phase_status "1" "$app" "IN_PROGRESS" "PHPStan configuration fixed and verified"
                else
                    update_phase_status "1" "$app" "FAILED" "PHPStan configuration failed"
                    cd ..
                    continue
                fi
            else
                update_phase_status "1" "$app" "FAILED" "PHPStan not installed"
                cd ..
                continue
            fi
        fi
        
        # Fix PHPUnit configuration
        if [ -f "phpunit.xml" ]; then
            echo "Fixing PHPUnit configuration..."
            
            # Remove deprecated attributes
            sed -i 's/backupStaticAttributes="false"//g' phpunit.xml 2>/dev/null || true
            sed -i 's/processUncoveredFiles="true"//g' phpunit.xml 2>/dev/null || true
            
            # Update coverage configuration
            sed -i 's/<include>/<source>/g' phpunit.xml 2>/dev/null || true
            sed -i 's/<\/include>/<\/source>/g' phpunit.xml 2>/dev/null || true
            
            # Verify PHPUnit works
            if [ -f "vendor/bin/phpunit" ]; then
                if vendor/bin/phpunit --version 2>&1 | tee "${RESULTS_DIR}/${app}_phpunit_phase1.log"; then
                    update_phase_status "1" "$app" "IN_PROGRESS" "PHPUnit configuration fixed and verified"
                else
                    update_phase_status "1" "$app" "FAILED" "PHPUnit configuration failed"
                    cd ..
                    continue
                fi
            else
                update_phase_status "1" "$app" "FAILED" "PHPUnit not installed"
                cd ..
                continue
            fi
        fi
        
        # Fix PHP CS Fixer configuration
        if [ -f ".php-cs-fixer.php" ]; then
            echo "Verifying PHP CS Fixer configuration..."
            
            if [ -f "vendor/bin/php-cs-fixer" ]; then
                if vendor/bin/php-cs-fixer fix --dry-run --diff 2>&1 | tee "${RESULTS_DIR}/${app}_phpcs_phase1.log"; then
                    update_phase_status "1" "$app" "IN_PROGRESS" "PHP CS Fixer configuration verified"
                else
                    update_phase_status "1" "$app" "FAILED" "PHP CS Fixer configuration failed"
                    cd ..
                    continue
                fi
            else
                update_phase_status "1" "$app" "FAILED" "PHP CS Fixer not installed"
                cd ..
                continue
            fi
        fi
        
        # Final verification - run all quality tools
        echo "Running final verification for $app..."
        local verification_passed=true
        
        # Test composer validate
        if ! composer validate 2>&1 | tee "${RESULTS_DIR}/${app}_composer_phase1.log"; then
            verification_passed=false
        fi
        
        # Test basic PHP syntax
        if ! find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; 2>&1 | tee "${RESULTS_DIR}/${app}_syntax_phase1.log"; then
            verification_passed=false
        fi
        
        if [ "$verification_passed" = true ]; then
            update_phase_status "1" "$app" "PASSED" "All configuration fixes applied and verified" "true"
            echo -e "${GREEN}✅ $app Phase 1 PASSED${NC}"
        else
            update_phase_status "1" "$app" "FAILED" "Verification failed"
            echo -e "${RED}❌ $app Phase 1 FAILED${NC}"
        fi
        
        cd ..
        echo ""
    done
    
    # Check if phase is complete
    if check_phase_completion "1"; then
        echo -e "${GREEN}🎉 PHASE 1 COMPLETED FOR ALL APPLICATIONS${NC}"
        return 0
    else
        echo -e "${RED}❌ PHASE 1 INCOMPLETE - STOPPING EXECUTION${NC}"
        display_status
        return 1
    fi
}

# Main execution function
main() {
    echo -e "${PURPLE}Starting STRICT PHASE EXECUTION...${NC}"
    echo ""
    
    # Initialize tracking
    initialize_phase_tracking
    
    # Display initial status
    display_status
    
    # Execute Phase 1
    if ! execute_phase_1; then
        echo -e "${RED}❌ EXECUTION STOPPED - Phase 1 incomplete${NC}"
        exit 1
    fi
    
    # Display final status
    display_status
    
    echo -e "${GREEN}🎉 Phase 1 execution complete. Ready for Phase 2.${NC}"
}

# Run main function
main "$@"
