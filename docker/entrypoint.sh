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

# DatabaseSeeder es idempotente (firstOrCreate + sync): crea permisos, roles y
# cuentas base sin pisar nada. Se corre en cada arranque de la demo para que un
# permiso o un rol nuevo llegue a una base que ya tiene datos. En una instalación
# real (DB_SEED_ON_BOOT=false) no se toca nada.
if [ "$DB_SEED_ON_BOOT" = "true" ] || [ "$DB_SEED_ALWAYS" = "true" ]; then
    echo "==> Sincronizando permisos, roles y cuentas base"
    php artisan db:seed --force
fi

# DemoSeeder sí borra y regenera las tablas de negocio. El comando decide solo:
# siembra únicamente si la base está vacía, y ante cualquier duda no toca nada.
# DB_SEED_ALWAYS=true reinicia la demo pública en cada arranque.
if [ "$DB_SEED_ALWAYS" = "true" ]; then
    php artisan demo:sembrar --forzar
elif [ "$DB_SEED_ON_BOOT" = "true" ]; then
    php artisan demo:sembrar
fi

echo "==> Enlazando storage público"
php artisan storage:link || true

echo "==> Cacheando configuración, rutas y vistas"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# La cola y el programador corren dentro del contenedor web porque el plan
# gratuito de Render no incluye servicios de fondo. En una instalación real
# conviene ponerlos en su propio servicio (ver render.yaml) y apagarlos acá con
# RUN_WORKERS=false, para que un reinicio del web no interrumpa un job.
if [ "${RUN_WORKERS:-true}" = "true" ]; then
    echo "==> Levantando worker de colas y programador de tareas"
    php artisan queue:work --tries=3 --max-time=3600 --sleep=3 &
    php artisan schedule:work &
fi

echo "==> Iniciando servidor en el puerto ${PORT:-8080}"
exec "$@"
