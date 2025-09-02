# Simple PHP Application Dockerfile
FROM php:8.4-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install PostgreSQL and Redis extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY public/ /var/www/html/
COPY lib/ /var/www/html/lib/
COPY config/app.env.example /var/www/html/config/app.env.example

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
