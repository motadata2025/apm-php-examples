#!/bin/bash

# Start all APM PHP applications locally
echo "Starting all APM PHP applications..."

# Array of applications and their ports
declare -A apps=(
    ["simple-php"]=8080
    ["laravel-app"]=8081
    ["symfony-app"]=8082
    ["slim-framework"]=8083
    ["codeigniter-app"]=8084
)

# Function to start application in background
start_app() {
    local app="$1"
    local port="$2"
    
    if [ -d "$app" ] && [ -f "$app/start-local.sh" ]; then
        echo "Starting $app on port $port..."
        cd "$app"
        ./start-local.sh &
        local pid=$!
        echo "$pid" > "../.$app.pid"
        cd ..
        echo "✅ $app started (PID: $pid)"
    else
        echo "⚠️  Skipping $app (not found or no startup script)"
    fi
}

# Start all applications
for app in "${!apps[@]}"; do
    port="${apps[$app]}"
    start_app "$app" "$port"
    sleep 2
done

echo ""
echo "🎉 All applications started!"
echo ""
echo "Access your applications at:"
for app in "${!apps[@]}"; do
    port="${apps[$app]}"
    echo "  $app: http://10.20.41.77:$port"
done

echo ""
echo "To stop all applications, run: ./stop-all-local.sh"
