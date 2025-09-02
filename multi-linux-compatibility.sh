#!/bin/bash

# Multi-Linux Compatibility Script
# Purpose: Ensure PHP APM applications work across different Linux distributions

echo "🔧 MULTI-LINUX COMPATIBILITY CHECKER"
echo "===================================="
echo ""

# Function to detect Linux distribution
detect_distribution() {
    local distro="unknown"
    local version="unknown"
    local package_manager="unknown"
    
    # Try multiple methods to detect distribution
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        distro="$ID"
        version="$VERSION_ID"
    elif [ -f /etc/lsb-release ]; then
        . /etc/lsb-release
        distro="$DISTRIB_ID"
        version="$DISTRIB_RELEASE"
    elif [ -f /etc/redhat-release ]; then
        distro="rhel"
        version=$(cat /etc/redhat-release | grep -oE '[0-9]+\.[0-9]+' | head -1)
    fi
    
    # Detect package manager
    if command -v apt >/dev/null 2>&1; then
        package_manager="apt"
    elif command -v yum >/dev/null 2>&1; then
        package_manager="yum"
    elif command -v dnf >/dev/null 2>&1; then
        package_manager="dnf"
    elif command -v zypper >/dev/null 2>&1; then
        package_manager="zypper"
    fi
    
    echo "📋 Distribution Information:"
    echo "  Distribution: $distro"
    echo "  Version: $version"
    echo "  Package Manager: $package_manager"
    echo "  Kernel: $(uname -r)"
    echo "  Architecture: $(uname -m)"
    echo ""
    
    export DETECTED_DISTRO="$distro"
    export DETECTED_VERSION="$version"
    export DETECTED_PKG_MGR="$package_manager"
}

# Function to check PHP installation method
check_php_installation() {
    echo "🔧 PHP Installation Analysis:"
    
    local php_binary=$(which php 2>/dev/null || echo "not found")
    echo "  PHP Binary: $php_binary"
    
    if [ "$php_binary" != "not found" ]; then
        echo "  PHP Version: $(php -v | head -1)"
        echo "  Thread Safety: $(php -r "echo defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';")"
        echo "  Configuration: $(php --ini | grep 'Loaded Configuration File' | cut -d':' -f2 | xargs)"
        
        # Check installation method
        if command -v dpkg >/dev/null 2>&1 && dpkg -l | grep -q php; then
            echo "  Installation: APT package manager"
            echo "  Packages: $(dpkg -l | grep php | wc -l) PHP packages installed"
        elif command -v rpm >/dev/null 2>&1 && rpm -qa | grep -q php; then
            echo "  Installation: RPM package manager"
            echo "  Packages: $(rpm -qa | grep php | wc -l) PHP packages installed"
        else
            echo "  Installation: Custom/Source build"
        fi
    else
        echo "  ❌ PHP not found in PATH"
    fi
    echo ""
}

# Function to generate OS-specific installation commands
generate_installation_commands() {
    echo "📦 OS-Specific PHP Installation Commands:"
    echo ""
    
    case "$DETECTED_DISTRO" in
        "ubuntu"|"debian")
            echo "  🔹 Ubuntu/Debian (APT):"
            echo "    sudo apt update"
            echo "    sudo apt install -y php8.3 php8.3-cli php8.3-common"
            echo "    sudo apt install -y php8.3-curl php8.3-json php8.3-mbstring"
            echo "    sudo apt install -y php8.3-mysql php8.3-sqlite3 php8.3-redis"
            echo "    sudo apt install -y php8.3-gd php8.3-zip php8.3-xml"
            ;;
        "centos"|"rhel"|"rocky"|"almalinux")
            echo "  🔹 CentOS/RHEL/Rocky/AlmaLinux (YUM/DNF):"
            echo "    sudo dnf install -y epel-release"
            echo "    sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm"
            echo "    sudo dnf module enable php:remi-8.3"
            echo "    sudo dnf install -y php php-cli php-common"
            echo "    sudo dnf install -y php-curl php-json php-mbstring"
            echo "    sudo dnf install -y php-mysqlnd php-sqlite3 php-redis"
            echo "    sudo dnf install -y php-gd php-zip php-xml"
            ;;
        "fedora")
            echo "  🔹 Fedora (DNF):"
            echo "    sudo dnf install -y php php-cli php-common"
            echo "    sudo dnf install -y php-curl php-json php-mbstring"
            echo "    sudo dnf install -y php-mysqlnd php-sqlite3 php-redis"
            echo "    sudo dnf install -y php-gd php-zip php-xml"
            ;;
        "opensuse"|"sles")
            echo "  🔹 openSUSE/SLES (Zypper):"
            echo "    sudo zypper install -y php8 php8-cli"
            echo "    sudo zypper install -y php8-curl php8-json php8-mbstring"
            echo "    sudo zypper install -y php8-mysql php8-sqlite php8-redis"
            echo "    sudo zypper install -y php8-gd php8-zip php8-xml"
            ;;
        *)
            echo "  🔹 Generic Linux (Source/Manual):"
            echo "    # Download PHP 8.3 source from php.net"
            echo "    # Configure with: --enable-cli --with-curl --with-openssl"
            echo "    # Install required development packages first"
            echo "    # Compile and install to /usr/local/bin"
            ;;
    esac
    echo ""
}

