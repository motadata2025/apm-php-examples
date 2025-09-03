#!/bin/bash
# run_all_services.sh - Start all APM services and wait for health
# Brings up centralized Docker services for APM PHP Examples validation

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$REPO_ROOT/docker/docker-compose.services.yml"
WAIT_SCRIPT="$SCRIPT_DIR/wait_for_health.sh"
TIMEOUT=180

# Container names to wait for
CONTAINERS=(
    "apm_mysql_simple"
    "apm_mysql_symfony"
    "apm_mysql_slim"
    "apm_mysql_codeigniter"
    "apm_mysql_laravel"
    "apm_postgres_simple"
    "apm_postgres_symfony"
    "apm_postgres_slim"
    "apm_postgres_codeigniter"
    "apm_postgres_laravel"
    "apm_redis_simple"
    "apm_redis_symfony"
    "apm_redis_slim"
    "apm_redis_codeigniter"
    "apm_redis_laravel"
)

echo "🚀 Starting APM PHP Examples services..."
echo "Compose file: $COMPOSE_FILE"
echo "Timeout: ${TIMEOUT}s per container"

# Check if compose file exists
if [[ ! -f "$COMPOSE_FILE" ]]; then
    echo "❌ ERROR: Compose file not found: $COMPOSE_FILE"
    exit 1
fi

# Check if wait script exists
if [[ ! -x "$WAIT_SCRIPT" ]]; then
    echo "❌ ERROR: Wait script not found or not executable: $WAIT_SCRIPT"
    exit 1
fi

# Function to cleanup on failure
cleanup_on_failure() {
    echo "🧹 Cleaning up failed containers..."
    docker compose -f "$COMPOSE_FILE" down --remove-orphans 2>/dev/null || true
}

# Trap to cleanup on script failure
trap cleanup_on_failure ERR

# Start services
echo "📦 Starting Docker Compose services..."
if ! docker compose -f "$COMPOSE_FILE" up -d; then
    echo "❌ ERROR: Failed to start Docker Compose services"
    echo "Checking for port conflicts..."
    netstat -tuln | grep -E "(3307|3308|3309|3310|3311|5433|5434|5435|5436|5437|6380|6381|6382|6383|6384)" || true
    exit 1
fi

echo "⏳ Waiting for services to become healthy..."

# Wait for each container to become healthy
failed_containers=()
for container in "${CONTAINERS[@]}"; do
    echo "Checking $container..."
    if ! "$WAIT_SCRIPT" "$container" "$TIMEOUT"; then
        echo "❌ Container $container failed to become healthy"
        failed_containers+=("$container")
    fi
done

# Check if any containers failed
if [[ ${#failed_containers[@]} -gt 0 ]]; then
    echo "❌ The following containers failed to become healthy:"
    printf '  - %s\n' "${failed_containers[@]}"
    
    # Log container status for debugging
    echo "📊 Container status:"
    docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(apm_|NAMES)" || true
    
    # Save failure log
    mkdir -p "$REPO_ROOT/augment/logs"
    {
        echo "Service startup failure - $(date)"
        echo "Failed containers: ${failed_containers[*]}"
        echo ""
        echo "Container status:"
        docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(apm_|NAMES)" || true
        echo ""
        echo "Docker logs for failed containers:"
        for container in "${failed_containers[@]}"; do
            echo "=== Logs for $container ==="
            docker logs --tail 50 "$container" 2>&1 || echo "Failed to get logs for $container"
            echo ""
        done
    } > "$REPO_ROOT/augment/logs/service-start-fail.log"
    
    echo "📝 Failure details saved to: augment/logs/service-start-fail.log"
    exit 1
fi

echo "✅ All services are healthy and ready!"
echo "📊 Service status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(apm_|NAMES)" || true

echo ""
echo "🎯 Services available at:"
echo "  MySQL:      localhost:3307-3311"
echo "  PostgreSQL: localhost:5433-5437"
echo "  Redis:      localhost:6380-6384"
echo ""
echo "Use 'docker compose -f $COMPOSE_FILE down' to stop all services"
