#!/bin/bash

# APM PHP Examples - Linux Distribution Compatibility Testing
# Tests applications across Ubuntu LTS, CentOS, RHEL, and generic Linux distributions

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🐧 APM PHP Examples - Linux Distribution Compatibility Testing${NC}"
echo "=============================================================="
echo ""

# Results storage
RESULTS_DIR="linux_compatibility_results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_FILE="${RESULTS_DIR}/linux_compatibility_${TIMESTAMP}.json"

# Create results directory
mkdir -p "$RESULTS_DIR"

# Function to detect Linux distribution
detect_linux_distribution() {
    echo -e "${CYAN}🔍 Detecting Linux Distribution${NC}"
    echo "--------------------------------"
    
    local distro="Unknown"
    local version="Unknown"
    local codename="Unknown"
    
    # Check /etc/os-release (most modern distributions)
    if [ -f /etc/os-release ]; then
        source /etc/os-release
        distro="$NAME"
        version="$VERSION_ID"
        codename="$VERSION_CODENAME"
    # Check /etc/lsb-release (Ubuntu/Debian)
    elif [ -f /etc/lsb-release ]; then
        source /etc/lsb-release
        distro="$DISTRIB_ID"
        version="$DISTRIB_RELEASE"
        codename="$DISTRIB_CODENAME"
    # Check /etc/redhat-release (RHEL/CentOS)
    elif [ -f /etc/redhat-release ]; then
        distro=$(cat /etc/redhat-release | cut -d' ' -f1)
        version=$(cat /etc/redhat-release | grep -oE '[0-9]+\.[0-9]+' | head -1)
    # Check /etc/debian_version (Debian)
    elif [ -f /etc/debian_version ]; then
        distro="Debian"
        version=$(cat /etc/debian_version)
    fi
    
    echo "Distribution: $distro"
    echo "Version: $version"
    echo "Codename: $codename"
    echo "Kernel: $(uname -r)"
    echo "Architecture: $(uname -m)"
    echo ""
    
    # Store results
    echo "{
        \"distribution\": \"$distro\",
        \"version\": \"$version\",
        \"codename\": \"$codename\",
        \"kernel\": \"$(uname -r)\",
        \"architecture\": \"$(uname -m)\"
    }" > "$RESULTS_FILE"
}

# Function to check package manager
detect_package_manager() {
    echo -e "${CYAN}📦 Detecting Package Manager${NC}"
    echo "-----------------------------"
    
    local pkg_manager="Unknown"
    
    if command -v apt-get > /dev/null; then
        pkg_manager="apt"
        echo "Package Manager: APT (Debian/Ubuntu)"
        echo "APT Version: $(apt-get --version | head -1)"
    elif command -v yum > /dev/null; then
        pkg_manager="yum"
        echo "Package Manager: YUM (RHEL/CentOS)"
        echo "YUM Version: $(yum --version | head -1)"
    elif command -v dnf > /dev/null; then
        pkg_manager="dnf"
        echo "Package Manager: DNF (Fedora/RHEL 8+)"
        echo "DNF Version: $(dnf --version | head -1)"
    elif command -v zypper > /dev/null; then
        pkg_manager="zypper"
        echo "Package Manager: Zypper (openSUSE)"
        echo "Zypper Version: $(zypper --version)"
    elif command -v pacman > /dev/null; then
        pkg_manager="pacman"
        echo "Package Manager: Pacman (Arch Linux)"
        echo "Pacman Version: $(pacman --version | head -1)"
    fi
    
    echo ""
    return 0
}

