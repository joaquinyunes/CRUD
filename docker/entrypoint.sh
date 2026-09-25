#!/bin/sh
set -e

# Render (y cualquier PaaS) levanta el contenedor sin .env: la configuración
# llega por variables de entorno. Acá sólo preparamos lo que depende de que la
# base ya esté disponible.

if [ -z "$APP_KEY" ]; then
    echo "ERROR: falta APP_KEY. Generala con 'php artisan key:generate --show' y cargala como variable de entorno."
    exit 1
fi

# La plataforma decide en qué puerto tiene que escuchar el proceso.
export SERVER_NAME=":${PORT:-8080}"

echo "==> Ejecutando migraciones"
php artisan migrate --force

# Cuántas filas hay en users nos dice si la base ya fue poblada alguna vez.
# Si la consulta falla por lo que sea, asumimos base vacía y sembramos.
usuarios=$(php artisan tinker --execute="echo \DB::table('users')->count();" 2>/dev/null | tr -dc '0-9') || usuarios=""
[ -n "$usuarios" ] || usuarios=0

if [ "$DB_SEED_ALWAYS" = "true" ] || { [ "$DB_SEED_ON_BOOT" = "true" ] && [ "$usuarios" -eq 0 ]; }; then
    echo "==> Cargando datos de demostración"
    php artisan db:seed --force
    php artisan db:seed --class=DemoSeeder --force
else
    echo "==> La base ya tiene datos; se omite el seed"
fi

echo "==> Enlazando storage público"
php artisan storage:link || true

echo "==> Cacheando configuración, rutas y vistas"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Iniciando servidor en el puerto ${PORT:-8080}"
exec "$@"
