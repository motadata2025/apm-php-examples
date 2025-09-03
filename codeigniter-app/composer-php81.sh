#!/bin/bash
# Composer wrapper for PHP 8.1 to handle timezone database corruption issue
# CodeIgniter App version
# Usage: ./composer-php81.sh [composer arguments]

echo "CodeIgniter App - Composer PHP 8.1 Wrapper"
echo "==========================================="

# Set timezone environment variables to prevent corruption errors
export TZ=UTC

echo "Setting timezone to UTC..."
echo "Running composer with timezone fix..."

# Run composer with timezone settings
exec php -d date.timezone=UTC /usr/local/bin/composer "$@"
