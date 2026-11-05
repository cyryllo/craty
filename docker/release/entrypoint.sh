#!/bin/bash
# Start kontenera Craty: MariaDB + harmonogram zadań + Apache w jednym kontenerze.
#
# Przy pierwszym uruchomieniu zakłada bazę, użytkownika z losowym hasłem i .env
# w wolumenie app-storage/. Przy każdym uruchomieniu wprowadza oczekujące
# migracje, więc aktualizacja to tylko nowy obraz. Zatrzymanie kontenera
# zamyka MariaDB poprawnie, żeby nie uszkodzić plików bazy.
set -euo pipefail

APP=/var/www/html
DATA="$APP/app-storage"
DB_DIR="$DATA/database"
ENV_FILE="$DATA/.env"
SOCKET=/run/mysqld/mysqld.sock

log() { echo "[craty] $*"; }

as_www() { runuser -u www-data -- "$@"; }

env_get() {
    php -r 'require "/var/www/html/vendor/autoload.php"; echo (new App\Support\EnvFileWriter($argv[1]))->get($argv[2]) ?? "";' "$ENV_FILE" "$1"
}

env_set() {
    php -r 'require "/var/www/html/vendor/autoload.php"; (new App\Support\EnvFileWriter($argv[1]))->set([$argv[2] => $argv[3]]);' "$ENV_FILE" "$1" "$2"
}

# ---------- katalogi w wolumenie ----------
mkdir -p "$DATA/app/public" "$DATA/framework/cache/data" "$DATA/framework/sessions" "$DATA/framework/views" "$DATA/logs"

# ---------- .env ----------
if [[ ! -f "$ENV_FILE" ]]; then
    log "Pierwsze uruchomienie: tworzę konfigurację"
    cp "$APP/.env.example" "$ENV_FILE"
    env_set APP_NAME Craty
    env_set APP_ENV production
    env_set APP_DEBUG false
    env_set APP_URL "${APP_URL:-http://localhost:8000}"
    env_set LOG_LEVEL warning
    env_set DB_CONNECTION mysql
    env_set DB_HOST 127.0.0.1
    env_set DB_PORT 3306
    env_set DB_DATABASE craty
    env_set DB_USERNAME craty
    env_set DB_PASSWORD "$(php -r 'echo bin2hex(random_bytes(24));')"
fi
if [[ -z "$(env_get APP_KEY)" ]]; then
    env_set APP_KEY "base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

chown www-data:www-data "$DATA" "$ENV_FILE"
chmod 600 "$ENV_FILE"
# Bez -R na całym wolumenie: przy wielu zdjęciach to trwałoby przy każdym starcie.
chown -R www-data:www-data "$DATA/app" "$DATA/framework" "$DATA/logs"

# ---------- MariaDB ----------
mkdir -p /run/mysqld "$DB_DIR"
chown mysql:mysql /run/mysqld
chown -R mysql:mysql "$DB_DIR"
chmod 700 "$DB_DIR"

FIRST_DB=0
if [[ ! -d "$DB_DIR/mysql" ]]; then
    log "Pierwsze uruchomienie: zakładam bazę danych"
    mariadb-install-db --user=mysql --skip-test-db --auth-root-authentication-method=socket > /dev/null
    FIRST_DB=1
fi

mariadbd --user=mysql &
DB_PID=$!

for _ in $(seq 1 60); do
    mariadb-admin --socket="$SOCKET" ping --silent 2> /dev/null && break
    if ! kill -0 "$DB_PID" 2> /dev/null; then
        log "MariaDB nie wystartowała"
        exit 1
    fi
    sleep 1
done

if [[ "$FIRST_DB" == 1 ]]; then
    DB_PASSWORD="$(env_get DB_PASSWORD)"
    mariadb --socket="$SOCKET" <<SQL
CREATE DATABASE IF NOT EXISTS craty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'craty'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS 'craty'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON craty.* TO 'craty'@'127.0.0.1';
GRANT ALL PRIVILEGES ON craty.* TO 'craty'@'localhost';
FLUSH PRIVILEGES;
SQL
else
    # Nowszy obraz może mieć nowszą MariaDB: dopasowuje tabele systemowe, gdy trzeba.
    mariadb-upgrade --socket="$SOCKET" --silent > /dev/null 2>&1 || true
fi

# ---------- aplikacja ----------
# Bezpieczne także przed instalacją: kreator po prostu nie będzie już miał
# czego migrować i założy tylko konto administratora.
as_www php "$APP/artisan" migrate --force --no-interaction || log "Migracja nie powiodła się, szczegóły w app-storage/logs"
as_www php "$APP/artisan" view:clear --no-interaction > /dev/null || true

# Harmonogram (np. dzienne przypomnienia o zaległych wypożyczeniach).
(
    while true; do
        sleep 60
        as_www php "$APP/artisan" schedule:run --no-interaction > /dev/null 2>&1 || true
    done
) &
SCHED_PID=$!

apache2-foreground &
WEB_PID=$!

log "Craty działa na porcie 8000"

shutdown() {
    log "Zatrzymuję"
    kill -TERM "$WEB_PID" "$SCHED_PID" 2> /dev/null || true
    mariadb-admin --socket="$SOCKET" shutdown 2> /dev/null || kill -TERM "$DB_PID" 2> /dev/null || true
    wait "$DB_PID" 2> /dev/null || true
    exit "${1:-0}"
}
trap 'shutdown 0' TERM INT

# Gdy padnie Apache albo baza, kończymy cały kontener, żeby Docker mógł go zrestartować.
wait -n "$WEB_PID" "$DB_PID" || true
log "Apache albo MariaDB zakończyły pracę"
shutdown 1
