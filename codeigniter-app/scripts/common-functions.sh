#!/bin/bash

# APM PHP Examples - Common Functions Library
# Provides robust error handling, logging, and utility functions

# Exit codes
readonly EXIT_SUCCESS=0
readonly EXIT_GENERAL_ERROR=1
readonly EXIT_MISUSE=2
readonly EXIT_CANNOT_EXECUTE=126
readonly EXIT_COMMAND_NOT_FOUND=127
readonly EXIT_INVALID_ARGUMENT=128

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly NC='\033[0m' # No Color

# Global variables
VERBOSE=${VERBOSE:-0}
SCRIPT_NAME=$(basename "$0")
LOG_FILE="logs/$(basename "$0" .sh).log"

# Ensure logs directory exists
mkdir -p logs

# Logging functions
log_info() {
    local message="$1"
    echo -e "${BLUE}ℹ️  $message${NC}"
    [[ $VERBOSE -eq 1 ]] && echo "[$(date '+%Y-%m-%d %H:%M:%S')] INFO: $message" >> "$LOG_FILE"
}

log_success() {
    local message="$1"
    echo -e "${GREEN}✅ $message${NC}"
    [[ $VERBOSE -eq 1 ]] && echo "[$(date '+%Y-%m-%d %H:%M:%S')] SUCCESS: $message" >> "$LOG_FILE"
}

log_warning() {
    local message="$1"
    echo -e "${YELLOW}⚠️  $message${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] WARNING: $message" >> "$LOG_FILE"
}

log_error() {
    local message="$1"
    echo -e "${RED}❌ $message${NC}" >&2
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $message" >> "$LOG_FILE"
}

log_verbose() {
    local message="$1"
    [[ $VERBOSE -eq 1 ]] && echo -e "${CYAN}🔍 $message${NC}"
    [[ $VERBOSE -eq 1 ]] && echo "[$(date '+%Y-%m-%d %H:%M:%S')] VERBOSE: $message" >> "$LOG_FILE"
}

# Error handling functions
fail_with_error() {
    local target="$1"
    local reason="$2"
    local exit_code="${3:-$EXIT_GENERAL_ERROR}"
    local suggestions="$4"
    
    log_error "[$target] failed: $reason"
    
    if [[ -n "$suggestions" ]]; then
        echo -e "${YELLOW}💡 Suggested next steps:${NC}"
        echo -e "$suggestions"
    fi
    
    echo -e "${YELLOW}📋 For more details, check: $LOG_FILE${NC}"
    exit "$exit_code"
}

success_with_message() {
    local target="$1"
    local message="$2"
    local verification_tips="$3"
    
    log_success "[$target] completed successfully: $message"
    
    if [[ -n "$verification_tips" ]]; then
        echo -e "${BLUE}🔍 Verification tips:${NC}"
        echo -e "$verification_tips"
    fi
}

# Command existence and execution wrappers
check_command() {
    local cmd="$1"
    local package_hint="$2"
    
    if ! command -v "$cmd" >/dev/null 2>&1; then
        log_error "Required command '$cmd' not found"
        if [[ -n "$package_hint" ]]; then
            echo -e "${YELLOW}💡 Install with: $package_hint${NC}"
        fi
        return $EXIT_COMMAND_NOT_FOUND
    fi
    
    log_verbose "Command '$cmd' found at $(command -v "$cmd")"
    return $EXIT_SUCCESS
}

safe_execute() {
    local description="$1"
    shift
    local cmd=("$@")
    
    log_verbose "Executing: ${cmd[*]}"
    
    if [[ $VERBOSE -eq 1 ]]; then
        "${cmd[@]}" 2>&1 | tee -a "$LOG_FILE"
        local exit_code=${PIPESTATUS[0]}
    else
        "${cmd[@]}" >> "$LOG_FILE" 2>&1
        local exit_code=$?
    fi
    
    if [[ $exit_code -eq 0 ]]; then
        log_verbose "$description - completed successfully"
        return $EXIT_SUCCESS
    else
        log_error "$description - failed with exit code $exit_code"
        return $exit_code
    fi
}

# System detection functions
detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        echo "$ID"
    elif [[ -f /etc/redhat-release ]]; then
        echo "rhel"
    elif [[ -f /etc/debian_version ]]; then
        echo "debian"
    else
        echo "unknown"
    fi
}

detect_architecture() {
    uname -m
}

get_package_manager() {
    local os=$(detect_os)
    case "$os" in
        ubuntu|debian) echo "apt" ;;
        rhel|centos|fedora|almalinux) echo "yum" ;;
        arch|manjaro) echo "pacman" ;;
        *) echo "unknown" ;;
    esac
}

