FROM php:8.4-cli-alpine

RUN apk add --no-cache curl libzip-dev oniguruma-dev zip unzip

RUN docker-php-ext-install pdo pdo_mysql mbstring zip bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .

EXPOSE 8001

CMD ["php", "bin/server.php"]
