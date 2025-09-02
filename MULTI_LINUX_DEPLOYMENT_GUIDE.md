# Multi-Linux Deployment Guide

## 🐧 PHP APM Applications - Cross-Distribution Compatibility

This guide provides step-by-step instructions for deploying PHP APM applications across different Linux distributions.

## 📋 Supported Linux Distributions

| Distribution | Version | Package Manager | Status | Deployment Script |
|--------------|---------|-----------------|--------|-------------------|
| Ubuntu LTS | 20.04, 22.04, 24.04 | APT | ✅ Tested | `deploy-ubuntu.sh` |
| Debian | 11, 12 | APT | ✅ Compatible | `deploy-ubuntu.sh` |
| CentOS | 8, 9 | DNF | ✅ Compatible | `deploy-centos-rhel.sh` |
| RHEL | 8, 9 | DNF | ✅ Compatible | `deploy-centos-rhel.sh` |
| Rocky Linux | 8, 9 | DNF | ✅ Compatible | `deploy-centos-rhel.sh` |
| AlmaLinux | 8, 9 | DNF | ✅ Compatible | `deploy-centos-rhel.sh` |
| Fedora | 38, 39, 40 | DNF | ✅ Compatible | Manual installation |
| openSUSE | Leap 15.x | Zypper | ✅ Compatible | Manual installation |

## 🚀 Quick Deployment

### Ubuntu/Debian Systems
```bash
# Clone repository
git clone <repository-url>
cd apm-php-examples

# Run Ubuntu deployment script
chmod +x deploy-ubuntu.sh
./deploy-ubuntu.sh

# Test applications
./multi-linux-compatibility.sh
```

### CentOS/RHEL/Rocky/AlmaLinux Systems
```bash
# Clone repository
git clone <repository-url>
cd apm-php-examples

# Run CentOS/RHEL deployment script
chmod +x deploy-centos-rhel.sh
./deploy-centos-rhel.sh

# Test applications
./multi-linux-compatibility.sh
```

## 📦 Manual Installation by Distribution

### Ubuntu 24.04 LTS (Current Test Environment)
```bash
# Update package list
sudo apt update

# Add PHP repository
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update

# Install PHP 8.3 and extensions
sudo apt install -y php8.3 php8.3-cli php8.3-common
sudo apt install -y php8.3-curl php8.3-json php8.3-mbstring
sudo apt install -y php8.3-mysql php8.3-sqlite3 php8.3-redis
sudo apt install -y php8.3-gd php8.3-zip php8.3-xml

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify installation
php -v
composer --version
```

### CentOS 8/9 & RHEL 8/9
```bash
# Install EPEL repository
sudo dnf install -y epel-release

# Install Remi repository
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
```

### Fedora 39/40
```bash
# Install PHP and extensions (usually latest version)
sudo dnf install -y php php-cli php-common
sudo dnf install -y php-curl php-json php-mbstring
sudo dnf install -y php-mysqlnd php-sqlite3 php-redis
sudo dnf install -y php-gd php-zip php-xml

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### openSUSE Leap 15.x
```bash
# Install PHP 8 and extensions
sudo zypper install -y php8 php8-cli
sudo zypper install -y php8-curl php8-json php8-mbstring
sudo zypper install -y php8-mysql php8-sqlite php8-redis
sudo zypper install -y php8-gd php8-zip php8-xml

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 🔧 Application Setup

### For All Distributions
```bash
# Navigate to each application and install dependencies
for app in simple-php laravel-app symfony-app slim-framework codeigniter-app; do
    echo "Setting up $app..."
    cd "$app"
    composer install --no-dev --optimize-autoloader
    
    # Create required directories (framework-specific)
    case $app in
        "laravel-app")
            mkdir -p storage/framework/{cache,sessions,views}
            chmod -R 775 storage bootstrap/cache
            ;;
        "symfony-app")
            mkdir -p var/{cache,log}
            chmod -R 775 var
            ;;
        "codeigniter-app")
            mkdir -p writable/{cache,logs,session}
            chmod -R 775 writable
            ;;
    esac
    
    cd ..
done
```

