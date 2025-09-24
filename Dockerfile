# Dockerfile
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zip curl \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN composer global require laravel/installer \
    && echo 'export PATH="$PATH:/root/.composer/vendor/bin"' >> /root/.bashrc
