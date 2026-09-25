#!/bin/sh
set -e

# Render (y cualquier PaaS) levanta el contenedor sin .env: la configuración
# llega por variables de entorno. Acá sólo preparamos lo que depende de que la
# base ya esté disponible.

if [ -z "$APP_KEY" ]; then
    echo "ERROR: falta APP_KEY. Generala con 'php artisan key:generate --show' y cargala como variable de entorno."
    exit 1
fi

echo "==> Ejecutando migraciones"
php artisan migrate --force

if [ "$DB_SEED_ON_BOOT" = "true" ]; then
    echo "==> Cargando datos de demostración"
    php artisan db:seed --force
    php artisan db:seed --class=DemoSeeder --force
fi

echo "==> Enlazando storage público"
php artisan storage:link || true

echo "==> Cacheando configuración, rutas y vistas"
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
