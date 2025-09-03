# Ubuntu Installation Guide - APM PHP Examples

## Overview

This guide provides step-by-step instructions for setting up the APM PHP Examples validation environment on Ubuntu systems. All commands are non-interactive and suitable for automation.

## Supported Versions

- Ubuntu 22.04 LTS (Jammy Jellyfish)
- PHP 8.1, 8.2, 8.3, 8.4

## Prerequisites

- Ubuntu 22.04 LTS system with sudo access
- Internet connectivity for package downloads
- At least 2GB RAM and 10GB disk space

## Installation Steps

### 1. System Update

```bash
# Update package lists
sudo apt update

# Upgrade existing packages (optional but recommended)
sudo apt upgrade -y
```

### 2. Install Docker and Docker Compose

```bash
# Install required packages
sudo apt install -y ca-certificates curl gnupg lsb-release

# Add Docker's official GPG key
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# Set up Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Add current user to docker group
sudo usermod -aG docker $USER

# Start and enable Docker service
sudo systemctl start docker
sudo systemctl enable docker
```

### 3. Install PHP and Extensions

#### PHP 8.4 (Latest)

```bash
# Add Ondrej's PHP repository
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.4 and required extensions
sudo apt install -y php8.4 php8.4-cli php8.4-common php8.4-mysql php8.4-pgsql php8.4-redis php8.4-curl php8.4-json php8.4-mbstring php8.4-xml php8.4-zip

# Verify installation
php8.4 --version
php8.4 -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

#### PHP 8.3

```bash
# Install PHP 8.3 and required extensions
sudo apt install -y php8.3 php8.3-cli php8.3-common php8.3-mysql php8.3-pgsql php8.3-redis php8.3-curl php8.3-json php8.3-mbstring php8.3-xml php8.3-zip

# Verify installation
php8.3 --version
php8.3 -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

#### PHP 8.2

```bash
# Install PHP 8.2 and required extensions
sudo apt install -y php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-pgsql php8.2-redis php8.2-curl php8.2-json php8.2-mbstring php8.2-xml php8.2-zip

# Verify installation
php8.2 --version
php8.2 -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

#### PHP 8.1

```bash
# Install PHP 8.1 and required extensions
sudo apt install -y php8.1 php8.1-cli php8.1-common php8.1-mysql php8.1-pgsql php8.1-redis php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip

# Verify installation
php8.1 --version
php8.1 -m | grep -E "(pdo|mysql|pgsql|redis|curl)"
```

### 4. Install Additional Tools

```bash
# Install Git (if not already installed)
sudo apt install -y git

# Install jq for JSON processing
sudo apt install -y jq

# Install netstat for port checking
sudo apt install -y net-tools
```

### 5. Configure PHP (Optional)

```bash
# For each PHP version, you may want to adjust settings
# Example for PHP 8.4:
sudo sed -i 's/;extension=pdo_mysql/extension=pdo_mysql/' /etc/php/8.4/cli/php.ini
sudo sed -i 's/;extension=pdo_pgsql/extension=pdo_pgsql/' /etc/php/8.4/cli/php.ini
sudo sed -i 's/;extension=redis/extension=redis/' /etc/php/8.4/cli/php.ini
sudo sed -i 's/allow_url_fopen = Off/allow_url_fopen = On/' /etc/php/8.4/cli/php.ini
```

### 6. Verify Installation

```bash
# Check Docker
docker --version
docker compose version

# Check PHP versions available
ls /usr/bin/php*

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
2. **PHP extension not found**: Verify extension packages are installed for the correct PHP version
3. **Port conflicts**: Check for existing services using the required ports (3307-3311, 5433-5437, 6380-6384)

### Verification Commands

```bash
# Check PHP extensions
php -m | grep -E "(PDO|mysql|pgsql|redis|curl)"

# Check Docker status
sudo systemctl status docker

# Check port availability
netstat -tuln | grep -E "(3307|3308|3309|3310|3311|5433|5434|5435|5436|5437|6380|6381|6382|6383|6384)"
```

## Notes

- This guide assumes a clean Ubuntu 22.04 installation
- Commands are designed to be non-interactive for automation
- Multiple PHP versions can be installed simultaneously
- Use `php8.x` command to specify PHP version (e.g., `php8.4 script.php`)

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
