# CentOS Stream Installation Guide - APM PHP Examples

## Overview

This guide provides step-by-step instructions for setting up the APM PHP Examples validation environment on CentOS Stream systems. All commands are non-interactive and suitable for automation.

## Supported Versions

- CentOS Stream 8
- CentOS Stream 9
- PHP 8.1, 8.2, 8.3, 8.4

## Prerequisites

- CentOS Stream 8 or 9 system with sudo access
- Internet connectivity for package downloads
- At least 2GB RAM and 10GB disk space

## Installation Steps

### 1. System Update

```bash
# Update package lists and system
sudo dnf update -y

# Install EPEL repository
sudo dnf install -y epel-release
```

### 2. Install Docker and Docker Compose

```bash
# Install required packages
sudo dnf install -y yum-utils device-mapper-persistent-data lvm2

# Add Docker repository
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo

# Install Docker Engine
sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Start and enable Docker service
sudo systemctl start docker
sudo systemctl enable docker

# Add current user to docker group
sudo usermod -aG docker $USER
```

### 3. Install PHP and Extensions

#### For CentOS Stream 9

```bash
# Install Remi repository for latest PHP versions
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm

# Enable PowerTools/CRB repository
sudo dnf config-manager --set-enabled crb
```

#### For CentOS Stream 8

```bash
# Install Remi repository for latest PHP versions
sudo dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Enable PowerTools repository
sudo dnf config-manager --set-enabled powertools
```

#### PHP 8.4 (Latest)

```bash
# Enable Remi PHP 8.4 repository
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.4 -y

# Install PHP 8.4 and required extensions
sudo dnf install -y php84-php php84-php-cli php84-php-common php84-php-mysqlnd php84-php-pgsql php84-php-redis php84-php-curl php84-php-json php84-php-mbstring php84-php-xml php84-php-zip

# Verify installation
php --version
php -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

#### PHP 8.3

```bash
# Enable Remi PHP 8.3 repository
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.3 -y

# Install PHP 8.3 and required extensions
sudo dnf install -y php83-php php83-php-cli php83-php-common php83-php-mysqlnd php83-php-pgsql php83-php-redis php83-php-curl php83-php-json php83-php-mbstring php83-php-xml php83-php-zip

# Verify installation
php --version
php -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

#### PHP 8.2

```bash
# Enable Remi PHP 8.2 repository
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.2 -y

# Install PHP 8.2 and required extensions
sudo dnf install -y php82-php php82-php-cli php82-php-common php82-php-mysqlnd php82-php-pgsql php82-php-redis php82-php-curl php82-php-json php82-php-mbstring php82-php-xml php82-php-zip

# Verify installation
php --version
php -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

#### PHP 8.1

```bash
# Enable Remi PHP 8.1 repository
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.1 -y

# Install PHP 8.1 and required extensions
sudo dnf install -y php81-php php81-php-cli php81-php-common php81-php-mysqlnd php81-php-pgsql php81-php-redis php81-php-curl php81-php-json php81-php-mbstring php81-php-xml php81-php-zip

# Verify installation
php --version
php -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

### 4. Install Additional Tools

```bash
# Install Git (if not already installed)
sudo dnf install -y git

# Install jq for JSON processing
sudo dnf install -y jq

# Install netstat for port checking
sudo dnf install -y net-tools
```

### 5. Configure SELinux (if enabled)

```bash
# Check SELinux status
sestatus

# If SELinux is enforcing, you may need to configure it for Docker
# Option 1: Set SELinux to permissive (less secure)
sudo setenforce 0
sudo sed -i 's/^SELINUX=enforcing$/SELINUX=permissive/' /etc/selinux/config

# Option 2: Configure SELinux policies for Docker (more secure)
sudo setsebool -P container_manage_cgroup on
```

### 6. Configure Firewall

```bash
# Check firewall status
sudo firewall-cmd --state

# If firewall is active, allow Docker ports
sudo firewall-cmd --permanent --zone=public --add-port=3307-3311/tcp
sudo firewall-cmd --permanent --zone=public --add-port=5433-5437/tcp
sudo firewall-cmd --permanent --zone=public --add-port=6380-6384/tcp
sudo firewall-cmd --reload
```

### 7. Verify Installation

```bash
# Check Docker
docker --version
docker compose version

# Check PHP
php --version
php -m | grep -E "(PDO|mysql|pgsql|redis|curl)"

# Test Docker without sudo (requires logout/login or newgrp docker)
docker run hello-world
```

## Post-Installation

1. **Logout and login again** to apply Docker group membership
2. **Clone the repository** and navigate to the project directory
3. **Run validation**: `./scripts/run_all_services.sh && ./scripts/validate_all.sh`

## Troubleshooting

### Common Issues

1. **Docker permission denied**: Logout and login again after adding user to docker group
2. **SELinux blocking Docker**: Configure SELinux policies or set to permissive mode
3. **Firewall blocking ports**: Configure firewall rules for required ports
4. **PHP extension not found**: Verify Remi repository is enabled and packages are installed

### Verification Commands

```bash
# Check PHP extensions
php -m | grep -E "(PDO|mysql|pgsql|redis|curl)"

# Check Docker status
sudo systemctl status docker

# Check port availability
netstat -tuln | grep -E "(3307|3308|3309|3310|3311|5433|5434|5435|5436|5437|6380|6381|6382|6383|6384)"

# Check SELinux status
sestatus

# Check firewall rules
sudo firewall-cmd --list-ports
```

## CentOS Stream Version Differences

### CentOS Stream 9
- Uses `crb` repository instead of `powertools`
- May have newer package versions by default
- Better container support

### CentOS Stream 8
- Uses `powertools` repository
- May require additional configuration for newer PHP versions
- End of life: May 31, 2024 (consider upgrading to Stream 9)

## Notes

- This guide assumes a clean CentOS Stream installation
- Commands are designed to be non-interactive for automation
- SELinux and firewall configurations may need adjustment based on security requirements
- Remi repository provides the latest PHP versions for CentOS/RHEL systems

## Validation

After installation, run the validation suite:

```bash
# Start services
./scripts/run_all_services.sh

# Run validation
./scripts/validate_all.sh

# Check results
ls -la augment/validation_results/
```
