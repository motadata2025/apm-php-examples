#!/bin/bash
# PHP wrapper for PHP 8.1 to handle timezone database corruption issue
# Symfony version
# Usage: ./php-php81.sh [php arguments]

echo "Symfony App - PHP 8.1 Wrapper"
echo "=============================="

# Set timezone environment variables to prevent corruption errors
export TZ=UTC

echo "Setting timezone to UTC..."
echo "Running PHP with timezone fix..."

# Run PHP with timezone settings
exec php -d date.timezone=UTC "$@"
