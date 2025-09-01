#!/bin/bash

# Simple PHP Application - Hardened System Requirements Check
# Robust cross-platform validation with comprehensive error handling

set -e

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common-functions.sh"

# Configuration
RUNTIME_CONFIG="config/runtime.env"
REQUIRED_PHP_VERSIONS=("8.1" "8.2" "8.3" "8.4")
REQUIRED_EXTENSIONS=("mysql" "pgsql" "redis" "mbstring" "xml" "curl" "json")
DOCKER_HEALTH_TIMEOUT=${DOCKER_HEALTH_TIMEOUT:-120}

# Auto-fix script permissions (cross-platform)
fix_script_permissions() {
    log_info "Ensuring script permissions"
    
    local script_dir="$SCRIPT_DIR"
    local fixed_count=0
    
    # Find and fix script permissions
    while IFS= read -r -d '' script; do
        if [[ ! -x "$script" ]]; then
            if chmod +x "$script" 2>/dev/null; then
                log_verbose "Fixed permissions for $(basename "$script")"
                ((fixed_count++))
            else
                log_warning "Could not fix permissions for $script"
            fi
        fi
    done < <(find "$script_dir" -name "*.sh" -type f -print0 2>/dev/null || true)
    
    if [[ $fixed_count -gt 0 ]]; then
        log_success "Fixed permissions for $fixed_count script(s)"
    else
        log_verbose "All script permissions are correct"
    fi
}

