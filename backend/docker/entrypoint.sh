#!/bin/sh
set -e

# Czekaj na PostgreSQL.
echo "Czekam na bazę danych ${DB_HOST}:${DB_PORT}..."
until php -r "exit(@fsockopen(getenv('DB_HOST'), (int)getenv('DB_PORT')) ? 0 : 1);" 2>/dev/null; do
  sleep 1
done

# Klucz aplikacji (jeśli nie ustawiony przez środowisko).
if [ -z "${APP_KEY}" ]; then
  php artisan key:generate --force || true
fi

# Migracje (+ seed startowy przy pierwszym uruchomieniu, jeśli włączony).
php artisan migrate --force
if [ "${RUN_SEED}" = "true" ]; then
  php artisan db:seed --force || true
fi

php artisan storage:link || true

exec "$@"