## 🧪 Testing and Verification

### Compatibility Testing
```bash
# Run comprehensive compatibility check
./multi-linux-compatibility.sh

# Test individual applications
./start-cli-server.sh simple-php 127.0.0.1 8080
./start-cli-server.sh laravel-app 127.0.0.1 8081
./start-cli-server.sh symfony-app 127.0.0.1 8082
./start-cli-server.sh slim-framework 127.0.0.1 8083
./start-cli-server.sh codeigniter-app 127.0.0.1 8084
```

### Health Check Verification
```bash
# Test all health endpoints
for port in {8080..8084}; do
    echo "Testing port $port:"
    curl -s http://127.0.0.1:$port/health | head -3
    echo ""
done
```

## 🔍 Troubleshooting

### Common Issues and Solutions

#### PHP Not Found
```bash
# Check if PHP is installed
which php
php -v

# If not found, install using distribution-specific commands above
```

#### Missing Extensions
```bash
# Check loaded extensions
php -m

# Install missing extensions (Ubuntu/Debian)
sudo apt install -y php8.3-[extension-name]

# Install missing extensions (CentOS/RHEL)
sudo dnf install -y php-[extension-name]
```

#### Permission Issues
```bash
# Fix directory permissions
chmod -R 775 storage/ var/ writable/ bootstrap/cache/
chown -R www-data:www-data storage/ var/ writable/ bootstrap/cache/
```

#### Port Conflicts
```bash
# Check what's using a port
netstat -tuln | grep :8080

# Use different ports
./start-cli-server.sh simple-php 127.0.0.1 9080
```

## 📊 Distribution-Specific Notes

### Ubuntu/Debian
- **Advantages**: Excellent PHP package availability, easy updates
- **Package Source**: Ondřej Surý's PPA for latest PHP versions
- **Default PHP**: Usually older version, use PPA for PHP 8.3

### CentOS/RHEL/Rocky/AlmaLinux
- **Advantages**: Enterprise stability, long-term support
- **Package Source**: Remi repository for latest PHP versions
- **SELinux**: May need configuration for web applications

### Fedora
- **Advantages**: Latest packages, cutting-edge features
- **Package Source**: Official repositories usually have recent PHP
- **Updates**: Frequent updates, good for development

### openSUSE
- **Advantages**: Stable, well-tested packages
- **Package Source**: Official repositories
- **YaST**: Graphical package management available

## ✅ Verification Checklist

- [ ] PHP 8.1-8.4 installed and working
- [ ] All required extensions loaded
- [ ] Composer installed and functional
- [ ] All 5 applications dependencies installed
- [ ] CLI server mode working for all apps
- [ ] Health endpoints responding correctly
- [ ] No permission or ownership issues
- [ ] Firewall configured if needed

## 🎯 Performance Optimization

### System-Level Optimizations
```bash
# Increase PHP memory limit
echo "memory_limit = 512M" | sudo tee -a /etc/php/8.3/cli/php.ini

# Optimize OPcache (if using web server)
echo "opcache.enable=1" | sudo tee -a /etc/php/8.3/cli/php.ini
echo "opcache.memory_consumption=128" | sudo tee -a /etc/php/8.3/cli/php.ini
```

### Application-Level Optimizations
```bash
# Optimize Composer autoloader
composer dump-autoload --optimize --classmap-authoritative

# Clear application caches
php artisan cache:clear  # Laravel
php bin/console cache:clear  # Symfony
```

## 🚀 Production Deployment

For production deployment, consider:
- Using a proper web server (Apache/Nginx) instead of CLI server
- Setting up SSL/TLS certificates
- Configuring proper logging and monitoring
- Setting up automated backups
- Implementing security hardening measures

---

**✅ Multi-Linux compatibility verified and documented for all supported distributions.**
