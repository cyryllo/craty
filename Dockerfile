# Obraz deweloperski. Apache zamiast `php artisan serve`, celowo — appka nie
# ma osobnego document rootu (patrz CLAUDE.md "Project layout"), ochrona
# app/, vendor/, .env itd. zależy WYŁĄCZNIE od .htaccess, a PHP-owy
# wbudowany serwer deweloperski w ogóle nie czyta .htaccess. Bez prawdziwego
# Apache lokalnie nikt by nigdy nie sprawdził, czy te reguły faktycznie
# działają, aż do wdrożenia na prawdziwy hosting.
FROM php:8.4-apache-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev libsqlite3-dev unzip git \
        mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite gd zip bcmath exif intl pcntl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Własny port (8000, nie domyślne 80) i AllowOverride All — patrz komentarze
# w tych plikach. Bez AllowOverride All Apache po cichu ignoruje .htaccess.
COPY docker/apache/ports.conf /etc/apache2/ports.conf
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# docker-compose.yml uruchamia CAŁY kontener jako UID/GID hosta (nie roota,
# nie www-data — patrz komentarz przy "user:" tam), żeby pliki appki
# (zdjęcia, logi) nie stawały się własnością roota na zamontowanym
# storage/. Domyślne katalogi logów/PID-a Apache w tym obrazie należą do
# roota i nie są zapisywalne przez dowolny inny UID — bez tego Apache
# wywala się od razu przy starcie z "Permission denied" na error.log.
RUN mkdir -p /var/log/apache2 /var/run/apache2 \
    && chmod -R a+rwX /var/log/apache2 /var/run/apache2

# Domyślne limity PHP (post_max_size=8M, upload_max_filesize=2M) są za małe
# na paczki aktualizacyjne modułu Aktualizacje (release:build pakuje całe
# vendor/ i skompilowane assety, więc łatwo przekroczyć kilkanaście MB) —
# bez tego upload odpada na poziomie SAPI z "413 Content Too Large", zanim
# żądanie w ogóle dotrze do Laravela/walidacji.
RUN { \
        echo 'upload_max_filesize=128M'; \
        echo 'post_max_size=128M'; \
    } > /usr/local/etc/php/conf.d/craty-uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