# Function to test PHP installation methods
test_php_installation() {
    echo -e "${CYAN}🐘 Testing PHP Installation Methods${NC}"
    echo "-----------------------------------"
    
    # Test current PHP installation
    if command -v php > /dev/null; then
        echo "✅ PHP is installed"
        echo "Version: $(php -v | head -1)"
        echo "Path: $(which php)"
        echo "Configuration: $(php --ini | grep 'Loaded Configuration File' | cut -d: -f2 | xargs)"
    else
        echo "❌ PHP is not installed"
    fi
    
    # Test package availability
    echo ""
    echo "Testing PHP package availability:"
    
    if command -v apt-cache > /dev/null; then
        echo "Available PHP packages (APT):"
        apt-cache search "^php[0-9]" | head -10 || echo "  No packages found"
    elif command -v yum > /dev/null; then
        echo "Available PHP packages (YUM):"
        yum search php | grep "^php" | head -10 || echo "  No packages found"
    elif command -v dnf > /dev/null; then
        echo "Available PHP packages (DNF):"
        dnf search php | grep "^php" | head -10 || echo "  No packages found"
    fi
    
    echo ""
}

# Function to test dependency installation
test_dependency_installation() {
    echo -e "${CYAN}🔧 Testing Dependency Installation${NC}"
    echo "----------------------------------"
    
    # Test Docker installation
    if command -v docker > /dev/null; then
        echo "✅ Docker is installed"
        echo "Version: $(docker --version)"
        
        # Test Docker service
        if systemctl is-active --quiet docker 2>/dev/null; then
            echo "✅ Docker service is running"
        else
            echo "⚠️  Docker service is not running"
        fi
    else
        echo "❌ Docker is not installed"
        echo "Installation commands by distribution:"
        echo "  Ubuntu/Debian: sudo apt-get install docker.io"
        echo "  RHEL/CentOS: sudo yum install docker"
        echo "  Fedora: sudo dnf install docker"
    fi
    
    # Test Docker Compose
    if command -v docker-compose > /dev/null; then
        echo "✅ Docker Compose is installed"
        echo "Version: $(docker-compose --version)"
    elif docker compose version > /dev/null 2>&1; then
        echo "✅ Docker Compose (plugin) is installed"
        echo "Version: $(docker compose version)"
    else
        echo "❌ Docker Compose is not installed"
    fi
    
    # Test Composer
    if command -v composer > /dev/null; then
        echo "✅ Composer is installed"
        echo "Version: $(composer --version)"
    else
        echo "❌ Composer is not installed"
        echo "Installation: curl -sS https://getcomposer.org/installer | php"
    fi
    
    # Test Git
    if command -v git > /dev/null; then
        echo "✅ Git is installed"
        echo "Version: $(git --version)"
    else
        echo "❌ Git is not installed"
    fi
    
    # Test curl
    if command -v curl > /dev/null; then
        echo "✅ curl is installed"
        echo "Version: $(curl --version | head -1)"
    else
        echo "❌ curl is not installed"
    fi
    
    echo ""
}

# Function to test system requirements
test_system_requirements() {
    echo -e "${CYAN}💻 Testing System Requirements${NC}"
    echo "------------------------------"
    
    # Test memory
    local total_mem=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    echo "Total Memory: ${total_mem}MB"
    if [ "$total_mem" -ge 2048 ]; then
        echo "✅ Memory requirement met (>= 2GB)"
    else
        echo "⚠️  Low memory: ${total_mem}MB (recommended: >= 2GB)"
    fi
    
    # Test disk space
    local disk_space=$(df -h . | awk 'NR==2{print $4}')
    echo "Available Disk Space: $disk_space"
    
    # Test CPU cores
    local cpu_cores=$(nproc)
    echo "CPU Cores: $cpu_cores"
    if [ "$cpu_cores" -ge 2 ]; then
        echo "✅ CPU requirement met (>= 2 cores)"
    else
        echo "⚠️  Single core system (recommended: >= 2 cores)"
    fi
    
    # Test network connectivity
    if ping -c 1 google.com > /dev/null 2>&1; then
        echo "✅ Network connectivity available"
    else
        echo "❌ Network connectivity issues"
    fi
    
    echo ""
}

