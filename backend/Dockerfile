FROM php:8.4-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    zip \
    unzip

# PHP extensions required by Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

# Install PHP dependencies (production only)
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# Copy app source
COPY . .

# Ensure the framework's writable dirs exist and are writable *before* any
# artisan command runs (package:discover writes to bootstrap/cache).
RUN mkdir -p bootstrap/cache \
        storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/logs && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Run post-install scripts now that source is present
RUN composer run-script post-autoload-dump

# Entrypoint: wait for DB → migrate → seed → start PHP-FPM
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
