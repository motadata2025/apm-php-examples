#!/bin/bash
# validate_all.sh - Run validation for all APM PHP applications
# Executes each app's validate.sh and collects JSON results

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
RESULTS_DIR="$REPO_ROOT/augment/validation_results"
TIMESTAMP=$(date +%s)

# Application directories
APPS=(
    "simple-php"
    "slim-framework"
    "symfony-app"
    "laravel-app"
    "codeigniter-app"
)

echo "🧪 Running validation for all APM PHP applications..."
echo "Results will be saved to: $RESULTS_DIR"
echo "Timestamp: $TIMESTAMP"

# Create results directory
mkdir -p "$RESULTS_DIR"

# Summary variables
total_apps=0
successful_apps=0
failed_apps=0
declare -a success_list=()
declare -a failure_list=()

# Function to validate a single app
validate_app() {
    local app_name="$1"
    local app_dir="$REPO_ROOT/$app_name"
    local validate_script="$app_dir/validate.sh"
    local result_file="$RESULTS_DIR/${TIMESTAMP}-${app_name}.json"
    
    echo "📱 Validating $app_name..."
    
    # Check if app directory exists
    if [[ ! -d "$app_dir" ]]; then
        echo "❌ App directory not found: $app_dir"
        echo '{"app":"'$app_name'","error":"directory_not_found","timestamp":'$TIMESTAMP'}' > "$result_file"
        return 1
    fi
    
    # Check if validate script exists
    if [[ ! -f "$validate_script" ]]; then
        echo "❌ Validate script not found: $validate_script"
        echo '{"app":"'$app_name'","error":"validate_script_not_found","timestamp":'$TIMESTAMP'}' > "$result_file"
        return 1
    fi
    
    # Make sure script is executable
    chmod +x "$validate_script"
    
    # Run validation and capture output
    echo "  Running: $validate_script"
    if cd "$app_dir" && timeout 120 ./validate.sh > "$result_file" 2>&1; then
        echo "  ✅ $app_name validation successful"
        return 0
    else
        local exit_code=$?
        echo "  ❌ $app_name validation failed (exit code: $exit_code)"
        
        # If the output isn't valid JSON, wrap it
        if ! jq . "$result_file" >/dev/null 2>&1; then
            local raw_output
            raw_output=$(cat "$result_file" 2>/dev/null || echo "No output captured")
            cat > "$result_file" <<EOF
{
  "app": "$app_name",
  "success": false,
  "error": "validation_failed",
  "exit_code": $exit_code,
  "raw_output": $(echo "$raw_output" | jq -R -s .),
  "timestamp": $TIMESTAMP
}
EOF
        fi
        return 1
    fi
}

# Validate each application
for app in "${APPS[@]}"; do
    total_apps=$((total_apps + 1))
    
    if validate_app "$app"; then
        successful_apps=$((successful_apps + 1))
        success_list+=("$app")
    else
        failed_apps=$((failed_apps + 1))
        failure_list+=("$app")
    fi
    
    echo ""
done

# Generate summary
echo "📊 Validation Summary:"
echo "  Total applications: $total_apps"
echo "  Successful: $successful_apps"
echo "  Failed: $failed_apps"

if [[ ${#success_list[@]} -gt 0 ]]; then
    echo "  ✅ Successful apps: ${success_list[*]}"
fi

if [[ ${#failure_list[@]} -gt 0 ]]; then
    echo "  ❌ Failed apps: ${failure_list[*]}"
fi

# Create summary JSON
summary_file="$RESULTS_DIR/${TIMESTAMP}-summary.json"
cat > "$summary_file" <<EOF
{
  "timestamp": $TIMESTAMP,
  "total_apps": $total_apps,
  "successful_apps": $successful_apps,
  "failed_apps": $failed_apps,
  "success_list": $(printf '%s\n' "${success_list[@]}" | jq -R . | jq -s .),
  "failure_list": $(printf '%s\n' "${failure_list[@]}" | jq -R . | jq -s .),
  "results_directory": "$RESULTS_DIR",
  "individual_results": [
$(for app in "${APPS[@]}"; do
    echo "    \"${TIMESTAMP}-${app}.json\""
done | sed '$!s/$/,/')
  ]
}
EOF

echo ""
echo "📝 Summary saved to: $summary_file"
echo "📁 Individual results in: $RESULTS_DIR"

# List all result files
echo ""
echo "📋 Result files:"
ls -la "$RESULTS_DIR"/${TIMESTAMP}-*.json

# Exit with appropriate code
if [[ $failed_apps -eq 0 ]]; then
    echo ""
    echo "🎉 All applications validated successfully!"
    exit 0
else
    echo ""
    echo "⚠️  Some applications failed validation. Check individual result files for details."
    exit 1
fi
