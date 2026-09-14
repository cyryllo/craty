# Obraz deweloperski (php artisan serve przez docker-compose). Obraz produkcyjny
# (php-fpm + nginx, kolejka, cron) to osobny temat na etap wdrożenia.
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev libsqlite3-dev unzip git \
        mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite gd zip bcmath exif intl pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
