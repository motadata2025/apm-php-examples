#!/bin/bash
# validate.sh - Codeigniter-app Application Validator Wrapper
# Runs the PHP validator and outputs structured JSON

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VALIDATOR_PHP="$SCRIPT_DIR/validator.php"
ENV_FILE="$SCRIPT_DIR/.env"
ENV_EXAMPLE="$SCRIPT_DIR/.env.example"

# Create augment directories
AUGMENT_DIR="$SCRIPT_DIR/../augment/codeigniter"
RESULTS_DIR="$AUGMENT_DIR/validation_results"
LOGS_DIR="$AUGMENT_DIR/logs"

mkdir -p "$RESULTS_DIR" "$LOGS_DIR"

# Generate timestamp for result file
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULT_FILE="$RESULTS_DIR/${TIMESTAMP}.json"

# Ensure .env file exists
if [[ ! -f "$ENV_FILE" ]]; then
    if [[ -f "$ENV_EXAMPLE" ]]; then
        # Creating .env from .env.example (silent)
        cp "$ENV_EXAMPLE" "$ENV_FILE"
    else
        echo "ERROR: No .env or .env.example file found" >&2
        echo '{"app":"codeigniter-app","success":false,"error":"env_file_missing","timestamp":'$(date +%s)'}' | tee "$RESULT_FILE"
        exit 1
    fi
fi

# Check if validator exists
if [[ ! -f "$VALIDATOR_PHP" ]]; then
    echo "ERROR: Validator script not found: $VALIDATOR_PHP" >&2
    echo '{"app":"codeigniter-app","success":false,"error":"validator_missing","timestamp":'$(date +%s)'}' | tee "$RESULT_FILE"
    exit 1
fi

# Run the PHP validator with timeout and save results
if timeout 120 php "$VALIDATOR_PHP" | tee "$RESULT_FILE"; then
    echo "Validation results saved to: $RESULT_FILE" >&2
    exit 0
else
    exit_code=$?
    if [[ $exit_code -eq 124 ]]; then
        echo '{"app":"codeigniter-app","success":false,"error":"timeout","timeout_seconds":120,"timestamp":'$(date +%s)'}' | tee "$RESULT_FILE"
    fi
    echo "Validation failed. Results saved to: $RESULT_FILE" >&2
    exit $exit_code
fi
