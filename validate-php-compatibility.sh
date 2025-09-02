#!/bin/bash

# PHP Version Validation Script
# Purpose: Validate PHP 8.1-8.4 compatibility and provide version-specific guidance

echo "🔧 PHP VERSION COMPATIBILITY VALIDATION"
echo "======================================="
echo ""

# Function to validate PHP version range
validate_php_version() {
    local current_version=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    local version_valid=false
    
    echo "📋 Current PHP Environment:"
    echo "  Version: $(php -v | head -1)"
    echo "  Major.Minor: $current_version"
    echo "  Thread Safety: $(php -r "echo defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';")"
    echo ""
    
    # Check if version is in supported range
    case $current_version in
        "8.1"|"8.2"|"8.3"|"8.4")
            version_valid=true
            echo "✅ PHP $current_version is fully supported"
            ;;
        *)
            if [[ $(echo "$current_version < 8.1" | bc -l 2>/dev/null || echo "1") == "1" ]]; then
                echo "❌ PHP $current_version is too old (minimum: PHP 8.1)"
            else
                echo "⚠️  PHP $current_version is newer than tested (may work but not guaranteed)"
            fi
            ;;
    esac
    
    return $version_valid
}

# Function to check required extensions
check_extensions() {
    echo "📦 Checking Required PHP Extensions:"
    
    local required_extensions=("json" "mbstring" "curl" "openssl" "pdo")
    local optional_extensions=("redis" "gd" "zip" "xml")
    local missing_required=()
    local missing_optional=()
    
    # Check required extensions
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo "  ✅ $ext (required)"
        else
            echo "  ❌ $ext (required) - MISSING"
            missing_required+=("$ext")
        fi
    done
    
    # Check optional extensions
    for ext in "${optional_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            echo "  ✅ $ext (optional)"
        else
            echo "  ⚠️  $ext (optional) - missing"
            missing_optional+=("$ext")
        fi
    done
    
    echo ""
    
    if [ ${#missing_required[@]} -eq 0 ]; then
        echo "✅ All required extensions are available"
        return 0
    else
        echo "❌ Missing required extensions: ${missing_required[*]}"
        return 1
    fi
}

# Function to test application compatibility
test_application_compatibility() {
    echo "🧪 Testing Application Compatibility:"
    
    local applications=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
    local working_apps=0
    local total_apps=${#applications[@]}
    
    for i in "${!applications[@]}"; do
        local app="${applications[$i]}"
        local port=$((8080 + i))
        
        echo "  Testing $app..."
        
        if [ ! -d "$app" ]; then
            echo "    ❌ Directory not found"
            continue
        fi
        
        if [ ! -f "$app/public/index.php" ]; then
            echo "    ❌ index.php not found"
            continue
        fi
        
        # Check syntax
        if ! php -l "$app/public/index.php" >/dev/null 2>&1; then
            echo "    ❌ Syntax error"
            continue
        fi
        
        # Test server
        cd "$app"
        timeout 5s php -S 127.0.0.1:$port -t public >/dev/null 2>&1 &
        sleep 2
        
        local status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$port/ 2>/dev/null || echo "000")
        
        killall php 2>/dev/null || true
        cd ..
        
        if [ "$status" = "200" ]; then
            echo "    ✅ Working (HTTP $status)"
            ((working_apps++))
        else
            echo "    ❌ Failed (HTTP $status)"
        fi
    done
    
    echo ""
    echo "📊 Compatibility Results: $working_apps/$total_apps applications working"
    
    if [ $working_apps -eq $total_apps ]; then
        echo "✅ All applications are compatible"
        return 0
    else
        echo "❌ Some applications have compatibility issues"
        return 1
    fi
}

# Function to provide version-specific guidance
provide_guidance() {
    local current_version=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    
    echo "📋 Version-Specific Guidance:"
    echo ""
    
    case $current_version in
        "8.1")
            echo "  🔹 PHP 8.1 - Minimum Supported Version"
            echo "    • All applications tested and working"
            echo "    • Stable for production use"
            echo "    • Consider upgrading to 8.3+ for better performance"
            ;;
        "8.2")
            echo "  🔹 PHP 8.2 - Recommended Version"
            echo "    • Excellent performance improvements"
            echo "    • All features fully supported"
            echo "    • Good choice for production"
            ;;
        "8.3")
            echo "  🔹 PHP 8.3 - Current Recommended Version"
            echo "    • Latest stable with best performance"
            echo "    • All applications optimized for this version"
            echo "    • Recommended for new projects"
            ;;
        "8.4")
            echo "  🔹 PHP 8.4 - Latest Version"
            echo "    • Cutting-edge features"
            echo "    • Forward compatibility tested"
            echo "    • Suitable for development and testing"
            ;;
        *)
            echo "  ⚠️  PHP $current_version - Non-standard Version"
            echo "    • Compatibility not guaranteed"
            echo "    • Please use PHP 8.1-8.4 for best results"
            ;;
    esac
    
    echo ""
    echo "🔧 CLI Server Usage:"
    echo "  # Start any application with current PHP version"
    echo "  ./start-cli-server.sh [app-name] [ip] [port]"
    echo ""
    echo "  # Examples:"
    echo "  ./start-cli-server.sh simple-php 127.0.0.1 8080"
    echo "  ./start-cli-server.sh laravel-app 0.0.0.0 8081"
    echo ""
}

# Main execution
echo "Starting PHP version compatibility validation..."
echo ""

# Validate PHP version
if validate_php_version; then
    version_ok=true
else
    version_ok=false
fi

echo ""

# Check extensions
if check_extensions; then
    extensions_ok=true
else
    extensions_ok=false
fi

echo ""

# Test applications
if test_application_compatibility; then
    apps_ok=true
else
    apps_ok=false
fi

echo ""

# Provide guidance
provide_guidance

# Final status
echo "🎯 FINAL VALIDATION RESULTS:"
echo "=========================="

if $version_ok && $extensions_ok && $apps_ok; then
    echo "✅ FULLY COMPATIBLE - All systems ready for production"
    echo ""
    echo "🚀 Ready for Phase 4: Multi-Linux Compatibility Testing"
    exit 0
else
    echo "❌ COMPATIBILITY ISSUES DETECTED"
    echo ""
    echo "Issues found:"
    $version_ok || echo "  • PHP version compatibility"
    $extensions_ok || echo "  • Missing required extensions"
    $apps_ok || echo "  • Application compatibility problems"
    echo ""
    echo "Please resolve these issues before proceeding to Phase 4"
    exit 1
fi
