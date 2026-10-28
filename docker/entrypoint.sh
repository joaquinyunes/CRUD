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

# Sin migraciones: la estructura de MongoDB son sus índices (database/mongo/indices.php).
# Crearlos es idempotente y de paso comprueba que la base responde; si el cluster todavía
# está despertando se reintenta unas veces antes de rendirse.
echo "==> Verificando MongoDB y creando índices"
intentos=0
until php artisan mongo:indices; do
    intentos=$((intentos + 1))
    if [ "$intentos" -ge 10 ]; then
        echo "ERROR: no se pudo conectar a MongoDB (revisá MONGODB_URI y que el cluster sea un replica set)."
        exit 1
    fi
    echo "MongoDB todavía no responde; reintento $intentos/10 en 5 s"
    sleep 5
done

# DatabaseSeeder es idempotente (firstOrCreate + sync): crea permisos, roles y
# cuentas base sin pisar nada. Se corre en cada arranque de la demo para que un
# permiso o un rol nuevo llegue a una base que ya tiene datos. En una instalación
# real (DB_SEED_ON_BOOT=false) no se toca nada.
if [ "$DB_SEED_ON_BOOT" = "true" ] || [ "$DB_SEED_ALWAYS" = "true" ]; then
    echo "==> Sincronizando permisos, roles y cuentas base"
    php artisan db:seed --force
fi

# Instalación real: sin demo no hay permisos, rol ni cuenta alguna, y nadie
# podría entrar. Esto deja el catálogo de permisos al día (y se los da al rol
# Administrador, así un permiso nuevo llega solo) y crea la primera cuenta con
# ADMIN_EMAIL / ADMIN_PASSWORD si todavía no existe. Nunca toca una contraseña
# ni los permisos de los demás roles.
if [ "$DB_SEED_ON_BOOT" != "true" ] && [ "$DB_SEED_ALWAYS" != "true" ]; then
    echo "==> Preparando la instalación (permisos, rol Administrador, primera cuenta)"
    php artisan sistema:instalar --no-interaction
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

# La cola es `sync` (los avisos corren en el mismo pedido), así que no hay worker. Solo el
# programador (presupuestos que vencen, aviso de stock crítico y el backup nocturno) corre
# dentro del contenedor web porque el plan gratuito de Render no incluye servicios de fondo.
# En una instalación real conviene ponerlo en su propio servicio (cron job de Render que
# corra `php artisan schedule:run` cada minuto) y apagarlo acá con RUN_SCHEDULER=false.
if [ "${RUN_SCHEDULER:-true}" = "true" ]; then
    echo "==> Levantando el programador de tareas"
    php artisan schedule:work &
fi

echo "==> Iniciando servidor en el puerto ${PORT:-8080}"
exec "$@"
