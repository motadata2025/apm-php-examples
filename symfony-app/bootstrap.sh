#!/bin/bash
# Symfony App Bootstrap Script
# Idempotent script to set up the Symfony application

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$SCRIPT_DIR/../augment/logs"
ERROR_LOG="$LOG_DIR/bootstrap-error.log"

# Ensure log directory exists
mkdir -p "$LOG_DIR"

echo "Symfony App Bootstrap Starting..." | tee "$ERROR_LOG"
echo "Working directory: $SCRIPT_DIR" | tee -a "$ERROR_LOG"
echo "Timestamp: $(date)" | tee -a "$ERROR_LOG"

# Function to log and exit on error
log_error_and_exit() {
    local message="$1"
    local exit_code="${2:-1}"
    echo "ERROR: $message" | tee -a "$ERROR_LOG"
    echo "Bootstrap failed at $(date)" | tee -a "$ERROR_LOG"
    exit "$exit_code"
}

# Check if composer is available
if ! command -v composer >/dev/null 2>&1; then
    log_error_and_exit "Composer is not installed or not in PATH"
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP Version: $PHP_VERSION" | tee -a "$ERROR_LOG"

# Check required PHP extensions (case-insensitive)
REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "pdo_pgsql" "json" "ctype" "iconv")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -qi "^$ext$"; then
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}" | tee -a "$ERROR_LOG"
    echo "Current PHP modules:" | tee -a "$ERROR_LOG"
    php -m | tee -a "$ERROR_LOG"
    log_error_and_exit "Required PHP extensions are missing: ${MISSING_EXTENSIONS[*]}"
fi

# Run composer install
echo "Running composer install..." | tee -a "$ERROR_LOG"
if ! composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1 | tee -a "$ERROR_LOG"; then
    log_error_and_exit "Composer install failed"
fi

# Create necessary directories
echo "Creating Symfony directories..." | tee -a "$ERROR_LOG"
mkdir -p var/cache var/log var/sessions config/packages public/css src/Controller src/Command templates

# Set proper permissions
chmod -R 755 var/ 2>/dev/null || true
chmod -R 755 public/ 2>/dev/null || true

# Create .env.local if it doesn't exist (don't overwrite .env)
if [ ! -f ".env.local" ]; then
    echo "Creating .env.local..." | tee -a "$ERROR_LOG"
    cat > .env.local << 'EOF'
# Local environment overrides
# This file is created by bootstrap.sh and can be customized
APP_ENV=dev
APP_DEBUG=true
EOF
fi

# Create basic Symfony kernel if it doesn't exist
if [ ! -f "src/Kernel.php" ]; then
    echo "Creating Symfony Kernel..." | tee -a "$ERROR_LOG"
    mkdir -p src
    cat > src/Kernel.php << 'EOF'
<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
EOF
fi

# Create basic console application
if [ ! -f "bin/console" ]; then
    echo "Creating console application..." | tee -a "$ERROR_LOG"
    mkdir -p bin
    cat > bin/console << 'EOF'
#!/usr/bin/env php
<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

if (!is_file(dirname(__DIR__).'/vendor/autoload.php')) {
    throw new LogicException('Dependencies are missing. Try running "composer install".');
}

require_once dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$application = new Application($kernel);
$application->run();
EOF
    chmod +x bin/console
fi

echo "Bootstrap completed successfully at $(date)" | tee -a "$ERROR_LOG"
echo "Vendor directory created: $([ -d vendor ] && echo 'YES' || echo 'NO')" | tee -a "$ERROR_LOG"
echo "Autoloader exists: $([ -f vendor/autoload.php ] && echo 'YES' || echo 'NO')" | tee -a "$ERROR_LOG"

exit 0
