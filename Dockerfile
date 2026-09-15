# Obraz deweloperski (php artisan serve przez docker-compose). Obraz produkcyjny
# (php-fpm + nginx, kolejka, cron) to osobny temat na etap wdrożenia.
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev libsqlite3-dev unzip git \
        mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite gd zip bcmath exif intl pcntl \
    && rm -rf /var/lib/apt/lists/*

# Domyślne limity PHP (post_max_size=8M, upload_max_filesize=2M) są za małe
# na paczki aktualizacyjne modułu Aktualizacje (release:build pakuje całe
# vendor/ i public/build, więc łatwo przekroczyć kilkanaście MB) — bez tego
# upload odpada na poziomie SAPI z "413 Content Too Large", zanim żądanie
# w ogóle dotrze do Laravela/walidacji.
RUN { \
        echo 'upload_max_filesize=128M'; \
        echo 'post_max_size=128M'; \
    } > /usr/local/etc/php/conf.d/craty-uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
