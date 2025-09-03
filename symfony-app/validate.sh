#!/bin/bash
# Symfony App Validation Wrapper Script
# Ensures bootstrap has been run and executes the APM validation command

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$SCRIPT_DIR/../augment/logs"
RESULTS_DIR="$SCRIPT_DIR/../augment/validation_results"
ERROR_LOG="$LOG_DIR/validate-error.log"

# Ensure directories exist
mkdir -p "$LOG_DIR" "$RESULTS_DIR"

echo "Symfony App Validation Starting..." | tee "$ERROR_LOG"
echo "Working directory: $SCRIPT_DIR" | tee -a "$ERROR_LOG"
echo "Timestamp: $(date)" | tee -a "$ERROR_LOG"

# Function to log and exit on error
log_error_and_exit() {
    local message="$1"
    local exit_code="${2:-1}"
    echo "ERROR: $message" | tee -a "$ERROR_LOG"
    echo "Validation failed at $(date)" | tee -a "$ERROR_LOG"
    exit "$exit_code"
}

# Check if bootstrap has been run
if [ ! -f "vendor/autoload.php" ] || [ ! -f "bin/console" ]; then
    echo "Bootstrap not detected, running bootstrap.sh..." | tee -a "$ERROR_LOG"

    if [ ! -f "bootstrap.sh" ]; then
        log_error_and_exit "bootstrap.sh not found"
    fi

    if ! bash bootstrap.sh 2>&1 | tee -a "$ERROR_LOG"; then
        log_error_and_exit "Bootstrap failed"
    fi

    echo "Bootstrap completed successfully" | tee -a "$ERROR_LOG"
fi

# Verify console command exists
if [ ! -f "bin/console" ]; then
    log_error_and_exit "bin/console not found after bootstrap"
fi

if [ ! -x "bin/console" ]; then
    chmod +x bin/console
fi

# Generate timestamp for result file
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULT_FILE="$RESULTS_DIR/symfony-app-${TIMESTAMP}.json"

echo "Running APM validation command..." | tee -a "$ERROR_LOG"
echo "Output will be saved to: $RESULT_FILE" | tee -a "$ERROR_LOG"

# Run the validation command with timeout
if timeout 120 php bin/console app:apm-validate 2>&1 | tee "$RESULT_FILE"; then
    echo "Validation completed successfully at $(date)" | tee -a "$ERROR_LOG"
    echo "Results saved to: $RESULT_FILE" | tee -a "$ERROR_LOG"

    # Check if the result indicates success
    if grep -q '"errors":\s*\[\s*\]' "$RESULT_FILE" 2>/dev/null; then
        echo "All validation checks passed" | tee -a "$ERROR_LOG"
        exit 0
    else
        echo "Some validation checks failed - see results file" | tee -a "$ERROR_LOG"
        exit 1
    fi
else
    exit_code=$?
    if [ $exit_code -eq 124 ]; then
        echo "Validation timed out after 120 seconds" | tee -a "$ERROR_LOG"
        echo '{"app":"symfony-app","error":"timeout","timeout_seconds":120,"timestamp":'$(date +%s)'}' > "$RESULT_FILE"
    else
        echo "Validation command failed with exit code: $exit_code" | tee -a "$ERROR_LOG"
    fi
    exit $exit_code
fi