get_install_command() {
    local package="$1"
    local pm=$(get_package_manager)
    local os=$(detect_os)
    
    case "$pm" in
        apt) echo "sudo apt update && sudo apt install -y $package" ;;
        yum) echo "sudo yum install -y $package" ;;
        pacman) echo "sudo pacman -S --noconfirm $package" ;;
        *) echo "# Please install $package using your system's package manager" ;;
    esac
}

# Docker detection and management
detect_docker_compose() {
    if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
        echo "docker compose"
    elif command -v docker-compose >/dev/null 2>&1; then
        echo "docker-compose"
    else
        return $EXIT_COMMAND_NOT_FOUND
    fi
}

check_docker_daemon() {
    if ! command -v docker >/dev/null 2>&1; then
        log_error "Docker not installed"
        echo -e "${YELLOW}💡 Install Docker:${NC}"
        echo "  $(get_install_command "docker.io")"
        echo "  # Or visit: https://docs.docker.com/engine/install/"
        return $EXIT_COMMAND_NOT_FOUND
    fi
    
    if ! docker info >/dev/null 2>&1; then
        log_error "Docker daemon not running"
        echo -e "${YELLOW}💡 Start Docker:${NC}"
        echo "  sudo systemctl start docker"
        echo "  # Or start Docker Desktop"
        return $EXIT_GENERAL_ERROR
    fi
    
    log_verbose "Docker daemon is running"
    return $EXIT_SUCCESS
}

# Port management
check_port_available() {
    local port="$1"
    local protocol="${2:-tcp}"
    
    if command -v ss >/dev/null 2>&1; then
        if ss -ln | grep -q ":$port "; then
            return $EXIT_GENERAL_ERROR
        fi
    elif command -v netstat >/dev/null 2>&1; then
        if netstat -ln | grep -q ":$port "; then
            return $EXIT_GENERAL_ERROR
        fi
    else
        log_warning "Cannot check port availability - ss/netstat not found"
        return $EXIT_SUCCESS
    fi
    
    return $EXIT_SUCCESS
}

get_port_owner() {
    local port="$1"
    
    if command -v ss >/dev/null 2>&1; then
        ss -tlnp | grep ":$port " | awk '{print $6}' | cut -d',' -f2 | cut -d'=' -f2
    elif command -v netstat >/dev/null 2>&1; then
        netstat -tlnp 2>/dev/null | grep ":$port " | awk '{print $7}' | cut -d'/' -f2
    else
        echo "unknown"
    fi
}

# Configuration management
load_config() {
    local config_file="$1"
    
    if [[ ! -f "$config_file" ]]; then
        log_error "Configuration file not found: $config_file"
        echo -e "${YELLOW}💡 Run 'make compile' to generate configuration${NC}"
        return $EXIT_GENERAL_ERROR
    fi
    
    # Source the configuration file safely
    set -a  # Export all variables
    source "$config_file"
    set +a  # Stop exporting
    
    log_verbose "Configuration loaded from $config_file"
    return $EXIT_SUCCESS
}

# Timeout wrapper
with_timeout() {
    local timeout="$1"
    local description="$2"
    shift 2
    local cmd=("$@")
    
    log_verbose "Running with ${timeout}s timeout: $description"
    
    if command -v timeout >/dev/null 2>&1; then
        timeout "$timeout" "${cmd[@]}"
        local exit_code=$?
        
        if [[ $exit_code -eq 124 ]]; then
            log_error "$description timed out after ${timeout}s"
            return $EXIT_GENERAL_ERROR
        fi
        
        return $exit_code
    else
        # Fallback without timeout
        log_warning "timeout command not available, running without timeout"
        "${cmd[@]}"
    fi
}

# Progress indicator
show_progress() {
    local message="$1"
    local duration="${2:-3}"
    
    echo -n -e "${BLUE}$message${NC}"
    for ((i=0; i<duration; i++)); do
        echo -n "."
        sleep 1
    done
    echo ""
}

# Cleanup trap
cleanup_on_exit() {
    local exit_code=$?
    if [[ $exit_code -ne 0 ]]; then
        log_error "Script exited with code $exit_code"
        echo -e "${YELLOW}📋 Check logs for details: $LOG_FILE${NC}"
    fi
}

# Set up exit trap
trap cleanup_on_exit EXIT

# Export functions for use in other scripts
export -f log_info log_success log_warning log_error log_verbose
export -f fail_with_error success_with_message
export -f check_command safe_execute
export -f detect_os detect_architecture get_package_manager get_install_command
export -f detect_docker_compose check_docker_daemon
export -f check_port_available get_port_owner
export -f load_config with_timeout show_progress
