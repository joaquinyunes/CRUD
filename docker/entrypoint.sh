#!/usr/bin/env bash
set -e

cd /var/www/html

# Puerto dinámico (Render/Railway inyectan $PORT)
PORT="${PORT:-8080}"
sed -i "s/listen 8080;/listen ${PORT};/" /etc/nginx/nginx.conf

# APP_KEY: generar en runtime si no vino por env
if [ -z "${APP_KEY}" ]; then
    export APP_KEY="$(php artisan key:generate --show)"
    echo "APP_KEY generada en runtime (seteala como env var para que persista entre deploys)."
fi

# Cache de framework (rápido en prod)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

# Migraciones (idempotente)
php artisan migrate --force --no-interaction

# Storage público
php artisan storage:link || true

exec "$@"
