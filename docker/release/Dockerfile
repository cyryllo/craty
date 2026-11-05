# Obraz produkcyjny Craty: aplikacja i baza MariaDB w jednym kontenerze.
#
#   docker build -f docker/release/Dockerfile -t craty .
#   docker run -d -p 8000:8000 -v craty-data:/var/www/html/app-storage craty
#
# Wszystkie dane (zdjęcia, kody QR, logi, .env i pliki bazy) leżą w jednym
# wolumenie zamontowanym w app-storage/, więc aktualizacja to pobranie nowego
# obrazu, a kopia zapasowa to kopia tego jednego wolumenu. Deweloperski obraz
# (Dockerfile w katalogu głównym) to zupełnie inna rzecz: nie zawiera kodu,
# montuje repo i ma osobny kontener bazy.

# ---------- PHP z rozszerzeniami, wspólne dla budowania i obrazu końcowego ----------
FROM php:8.4-apache-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip bcmath exif intl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# ---------- zależności PHP (bez pakietów deweloperskich) ----------
FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------- skompilowane CSS/JS ----------
# Wynik (build/) nie zależy od architektury, więc przy budowaniu na kilka
# platform ten etap działa natywnie na maszynie budującej.
FROM --platform=$BUILDPLATFORM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# Tailwind skanuje widoki paginacji Laravela w poszukiwaniu klas.
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---------- obraz końcowy ----------
FROM base

RUN apt-get update && apt-get install -y --no-install-recommends mariadb-server \
    && rm -rf /var/lib/apt/lists/* /var/lib/mysql/*

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/release/php.ini "$PHP_INI_DIR/conf.d/zz-craty.ini"
COPY docker/release/mariadb.cnf /etc/mysql/mariadb.conf.d/99-craty.cnf
COPY docker/apache/ports.conf /etc/apache2/ports.conf
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/release/apache.conf /etc/apache2/conf-enabled/zz-craty.conf
COPY --chmod=755 docker/release/entrypoint.sh /usr/local/bin/craty-entrypoint

WORKDIR /var/www/html

# Kod należy do roota i jest tylko do odczytu dla Apache (www-data).
# Zapisywalne są wyłącznie wolumen app-storage/ i bootstrap/cache.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/build ./build

# .env i katalog na pliki bazy żyją w wolumenie (app-storage/), żeby przetrwały
# wymianę obrazu. Symlink "storage" (zdjęcia, QR) od razu na miejscu, więc
# index.php ani instalator nie muszą go tworzyć w katalogu tylko do odczytu.
# Bazowy obraz PHP daje /var/www/html uprawnienia 1777: w takim katalogu jądro
# (fs.protected_symlinks) nie pozwala Apache przejść przez symlink .env
# należący do roota, a appka po cichu startowała bez konfiguracji.
RUN rm -f .env storage \
    && ln -s app-storage/.env .env \
    && ln -s /var/www/html/app-storage/app/public storage \
    && rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && chown root:root . && chmod 755 . \
    && chown -R www-data:www-data bootstrap/cache

ENV CRATY_DOCKER=true

VOLUME /var/www/html/app-storage
EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8000/up > /dev/null || exit 1

# Bazowy obraz PHP ustawia STOPSIGNAL SIGWINCH (łagodne zatrzymanie samego
# Apache). Skrypt startowy tego sygnału nie obsługuje, więc Docker po 10 s
# zabijałby wszystko, a MariaDB przy kolejnym starcie robiła crash recovery.
STOPSIGNAL SIGTERM
ENTRYPOINT ["craty-entrypoint"]
