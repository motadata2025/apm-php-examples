#!/bin/bash
# Deployment Verification Script

echo "🔍 DEPLOYMENT VERIFICATION"
echo "========================="
echo ""

# Check PHP installation
echo "📋 PHP Installation Check:"
if command -v php >/dev/null 2>&1; then
    echo "  ✅ PHP found: $(php -v | head -1)"
    echo "  ✅ Thread Safety: $(php -r "echo defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';")"
else
    echo "  ❌ PHP not found in PATH"
    exit 1
fi

# Check Composer
echo ""
echo "📦 Composer Check:"
if command -v composer >/dev/null 2>&1; then
    echo "  ✅ Composer found: $(composer --version)"
else
    echo "  ❌ Composer not found"
    exit 1
fi

# Check required extensions
echo ""
echo "🔧 PHP Extensions Check:"
required_extensions=("json" "mbstring" "curl" "openssl")
for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "  ✅ $ext"
    else
        echo "  ❌ $ext (missing)"
    fi
done

# Test all applications
echo ""
echo "🧪 Application Testing:"
applications=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
working=0

for i in "${!applications[@]}"; do
    app="${applications[$i]}"
    port=$((8080 + i))
    
    if [ -d "$app" ]; then
        cd "$app"
        timeout 5s php -S 127.0.0.1:$port -t public >/dev/null 2>&1 &
        sleep 2
        
        status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$port/ 2>/dev/null || echo "000")
        killall php 2>/dev/null || true
        
        if [ "$status" = "200" ]; then
            echo "  ✅ $app (HTTP $status)"
            ((working++))
        else
            echo "  ❌ $app (HTTP $status)"
        fi
        
        cd ..
    else
        echo "  ❌ $app (directory not found)"
    fi
done

echo ""
echo "🎯 Verification Results: $working/${#applications[@]} applications working"

if [ $working -eq ${#applications[@]} ]; then
    echo "✅ DEPLOYMENT SUCCESSFUL - All applications ready"
    exit 0
else
    echo "❌ DEPLOYMENT ISSUES - Some applications need attention"
    exit 1
fi
