# PHP 8.2 FPM base image
# Installs: pdo_mysql, composer, required PHP extensions
# Working directory: /var/www/html
# Runs: composer install, then php-fpm

FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev unzip curl \
    && docker-php-ext-install pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY api/ .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 9000

CMD ["php-fpm"]