# Function to test application compatibility on current system
test_system_compatibility() {
    echo "🧪 Testing Application Compatibility on Current System:"
    echo ""
    
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
        
        cd "$app"
        
        # Test CLI server startup
        timeout 5s php -S 127.0.0.1:$port -t public >/dev/null 2>&1 &
        local server_pid=$!
        sleep 2
        
        # Test endpoints
        local root_status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$port/ 2>/dev/null || echo "000")
        local health_status=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$port/health 2>/dev/null || echo "000")
        
        # Kill server
        kill $server_pid 2>/dev/null || true
        
        cd ..
        
        if [ "$root_status" = "200" ]; then
            echo "    ✅ Working (Root: $root_status, Health: $health_status)"
            ((working_apps++))
        else
            echo "    ❌ Failed (Root: $root_status, Health: $health_status)"
        fi
    done
    
    echo ""
    echo "📊 System Compatibility: $working_apps/$total_apps applications working"
    
    if [ $working_apps -eq $total_apps ]; then
        echo "✅ All applications compatible with current Linux system"
        return 0
    else
        echo "⚠️  Some applications have issues on current system"
        return 1
    fi
}

# Function to create distribution-specific deployment scripts
create_deployment_scripts() {
    echo "📋 Creating Distribution-Specific Deployment Scripts..."
    
    # Ubuntu/Debian deployment script
    cat > "deploy-ubuntu.sh" << 'EOF'
#!/bin/bash
# Ubuntu/Debian Deployment Script for PHP APM Applications

echo "🚀 Deploying PHP APM Applications on Ubuntu/Debian"

# Update package list
sudo apt update

# Install PHP 8.3 and required extensions
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-common
sudo apt install -y php8.3-curl php8.3-json php8.3-mbstring
sudo apt install -y php8.3-mysql php8.3-sqlite3 php8.3-redis
sudo apt install -y php8.3-gd php8.3-zip php8.3-xml

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install dependencies for all applications
for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do
    if [ -d "$app" ]; then
        echo "Installing dependencies for $app..."
        cd "$app"
        composer install --no-dev --optimize-autoloader
        cd ..
    fi
done

echo "✅ Ubuntu/Debian deployment complete"
EOF

    # CentOS/RHEL deployment script
    cat > "deploy-centos-rhel.sh" << 'EOF'
#!/bin/bash
# CentOS/RHEL Deployment Script for PHP APM Applications

echo "🚀 Deploying PHP APM Applications on CentOS/RHEL"

# Install EPEL and Remi repositories
sudo dnf install -y epel-release
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Enable PHP 8.3 module
sudo dnf module enable -y php:remi-8.3

# Install PHP and extensions
sudo dnf install -y php php-cli php-common
sudo dnf install -y php-curl php-json php-mbstring
sudo dnf install -y php-mysqlnd php-sqlite3 php-redis
sudo dnf install -y php-gd php-zip php-xml

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install dependencies for all applications
for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do
    if [ -d "$app" ]; then
        echo "Installing dependencies for $app..."
        cd "$app"
        composer install --no-dev --optimize-autoloader
        cd ..
    fi
done

echo "✅ CentOS/RHEL deployment complete"
EOF

    chmod +x deploy-ubuntu.sh deploy-centos-rhel.sh
    echo "  ✅ Created deploy-ubuntu.sh"
    echo "  ✅ Created deploy-centos-rhel.sh"
    echo ""
}

# Main execution
echo "Starting multi-Linux compatibility analysis..."
echo ""

# Detect current distribution
detect_distribution

# Check PHP installation
check_php_installation

# Generate installation commands
generate_installation_commands

# Test current system compatibility
test_system_compatibility

# Create deployment scripts
create_deployment_scripts

echo "🎯 MULTI-LINUX COMPATIBILITY ANALYSIS COMPLETE"
echo ""
echo "📋 Summary:"
echo "  ✅ Distribution detected: $DETECTED_DISTRO $DETECTED_VERSION"
echo "  ✅ Package manager: $DETECTED_PKG_MGR"
echo "  ✅ PHP installation analyzed"
echo "  ✅ OS-specific commands generated"
echo "  ✅ System compatibility tested"
echo "  ✅ Deployment scripts created"
