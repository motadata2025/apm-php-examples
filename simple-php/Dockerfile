# Use official PHP 8.4 CLI image
FROM php:8.4-cli

WORKDIR /app

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    libpng-dev \
    libxml2-dev \
    default-mysql-client \
    default-libmysqlclient-dev \
    build-essential \
    && docker-php-ext-install \
        pdo pdo_pgsql pgsql \
        pdo_mysql mysqli \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Install Composer (stable method without pulling separate image)
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/bin --filename=composer \
    && rm composer-setup.php

# Copy app code
COPY . .

# Install PHP dependencies if composer.json exists
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

# Expose default port (for PHP built-in server if needed)
EXPOSE 8011

# Default command (PHP built-in server for CLI apps)
CMD [ "php", "-S","0.0.0.0:8011","-t","public" ]
