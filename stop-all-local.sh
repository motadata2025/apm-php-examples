#!/bin/bash

# Stop all APM PHP applications
echo "Stopping all APM PHP applications..."

# Array of applications
apps=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

for app in "${apps[@]}"; do
    if [ -f ".$app.pid" ]; then
        pid=$(cat ".$app.pid")
        if kill -0 "$pid" 2>/dev/null; then
            echo "Stopping $app (PID: $pid)..."
            kill "$pid"
            rm ".$app.pid"
            echo "✅ $app stopped"
        else
            echo "⚠️  $app was not running"
            rm ".$app.pid"
        fi
    else
        echo "⚠️  No PID file found for $app"
    fi
done

echo ""
echo "🛑 All applications stopped!"
