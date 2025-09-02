# Linux Distribution Installation Guide

**Generated:** Wed 03 Sep 2025 12:38:02 AM IST
**Distribution:** Ubuntu
**Architecture:** x86_64

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
```bash
# Update package list
sudo apt-get update

# Install Docker
sudo apt-get install -y docker.io docker-compose

# Install PHP and extensions
sudo apt-get install -y php8.1 php8.1-cli php8.1-fpm \
    php8.1-mysql php8.1-pgsql php8.1-redis \
    php8.1-curl php8.1-json php8.1-mbstring \
    php8.1-xml php8.1-zip php8.1-gd

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git
sudo apt-get install -y git curl

# Add user to docker group
sudo usermod -aG docker $USER
```

### RHEL/CentOS 8+
```bash
# Install Docker
sudo dnf install -y docker docker-compose

# Install PHP and extensions
sudo dnf install -y php php-cli php-fpm \
    php-mysqlnd php-pgsql php-redis \
    php-curl php-json php-mbstring \
    php-xml php-zip php-gd

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git
sudo dnf install -y git curl

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker $USER
```

### CentOS 7
```bash
# Install Docker
sudo yum install -y docker

# Install PHP 8.1 from Remi repository
sudo yum install -y epel-release
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm
sudo yum-config-manager --enable remi-php81

sudo yum install -y php php-cli php-fpm \
    php-mysqlnd php-pgsql php-redis \
    php-curl php-json php-mbstring \
    php-xml php-zip php-gd

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Start Docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker $USER
```

## Post-Installation

### Verify Installation
```bash
# Check versions
php --version
composer --version
docker --version
docker-compose --version

# Test Docker
docker run hello-world
```

### Setup Applications
```bash
# Clone repository
git clone <repository-url>
cd apm-php-examples

# Run setup for all applications
./setup-all-apps.sh

# Or setup individual applications
cd simple-php && ./setup.sh
```

## Troubleshooting

### Common Issues

1. **Permission Denied (Docker)**
   - Solution: Add user to docker group and logout/login
   - Command: `sudo usermod -aG docker $USER`

2. **SELinux Issues (RHEL/CentOS)**
   - Solution: Configure SELinux or set to permissive mode
   - Command: `sudo setenforce 0`

3. **PHP Extension Missing**
   - Solution: Install missing extensions via package manager
   - Ubuntu: `sudo apt-get install php8.1-<extension>`
   - RHEL: `sudo dnf install php-<extension>`

4. **Composer Memory Issues**
   - Solution: Increase PHP memory limit
   - Command: `php -d memory_limit=2G /usr/local/bin/composer install`

### Support

For additional support, please refer to:
- Docker documentation: https://docs.docker.com/
- PHP documentation: https://www.php.net/docs.php
- Composer documentation: https://getcomposer.org/doc/