# Function to test file permissions and security
test_file_permissions() {
    echo -e "${CYAN}🔒 Testing File Permissions and Security${NC}"
    echo "----------------------------------------"
    
    # Test current user permissions
    echo "Current User: $(whoami)"
    echo "User ID: $(id -u)"
    echo "Group ID: $(id -g)"
    echo "Groups: $(groups)"
    
    # Test sudo access
    if sudo -n true 2>/dev/null; then
        echo "✅ Sudo access available (passwordless)"
    elif sudo -l > /dev/null 2>&1; then
        echo "✅ Sudo access available (with password)"
    else
        echo "❌ No sudo access"
    fi
    
    # Test Docker group membership
    if groups | grep -q docker; then
        echo "✅ User is in docker group"
    else
        echo "⚠️  User is not in docker group (may need sudo for Docker)"
    fi
    
    # Test SELinux status (RHEL/CentOS)
    if command -v getenforce > /dev/null; then
        local selinux_status=$(getenforce)
        echo "SELinux Status: $selinux_status"
        if [ "$selinux_status" = "Enforcing" ]; then
            echo "⚠️  SELinux is enforcing (may require additional configuration)"
        fi
    fi
    
    # Test AppArmor status (Ubuntu)
    if command -v aa-status > /dev/null; then
        echo "AppArmor Status: $(aa-status --enabled && echo 'Enabled' || echo 'Disabled')"
    fi
    
    echo ""
}

# Function to test application compatibility
test_application_compatibility() {
    local app="$1"
    echo -e "${CYAN}🚀 Testing $app Compatibility${NC}"
    echo "--------------------------------"
    
    cd "$app"
    
    # Test directory permissions
    if [ -r . ] && [ -w . ] && [ -x . ]; then
        echo "✅ Directory permissions OK"
    else
        echo "❌ Directory permission issues"
    fi
    
    # Test Docker Compose file
    if [ -f "docker-compose.yml" ]; then
        echo "✅ Docker Compose file found"
        
        # Validate Docker Compose file
        if docker-compose config > /dev/null 2>&1; then
            echo "✅ Docker Compose file is valid"
        else
            echo "❌ Docker Compose file validation failed"
        fi
    else
        echo "❌ Docker Compose file not found"
    fi
    
    # Test Composer file
    if [ -f "composer.json" ]; then
        echo "✅ Composer file found"
        
        # Validate Composer file
        if composer validate > /dev/null 2>&1; then
            echo "✅ Composer file is valid"
        else
            echo "⚠️  Composer file validation warnings"
        fi
    else
        echo "❌ Composer file not found"
    fi
    
    # Test setup script
    if [ -f "setup.sh" ]; then
        echo "✅ Setup script found"
        if [ -x "setup.sh" ]; then
            echo "✅ Setup script is executable"
        else
            echo "⚠️  Setup script is not executable"
        fi
    else
        echo "❌ Setup script not found"
    fi
    
    cd ..
    echo ""
}