# Enhanced PHP version detection with cross-platform support
check_php_versions() {
    log_info "Checking PHP versions"
    
    local found_versions=()
    local missing_versions=()
    local os=$(detect_os)
    local pm=$(get_package_manager)
    
    for version in "${REQUIRED_PHP_VERSIONS[@]}"; do
        local php_cmd="php$version"
        
        if command -v "$php_cmd" >/dev/null 2>&1; then
            local actual_version
            actual_version=$($php_cmd -r "echo PHP_VERSION;" 2>/dev/null || echo "unknown")
            found_versions+=("$version ($actual_version)")
            log_success "PHP $version: Available ($actual_version)"
        else
            missing_versions+=("$version")
            log_warning "PHP $version: Not found"
        fi
    done
    
    if [[ ${#found_versions[@]} -eq 0 ]]; then
        fail_with_error "check_php_versions" "No supported PHP versions found" $EXIT_GENERAL_ERROR \
            "Install PHP with:\n$(get_install_command "php php-cli php-fpm")"
    fi
    
    # Show installation commands for missing versions
    if [[ ${#missing_versions[@]} -gt 0 ]]; then
        log_info "Missing PHP versions: ${missing_versions[*]}"
        echo -e "${YELLOW}💡 Install missing versions:${NC}"
        
        case "$pm" in
            apt)
                echo "  sudo apt update"
                for version in "${missing_versions[@]}"; do
                    echo "  sudo apt install -y php$version php$version-cli php$version-fpm"
                done
                ;;
            yum)
                for version in "${missing_versions[@]}"; do
                    echo "  sudo yum install -y php$version php$version-cli php$version-fpm"
                done
                ;;
            pacman)
                echo "  sudo pacman -S php"
                ;;
            *)
                echo "  # Install PHP ${missing_versions[*]} using your system's package manager"
                ;;
        esac
    fi
    
    log_success "Found ${#found_versions[@]} PHP version(s): ${found_versions[*]}"
}

# Enhanced PHP extensions check with installation guidance
check_php_extensions() {
    log_info "Checking PHP extensions"
    
    local target_versions=()
    local missing_extensions=()
    
    # Determine which PHP versions to check
    if [[ -f "$RUNTIME_CONFIG" ]]; then
        source "$RUNTIME_CONFIG"
        if [[ -n "$PHP_VERSION" ]] && command -v "php$PHP_VERSION" >/dev/null 2>&1; then
            target_versions=("$PHP_VERSION")
        fi
    fi
    
    # Fallback to all available versions
    if [[ ${#target_versions[@]} -eq 0 ]]; then
        for version in "${REQUIRED_PHP_VERSIONS[@]}"; do
            if command -v "php$version" >/dev/null 2>&1; then
                target_versions+=("$version")
            fi
        done
    fi
    
    if [[ ${#target_versions[@]} -eq 0 ]]; then
        log_warning "No PHP versions available for extension checking"
        return 0
    fi
    
    # Check extensions for each PHP version
    for version in "${target_versions[@]}"; do
        log_verbose "Checking PHP $version extensions"
        
        for ext in "${REQUIRED_EXTENSIONS[@]}"; do
            if ! php$version -m 2>/dev/null | grep -q "^$ext$"; then
                local package_name="php$version-$ext"
                
                # Handle special extension names
                case "$ext" in
                    "mysql") package_name="php$version-mysql" ;;
                    "pgsql") package_name="php$version-pgsql" ;;
                    "redis") package_name="php$version-redis" ;;
                    "mbstring") package_name="php$version-mbstring" ;;
                    "xml") package_name="php$version-xml" ;;
                    "curl") package_name="php$version-curl" ;;
                    "json") package_name="php$version-json" ;;
                esac
                
                missing_extensions+=("$package_name")
                log_warning "Missing: $ext for PHP $version"
            else
                log_verbose "✓ $ext available for PHP $version"
            fi
        done
    done
    
    # Offer to install missing extensions
    if [[ ${#missing_extensions[@]} -gt 0 ]]; then
        echo -e "\n${YELLOW}Missing PHP extensions detected:${NC}"
        printf '%s\n' "${missing_extensions[@]}" | sort -u
        
        if [[ -t 0 ]] && [[ "${AUTO_INSTALL_EXTENSIONS:-0}" -ne 1 ]]; then
            echo -e "\n${YELLOW}Install missing extensions? (y/N):${NC}"
            read -r -t 30 response || response="n"
            
            if [[ "$response" =~ ^[yY] ]]; then
                install_php_extensions "${missing_extensions[@]}"
            fi
        elif [[ "${AUTO_INSTALL_EXTENSIONS:-0}" -eq 1 ]]; then
            log_info "Auto-installing missing extensions"
            install_php_extensions "${missing_extensions[@]}"
        else
            echo -e "${YELLOW}💡 Install manually with:${NC}"
            echo "  $(get_install_command "${missing_extensions[*]}")"
        fi
    else
        log_success "All required PHP extensions are available"
    fi
}

# Install PHP extensions with error handling
install_php_extensions() {
    local extensions=("$@")
    
    if [[ ${#extensions[@]} -eq 0 ]]; then
        return 0
    fi
    
    log_info "Installing PHP extensions: ${extensions[*]}"
    
    local pm=$(get_package_manager)
    case "$pm" in
        apt)
            safe_execute "Update package list" sudo apt update
            safe_execute "Install PHP extensions" sudo apt install -y "${extensions[@]}"
            ;;
        yum)
            safe_execute "Install PHP extensions" sudo yum install -y "${extensions[@]}"
            ;;
        pacman)
            safe_execute "Install PHP extensions" sudo pacman -S --noconfirm "${extensions[@]}"
            ;;
        *)
            log_error "Cannot auto-install extensions on this system"
            echo -e "${YELLOW}💡 Install manually: ${extensions[*]}${NC}"
            return 1
            ;;
    esac
    
    log_success "PHP extensions installed successfully"
}

# Enhanced web server detection
check_web_servers() {
    log_info "Checking web servers"
    
    local available_servers=()
    local server_details=()
    
    # Check PHP CLI
    if command -v php >/dev/null 2>&1; then
        local php_version=$(php -r "echo PHP_VERSION;" 2>/dev/null || echo "unknown")
        available_servers+=("php-cli")
        server_details+=("PHP-CLI: $php_version")
        log_success "PHP-CLI: Available ($php_version)"
    else
        log_warning "PHP-CLI: Not available"
    fi
    
    # Check Apache
    local apache_cmd=""
    if command -v apache2 >/dev/null 2>&1; then
        apache_cmd="apache2"
    elif command -v httpd >/dev/null 2>&1; then
        apache_cmd="httpd"
    fi
    
    if [[ -n "$apache_cmd" ]]; then
        local apache_version=$($apache_cmd -v 2>/dev/null | head -1 | cut -d' ' -f3 || echo "unknown")
        available_servers+=("apache")
        server_details+=("Apache: $apache_version")
        log_success "Apache: Available ($apache_version)"
        
        # Check mod_php and PHP-FPM
        check_apache_modules
    else
        log_warning "Apache: Not installed"
        echo -e "${YELLOW}💡 Install Apache: $(get_install_command "apache2")${NC}"
    fi
    
    # Check Nginx
    if command -v nginx >/dev/null 2>&1; then
        local nginx_version=$(nginx -v 2>&1 | cut -d' ' -f3 | cut -d'/' -f2 || echo "unknown")
        available_servers+=("nginx")
        server_details+=("Nginx: $nginx_version")
        log_success "Nginx: Available ($nginx_version)"
    else
        log_warning "Nginx: Not installed"
        echo -e "${YELLOW}💡 Install Nginx: $(get_install_command "nginx")${NC}"
    fi
    
    # Check PHP-FPM
    check_php_fpm
    
    if [[ ${#available_servers[@]} -eq 0 ]]; then
        fail_with_error "check_web_servers" "No web servers available" $EXIT_GENERAL_ERROR \
            "Install a web server:\n$(get_install_command "apache2 nginx")"
    fi
    
    log_success "Available web servers: ${available_servers[*]}"
    log_verbose "Server details: ${server_details[*]}"
}

# Check Apache modules
check_apache_modules() {
    local apache_cmd="apache2"
    command -v httpd >/dev/null 2>&1 && apache_cmd="httpd"
    
    # Check for mod_php
    if $apache_cmd -M 2>/dev/null | grep -q "php"; then
        log_success "Apache mod_php: Available"
    else
        log_warning "Apache mod_php: Not enabled"
    fi
}

# Check PHP-FPM with socket detection
check_php_fpm() {
    local fpm_available=false
    local fpm_sockets=()
    
    for version in "${REQUIRED_PHP_VERSIONS[@]}"; do
        if command -v "php-fpm$version" >/dev/null 2>&1; then
            fpm_available=true
            log_success "PHP-FPM $version: Available"
            
            # Detect socket paths
            local socket_paths=(
                "/run/php/php$version-fpm.sock"
                "/var/run/php-fpm/php$version-fpm.sock"
                "/var/run/php/php$version-fpm.sock"
            )
            
            for socket in "${socket_paths[@]}"; do
                if [[ -S "$socket" ]]; then
                    fmp_sockets+=("$socket")
                    log_verbose "Found FPM socket: $socket"
                fi
            done
        fi
    done
    
    if [[ "$fmp_available" == "false" ]]; then
        log_warning "PHP-FPM: Not available"
        echo -e "${YELLOW}💡 Install PHP-FPM: $(get_install_command "php-fpm")${NC}"
    fi
}

# Enhanced Docker checks with health monitoring
check_docker() {
    log_info "Checking Docker environment"
    
    # Check Docker installation
    if ! check_command "docker" "$(get_install_command "docker.io")"; then
        return $EXIT_COMMAND_NOT_FOUND
    fi
    
    # Check Docker daemon
    if ! check_docker_daemon; then
        return $EXIT_GENERAL_ERROR
    fi
    
    # Detect Docker Compose
    local compose_cmd
    if ! compose_cmd=$(detect_docker_compose); then
        log_error "Docker Compose not available"
        echo -e "${YELLOW}💡 Install Docker Compose:${NC}"
        echo "  # For Docker Compose v2:"
        echo "  sudo apt install docker-compose-plugin"
        echo "  # For Docker Compose v1:"
        echo "  sudo apt install docker-compose"
        return $EXIT_COMMAND_NOT_FOUND
    fi
    
    log_success "Docker Compose: $compose_cmd"
    
    # Check architecture compatibility
    local arch=$(detect_architecture)
    log_verbose "System architecture: $arch"
    
    # Verify Docker can run containers
    if ! safe_execute "Test Docker functionality" docker run --rm hello-world; then
        log_error "Docker cannot run containers"
        return $EXIT_GENERAL_ERROR
    fi
    
    log_success "Docker environment is ready"
    return $EXIT_SUCCESS
}

# Enhanced Docker service startup with health monitoring
start_docker_services() {
    log_info "Starting Docker services"
    
    if [[ ! -f "docker-compose.yml" ]]; then
        log_warning "No docker-compose.yml found - skipping Docker services"
        return 0
    fi
    
    # Load configuration for port checking
    local mysql_port=3309
    local postgres_port=5435
    local redis_port=6382
    
    if [[ -f "$RUNTIME_CONFIG" ]]; then
        source "$RUNTIME_CONFIG"
        mysql_port=${MYSQL_PORT:-3309}
        postgres_port=${POSTGRES_PORT:-5435}
        redis_port=${REDIS_PORT:-6382}
    fi
    
    # Check port availability
    check_service_ports "$mysql_port" "$postgres_port" "$redis_port"
    
    # Start services
    local compose_cmd=$(detect_docker_compose)
    log_info "Starting containers with $compose_cmd"
    
    if ! safe_execute "Start Docker containers" $compose_cmd up -d; then
        fail_with_error "start_docker_services" "Failed to start containers" $EXIT_GENERAL_ERROR \
            "Check Docker logs:\n  $compose_cmd logs"
    fi
    
    # Wait for health checks
    wait_for_container_health
    
    log_success "Docker services started successfully"
}

# Check if service ports are available
check_service_ports() {
    local ports=("$@")
    local conflicts=()
    
    for port in "${ports[@]}"; do
        if ! check_port_available "$port"; then
            local owner=$(get_port_owner "$port")
            conflicts+=("Port $port in use by: $owner")
            log_error "Port $port is already in use by: $owner"
        else
            log_verbose "Port $port is available"
        fi
    done
    
    if [[ ${#conflicts[@]} -gt 0 ]]; then
        echo -e "\n${RED}Port conflicts detected:${NC}"
        printf '%s\n' "${conflicts[@]}"
        echo -e "\n${YELLOW}💡 Resolve conflicts:${NC}"
        echo "  1. Stop conflicting services"
        echo "  2. Change ports in $RUNTIME_CONFIG"
        echo "  3. Use 'make compile' to reconfigure"
        
        fail_with_error "check_service_ports" "Port conflicts prevent startup" $EXIT_GENERAL_ERROR
    fi
}

# Wait for container health with timeout
wait_for_container_health() {
    log_info "Waiting for container health checks"
    
    local compose_cmd=$(detect_docker_compose)
    local timeout=$DOCKER_HEALTH_TIMEOUT
    local interval=5
    local elapsed=0
    
    while [[ $elapsed -lt $timeout ]]; do
        local unhealthy_containers=()
        
        # Get container status
        while IFS= read -r line; do
            if [[ "$line" =~ ^[[:space:]]*([^[:space:]]+)[[:space:]]+.*[[:space:]]+(starting|unhealthy|Created)[[:space:]]* ]]; then
                unhealthy_containers+=("${BASH_REMATCH[1]}")
            fi
        done < <($compose_cmd ps 2>/dev/null || true)
        
        if [[ ${#unhealthy_containers[@]} -eq 0 ]]; then
            log_success "All containers are healthy"
            return 0
        fi
        
        log_verbose "Waiting for containers: ${unhealthy_containers[*]} (${elapsed}s/${timeout}s)"
        sleep $interval
        ((elapsed += interval))
    done
    
    # Timeout reached - show diagnostics
    log_error "Container health check timeout after ${timeout}s"
    echo -e "\n${YELLOW}Container diagnostics:${NC}"
    $compose_cmd ps || true
    
    echo -e "\n${YELLOW}💡 Check container logs:${NC}"
    for container in "${unhealthy_containers[@]}"; do
        echo "  $compose_cmd logs $container"
    done
    
    return $EXIT_GENERAL_ERROR
}

# Show comprehensive summary
show_summary() {
    echo -e "\n${BLUE}📊 System Setup Summary${NC}"
    echo "========================"
    
    # System information
    echo -e "${PURPLE}System Information:${NC}"
    echo "  OS: $(detect_os)"
    echo "  Architecture: $(detect_architecture)"
    echo "  Package Manager: $(get_package_manager)"
    
    # PHP status
    echo -e "\n${PURPLE}PHP Status:${NC}"
    for version in "${REQUIRED_PHP_VERSIONS[@]}"; do
        if command -v "php$version" >/dev/null 2>&1; then
            echo "  ✅ PHP $version: Available"
        else
            echo "  ❌ PHP $version: Not found"
        fi
    done
    
    # Docker status
    echo -e "\n${PURPLE}Docker Status:${NC}"
    if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
        echo "  ✅ Docker: Running"
        local compose_cmd=$(detect_docker_compose 2>/dev/null || echo "not available")
        echo "  ✅ Compose: $compose_cmd"
    else
        echo "  ❌ Docker: Not available"
    fi
    
    # Configuration status
    echo -e "\n${PURPLE}Configuration:${NC}"
    if [[ -f "$RUNTIME_CONFIG" ]]; then
        echo "  ✅ Runtime config: $RUNTIME_CONFIG"
    else
        echo "  ⚠️  Runtime config: Not found (run 'make compile')"
    fi
    
    echo -e "\n${GREEN}✅ System setup completed successfully${NC}"
    echo -e "${YELLOW}💡 Next steps:${NC}"
    echo "  1. Run 'make compile' to configure the application"
    echo "  2. Run 'make start' to deploy and start the application"
    echo "  3. Run 'make status' to check application health"
}

# Main execution with comprehensive error handling
main() {
    log_info "Simple PHP - System Requirements Check (Hardened)"
    
    # Migrate configuration if needed
    if [[ ! -f "$RUNTIME_CONFIG" ]] && [[ -f "config/app.env" || -f ".env" ]]; then
        log_info "Migrating legacy configuration"
        if ! "$SCRIPT_DIR/migrate-config.sh"; then
            log_warning "Configuration migration failed, continuing with defaults"
        fi
    fi
    
    # System checks
    fix_script_permissions
    check_php_versions
    check_php_extensions
    check_web_servers
    
    # Docker setup
    if check_docker; then
        start_docker_services
    else
        log_warning "Docker not available - some features will be limited"
    fi
    
    show_summary
    
    success_with_message "setup" "System requirements validated successfully" \
        "• Run 'VERBOSE=1 make setup' for detailed logs\n• Check logs at: $LOG_FILE"
}

# Execute main function
main "$@"
