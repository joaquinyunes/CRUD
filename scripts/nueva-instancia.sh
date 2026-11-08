#!/bin/sh
# V4 — Instancia nueva para un cliente en menos de 1 hora.
#
#   ./scripts/nueva-instancia.sh <cliente> <email-admin> [--local]
#
# Genera instancias/<cliente>/.env con APP_KEY y una contraseña de administrador fuertes
# (carpeta fuera de git), valida los requisitos y:
#   --local   levanta la instancia con Docker (app + Mongo con replica set) y espera a /up
#   sin flag  imprime las variables para pegar en Render (Blueprint) y los pasos 👤 que quedan
set -eu
CLIENTE="${1:?Uso: nueva-instancia.sh <cliente> <email-admin> [--local]}"
EMAIL="${2:?Falta el email del administrador}"
MODO="${3:-}"
case "$CLIENTE" in *[!a-z0-9-]*|'') echo "El cliente va en minúsculas, números y guiones" >&2; exit 1;; esac
command -v openssl >/dev/null || { echo "Falta openssl" >&2; exit 1; }

DIR="instancias/$CLIENTE"
[ ! -e "$DIR/.env" ] || { echo "Ya existe $DIR/.env: no lo piso." >&2; exit 1; }
mkdir -p "$DIR"
APP_KEY="base64:$(openssl rand -base64 32)"
CLAVE="$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-20)"
umask 077
cat > "$DIR/.env" <<ENV
APP_KEY=$APP_KEY
DB_SEED_ON_BOOT=false
ADMIN_EMAIL=$EMAIL
ADMIN_PASSWORD=$CLAVE
MONGODB_DATABASE=crud_erp_$(echo "$CLIENTE" | tr '-' '_')
ENV
echo "Creado $DIR/.env (APP_KEY y contraseña de administrador generadas; guardalo en el gestor de claves del cliente)."

if [ "$MODO" = "--local" ]; then
  command -v docker >/dev/null || { echo "Falta Docker" >&2; exit 1; }
  docker compose --env-file "$DIR/.env" -p "erp-$CLIENTE" up -d --build
  for i in $(seq 1 60); do
    code=$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8080/up || true)
    [ "$code" = "200" ] && { echo "OK: http://localhost:8080 responde (/up 200). Entrá con $EMAIL."; exit 0; }
    sleep 3
  done
  echo "ERROR: /up no respondió 200 a tiempo. Mirá: docker compose -p erp-$CLIENTE logs app" >&2
  exit 1
fi

cat <<TXT

== Render (👤, ~20 min) ==
1. Atlas: cluster que no se pause, usuario readWrite sobre la base, regla de red (docs/DESPLIEGUE-MONGODB.md).
2. Render > New > Blueprint > repo. Variables a cargar a mano:
     APP_KEY, MONGODB_URI, MONGODB_DATABASE, ADMIN_EMAIL, ADMIN_PASSWORD   (están en $DIR/.env)
     DB_SEED_ON_BOOT=false   FILESYSTEM_DISK=s3 + AWS_*   RUN_SCHEDULER=true (o cron job aparte)
3. Cuando /up dé 200: entrar, activar el doble factor del administrador (obligatorio en producción).
4. Programar scripts/backup-mongodump.sh y hacer UNA restauración de prueba (docs/BACKUPS.md).
5. Cargar la URL en la variable URL_PRODUCCION de GitHub para el aviso de caída (docs/MONITOREO.md).
TXT
