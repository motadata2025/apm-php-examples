#!/bin/bash
# validate.sh - Laravel-app Application Validator Wrapper
# Runs the PHP validator and outputs structured JSON

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VALIDATOR_PHP="$SCRIPT_DIR/validator.php"
ENV_FILE="$SCRIPT_DIR/.env"
ENV_EXAMPLE="$SCRIPT_DIR/.env.example"

# Ensure .env file exists
if [[ ! -f "$ENV_FILE" ]]; then
    if [[ -f "$ENV_EXAMPLE" ]]; then
        echo "Creating .env from .env.example..." >&2
        cp "$ENV_EXAMPLE" "$ENV_FILE"
    else
        echo "ERROR: No .env or .env.example file found" >&2
        echo '{"app":"laravel-app","success":false,"error":"env_file_missing","timestamp":'$(date +%s)'}' 
        exit 1
    fi
fi

# Check if validator exists
if [[ ! -f "$VALIDATOR_PHP" ]]; then
    echo "ERROR: Validator script not found: $VALIDATOR_PHP" >&2
    echo '{"app":"laravel-app","success":false,"error":"validator_missing","timestamp":'$(date +%s)'}'
    exit 1
fi

# Run the PHP validator with timeout
if timeout 120 php "$VALIDATOR_PHP"; then
    exit 0
else
    exit_code=$?
    if [[ $exit_code -eq 124 ]]; then
        echo '{"app":"laravel-app","success":false,"error":"timeout","timeout_seconds":120,"timestamp":'$(date +%s)'}'
    fi
    exit $exit_code
fi
