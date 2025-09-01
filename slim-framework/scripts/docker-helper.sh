#!/bin/bash

# Simple PHP Application - Docker Helper Script
# APM PHP Examples - Independent Application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to detect Docker Compose command
detect_docker_compose() {
    if command -v "docker" >/dev/null 2>&1; then
        # Check if Docker Compose V2 is available
        if docker compose version >/dev/null 2>&1; then
            echo "docker compose"
            return 0
        fi
    fi
    
    # Check if Docker Compose V1 is available
    if command -v "docker-compose" >/dev/null 2>&1; then
        echo "docker-compose"
        return 0
    fi
    
    echo "none"
    return 1
}

# Function to get Docker version info
get_docker_info() {
    local docker_cmd=$(detect_docker_compose)
    
    if [[ "$docker_cmd" == "none" ]]; then
        echo -e "${RED}❌ Docker Compose not found${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Docker Compose detected: $docker_cmd${NC}"
    
    # Get Docker version
    if command -v docker >/dev/null 2>&1; then
        local docker_version=$(docker --version)
        echo -e "${BLUE}Docker: $docker_version${NC}"
    fi
    
    # Get Docker Compose version
    if [[ "$docker_cmd" == "docker compose" ]]; then
        local compose_version=$(docker compose version)
        echo -e "${BLUE}Compose: $compose_version${NC}"
    else
        local compose_version=$(docker-compose --version)
        echo -e "${BLUE}Compose: $compose_version${NC}"
    fi
    
    return 0
}

# Function to execute Docker Compose command
execute_docker_compose() {
    local action=$1
    local file=${2:-"docker-compose.yml"}
    local service=${3:-""}
    
    local docker_cmd=$(detect_docker_compose)
    
    if [[ "$docker_cmd" == "none" ]]; then
        echo -e "${RED}❌ Docker Compose not available${NC}"
        return 1
    fi
    
    # Build the command
    local cmd="$docker_cmd"
    
    if [[ "$file" != "docker-compose.yml" ]]; then
        cmd="$cmd -f $file"
    fi
    
    cmd="$cmd $action"
    
    if [[ -n "$service" ]]; then
        cmd="$cmd $service"
    fi
    
    echo -e "${BLUE}Executing: $cmd${NC}"
    eval $cmd
}

# Function to check if services are running
check_services_status() {
    local file=${1:-"docker-compose.yml"}
    
    local docker_cmd=$(detect_docker_compose)
    
    if [[ "$docker_cmd" == "none" ]]; then
        echo -e "${RED}❌ Docker Compose not available${NC}"
        return 1
    fi
    
    # Build the command
    local cmd="$docker_cmd"
    
    if [[ "$file" != "docker-compose.yml" ]]; then
        cmd="$cmd -f $file"
    fi
    
    cmd="$cmd ps"
    
    echo -e "${BLUE}Checking services status...${NC}"
    eval $cmd
}

# Function to start Simple PHP services
start_app_services() {
    echo -e "${BLUE}🚀 Starting Simple PHP services...${NC}"
    
    # Check if docker-compose.yml exists
    if [ ! -f "docker-compose.yml" ]; then
        echo -e "${YELLOW}⚠️  docker-compose.yml not found - no services to start${NC}"
        return 0
    fi
    
    execute_docker_compose "up -d" "docker-compose.yml"
    
    echo -e "${GREEN}✅ Simple PHP services started${NC}"
}

# Function to stop Simple PHP services
stop_app_services() {
    echo -e "${BLUE}🛑 Stopping Simple PHP services...${NC}"
    
    # Check if docker-compose.yml exists
    if [ ! -f "docker-compose.yml" ]; then
        echo -e "${YELLOW}⚠️  docker-compose.yml not found - no services to stop${NC}"
        return 0
    fi
    
    execute_docker_compose "down" "docker-compose.yml"
    
    echo -e "${GREEN}✅ Simple PHP services stopped${NC}"
}

# Function to check Simple PHP services status
check_app_services() {
    echo -e "${BLUE}📊 Checking Simple PHP services status...${NC}"
    
    # Check if docker-compose.yml exists
    if [ ! -f "docker-compose.yml" ]; then
        echo -e "${YELLOW}⚠️  docker-compose.yml not found - no services configured${NC}"
        return 0
    fi
    
    check_services_status "docker-compose.yml"
}

# Main execution
main() {
    local action=${1:-"info"}
    local file=${2:-"docker-compose.yml"}
    local service=${3:-""}
    
    case $action in
        "info")
            get_docker_info
            ;;
        "up"|"down"|"build"|"logs"|"ps")
            execute_docker_compose "$action" "$file" "$service"
            ;;
        "app-up")
            start_app_services
            ;;
        "app-down")
            stop_app_services
            ;;
        "app-status")
            check_app_services
            ;;
        *)
            echo -e "${YELLOW}Usage: $0 {info|up|down|build|logs|ps|app-up|app-down|app-status} [file] [service]${NC}"
            echo -e ""
            echo -e "Commands:"
            echo -e "  info              - Show Docker version information"
            echo -e "  up                - Start services"
            echo -e "  down              - Stop services"
            echo -e "  build             - Build services"
            echo -e "  logs              - Show logs"
            echo -e "  ps                - Show status"
            echo -e "  app-up            - Start Simple PHP services"
            echo -e "  app-down          - Stop Simple PHP services"
            echo -e "  app-status        - Check Simple PHP services status"
            ;;
    esac
}

# Execute main function
main "$@"
