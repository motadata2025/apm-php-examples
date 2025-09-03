#!/bin/bash
# Composer wrapper for PHP 8.1 to handle timezone database corruption issue
# Usage: ./composer-php81.sh [composer arguments]

# Set timezone environment variables to prevent corruption errors
export TZ=UTC

# Run composer with timezone settings
exec php -d date.timezone=UTC /usr/local/bin/composer "$@"
