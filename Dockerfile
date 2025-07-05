FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev libpq-dev libjpeg-dev libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy app files
COPY . .

# TEMP .env to allow composer to work
COPY .env.example .env

# Set permissions and make scripts executable
RUN chown -R www-data:www-data /var/www/html \
    && chmod +x docker/laravel/init-migrate.sh

# Install PHP deps
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

USER root

# Entrypoint: run init-migrate.sh
ENTRYPOINT ["./docker/laravel/init-migrate.sh"]

CMD ["php-fpm"]
