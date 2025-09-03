#!/bin/bash
# wait_for_health.sh - Wait for Docker container health checks
# Usage: wait_for_health.sh <container_name> <timeout_seconds>

set -euo pipefail

CONTAINER_NAME="${1:-}"
TIMEOUT="${2:-180}"
INTERVAL=5

if [[ -z "$CONTAINER_NAME" ]]; then
    echo "ERROR: Container name required"
    echo "Usage: $0 <container_name> <timeout_seconds>"
    exit 1
fi

echo "Waiting for container '$CONTAINER_NAME' to become healthy (timeout: ${TIMEOUT}s)..."

start_time=$(date +%s)
end_time=$((start_time + TIMEOUT))

while [[ $(date +%s) -lt $end_time ]]; do
    # Check if container exists
    if ! docker inspect "$CONTAINER_NAME" >/dev/null 2>&1; then
        echo "ERROR: Container '$CONTAINER_NAME' does not exist"
        exit 1
    fi
    
    # Get container health status
    health_status=$(docker inspect --format='{{.State.Health.Status}}' "$CONTAINER_NAME" 2>/dev/null || echo "none")
    
    case "$health_status" in
        "healthy")
            echo "✅ Container '$CONTAINER_NAME' is healthy"
            exit 0
            ;;
        "unhealthy")
            echo "❌ Container '$CONTAINER_NAME' is unhealthy"
            echo "Container logs:"
            docker logs --tail 20 "$CONTAINER_NAME"
            exit 1
            ;;
        "starting")
            echo "⏳ Container '$CONTAINER_NAME' is starting... ($(date +%H:%M:%S))"
            ;;
        "none")
            echo "⚠️  Container '$CONTAINER_NAME' has no health check defined"
            # For containers without health checks, check if they're running
            if docker inspect --format='{{.State.Running}}' "$CONTAINER_NAME" | grep -q "true"; then
                echo "✅ Container '$CONTAINER_NAME' is running (no health check)"
                exit 0
            else
                echo "❌ Container '$CONTAINER_NAME' is not running"
                exit 1
            fi
            ;;
        *)
            echo "⚠️  Container '$CONTAINER_NAME' health status: $health_status"
            ;;
    esac
    
    sleep $INTERVAL
done

echo "❌ Timeout waiting for container '$CONTAINER_NAME' to become healthy"
echo "Final status: $(docker inspect --format='{{.State.Health.Status}}' "$CONTAINER_NAME" 2>/dev/null || echo "unknown")"
echo "Container logs:"
docker logs --tail 20 "$CONTAINER_NAME"
exit 1