# Function to generate distribution-specific installation guide
generate_installation_guide() {
    local distro="$1"
    local report_file="${RESULTS_DIR}/installation_guide_${TIMESTAMP}.md"
    
    echo -e "${PURPLE}📋 Generating Installation Guide${NC}"
    echo "================================="
    
    cat > "$report_file" << EOF
# Linux Distribution Installation Guide

**Generated:** $(date)
**Distribution:** $distro
**Architecture:** $(uname -m)

## Prerequisites

### System Requirements
- Memory: >= 2GB RAM
- Disk Space: >= 5GB available
- CPU: >= 2 cores (recommended)
- Network: Internet connectivity required

### Required Software
- Docker
- Docker Compose
- PHP 8.1+ with extensions
- Composer
- Git
- curl

## Installation Instructions

### Ubuntu/Debian
\`\`\`bash
# Update package list
sudo apt-get update

# Install Docker
sudo apt-get install -y docker.io docker-compose

# Install PHP and extensions
sudo apt-get install -y php8.1 php8.1-cli php8.1-fpm \\
    php8.1-mysql php8.1-pgsql php8.1-redis \\
    php8.1-curl php8.1-json php8.1-mbstring \\
    php8.1-xml php8.1-zip php8.1-gd

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git
sudo apt-get install -y git curl

# Add user to docker group
sudo usermod -aG docker \$USER
\`\`\`

### RHEL/CentOS 8+
\`\`\`bash
# Install Docker
sudo dnf install -y docker docker-compose

# Install PHP and extensions
sudo dnf install -y php php-cli php-fpm \\
    php-mysqlnd php-pgsql php-redis \\
    php-curl php-json php-mbstring \\
    php-xml php-zip php-gd

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git
sudo dnf install -y git curl

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker \$USER
\`\`\`

### CentOS 7
\`\`\`bash
# Install Docker
sudo yum install -y docker

# Install PHP 8.1 from Remi repository
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm
sudo yum-config-manager --enable remi-php81

sudo yum install -y php php-cli php-fpm \\
    php-mysqlnd php-pgsql php-redis \\
    php-curl php-json php-mbstring \\
    php-xml php-zip php-gd

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-\$(uname -s)-\$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Start Docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker \$USER
\`\`\`

## Post-Installation

### Verify Installation
\`\`\`bash
# Check versions
php --version
composer --version
docker --version
docker-compose --version

# Test Docker
docker run hello-world
\`\`\`

### Setup Applications
\`\`\`bash
# Clone repository
git clone <repository-url>
cd apm-php-examples

# Run setup for all applications
./setup-all-apps.sh

# Or setup individual applications
cd simple-php && ./setup.sh
\`\`\`

## Troubleshooting

### Common Issues

1. **Permission Denied (Docker)**
   - Solution: Add user to docker group and logout/login
   - Command: \`sudo usermod -aG docker \$USER\`

2. **SELinux Issues (RHEL/CentOS)**
   - Solution: Configure SELinux or set to permissive mode
   - Command: \`sudo setenforce 0\`

3. **PHP Extension Missing**
   - Solution: Install missing extensions via package manager
   - Ubuntu: \`sudo apt-get install php8.1-<extension>\`
   - RHEL: \`sudo dnf install php-<extension>\`

4. **Composer Memory Issues**
   - Solution: Increase PHP memory limit
   - Command: \`php -d memory_limit=2G /usr/local/bin/composer install\`

### Support

For additional support, please refer to:
- Docker documentation: https://docs.docker.com/
- PHP documentation: https://www.php.net/docs.php
- Composer documentation: https://getcomposer.org/doc/
EOF

    echo -e "${GREEN}✅ Installation guide generated: $report_file${NC}"
}

# Main compatibility testing process
main() {
    echo -e "${PURPLE}Starting Linux distribution compatibility testing...${NC}"
    echo ""
    
    # Detect system information
    detect_linux_distribution
    detect_package_manager
    
    # Test system compatibility
    test_php_installation
    test_dependency_installation
    test_system_requirements
    test_file_permissions
    
    # Test application compatibility
    local applications=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")
    
    for app in "${applications[@]}"; do
        if [ -d "$app" ]; then
            test_application_compatibility "$app"
        else
            echo -e "${RED}❌ Application directory $app not found${NC}"
            echo ""
        fi
    done
    
    # Generate installation guide
    local distro=$(grep '^NAME=' /etc/os-release 2>/dev/null | cut -d'"' -f2 || echo "Unknown")
    generate_installation_guide "$distro"
    
    echo -e "${GREEN}🎉 Linux distribution compatibility testing complete!${NC}"
    echo ""
    echo -e "${YELLOW}Results saved to: $RESULTS_DIR${NC}"
    echo -e "${YELLOW}View installation guide: ${RESULTS_DIR}/installation_guide_${TIMESTAMP}.md${NC}"
}

# Run main function
main "$@"
