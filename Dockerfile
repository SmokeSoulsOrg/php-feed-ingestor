FROM php:8.4-fpm

# Install system dependencies and PHP extensions (including sockets)
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev libpq-dev libjpeg-dev libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd sockets

# Allow Git to work in container without "dubious ownership" errors
RUN git config --global --add safe.directory /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Temporary .env to allow Composer scripts to run
COPY .env.example .env

# Set permissions and make init script executable
RUN chown -R www-data:www-data /var/www/html \
    && chmod +x docker/laravel/init-migrate.sh

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Entrypoint runs Laravel initialization and background workers
ENTRYPOINT ["./docker/laravel/init-migrate.sh"]

# FPM process (not used if background tasks only, but kept for compatibility)
CMD ["php-fpm"]
