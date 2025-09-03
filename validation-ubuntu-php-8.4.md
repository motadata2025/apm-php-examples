# Validation Guide - Ubuntu 22.04 + PHP 8.4

## Overview

This document provides the exact commands and steps used to validate the APM PHP Examples on Ubuntu 22.04 with PHP 8.4.

## System Requirements

- Ubuntu 22.04 LTS (Jammy Jellyfish)
- PHP 8.4 with required extensions
- Docker and Docker Compose
- Git

## Host Installation Commands

### 1. System Update
```bash
sudo apt update
sudo apt upgrade -y
```

### 2. Install Docker
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

# Add user to docker group
sudo usermod -aG docker $USER

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker
```

### 3. Install PHP 8.4
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

### 4. Install Additional Tools
```bash
sudo apt install -y git jq net-tools
```

## Validation Steps

### 1. Clone Repository
```bash
git clone <repository-url>
cd apm-php-examples
git checkout compat/ubuntu-22.04/php-8.4
```

### 2. Start Services
```bash
./scripts/run_all_services.sh
```

### 3. Run Validation
```bash
./scripts/validate_all.sh
```

### 4. Check Results
```bash
ls -la augment/validation_results/
cat augment/validation_results/*-summary.json
```

## Expected Results

All applications should pass validation with the following checks:
- ✅ MySQL connectivity (ports 3307-3311)
- ✅ PostgreSQL connectivity (ports 5433-5437)  
- ✅ Redis connectivity (ports 6380-6384)
- ✅ HTTP connectivity (httpbin.org)

## Troubleshooting

### Common Issues

1. **Docker permission denied**
   ```bash
   # Logout and login again, or use:
   newgrp docker
   ```

2. **PHP extension missing**
   ```bash
   # Check installed extensions
   php8.4 -m | grep -E "(PDO|mysql|pgsql|redis|curl)"
   
   # Install missing extensions
   sudo apt install -y php8.4-<extension-name>
   ```

3. **Port conflicts**
   ```bash
   # Check port usage
   netstat -tuln | grep -E "(3307|3308|3309|3310|3311|5433|5434|5435|5436|5437|6380|6381|6382|6383|6384)"
   
   # Stop conflicting services
   sudo systemctl stop mysql postgresql redis-server
   ```

## Validation Commands Used

```bash
# Start services
./scripts/run_all_services.sh

# Validate individual applications
cd simple-php && ./validate.sh
cd ../symfony-app && ./validate.sh  
cd ../slim-framework && ./validate.sh
cd ../codeigniter-app && ./validate.sh
cd ../laravel-app && ./validate.sh

# Validate all at once
./scripts/validate_all.sh

# Check service status
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Stop services when done
docker compose -f docker/docker-compose.services.yml down
```

## Notes

- This validation was performed on a clean Ubuntu 22.04 installation
- All commands are non-interactive and suitable for automation
- PHP 8.4 is the latest stable version at time of validation
- Services run in Docker containers while PHP validation runs on the host

## Results Summary

| Application | MySQL | PostgreSQL | Redis | HTTP | Overall |
|-------------|-------|------------|-------|------|---------|
| simple-php | ✅ | ✅ | ✅ | ✅ | ✅ |
| symfony-app | ✅ | ✅ | ✅ | ✅ | ✅ |
| slim-framework | ✅ | ✅ | ✅ | ✅ | ✅ |
| codeigniter-app | ✅ | ✅ | ✅ | ✅ | ✅ |
| laravel-app | ✅ | ✅ | ✅ | ✅ | ✅ |

All applications successfully validated on Ubuntu 22.04 with PHP 8.4.
