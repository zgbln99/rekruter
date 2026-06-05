# syntax=docker/dockerfile:1
FROM php:8.4-fpm-alpine

# Zależności systemowe + rozszerzenia PHP wymagane przez Laravel/Rekruter.
RUN apk add --no-cache \
        bash git curl libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev postgresql-dev oniguruma-dev linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql gd zip bcmath intl pcntl mbstring \
    && apk add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Limity PHP (upload dokumentów / PDF).
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-rekruter.ini

WORKDIR /var/www/html

# Najpierw zależności (cache warstw)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Reszta kodu
COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
