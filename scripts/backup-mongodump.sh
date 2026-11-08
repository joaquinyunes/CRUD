#!/bin/sh
# Copia completa de la base con mongodump (S10). Se programa con un cron job de Render
# (o cron del servidor) una vez por noche. Complementa el backup de la aplicación
# (php artisan backup:crear): este sirve aunque la app esté caída.
#
#   MONGODB_URI="mongodb+srv://..." ./scripts/backup-mongodump.sh [carpeta]
#
# Variables: MONGODB_URI (obligatoria), MONGODB_DATABASE (crud_erp), CONSERVAR (14 copias),
# BACKUP_S3_URI (opcional, s3://bucket/ruta/ — requiere aws cli y credenciales en el entorno).
set -eu
: "${MONGODB_URI:?Falta MONGODB_URI}"
DB="${MONGODB_DATABASE:-crud_erp}"
DESTINO="${1:-./backups}"
mkdir -p "$DESTINO"
ARCHIVO="$DESTINO/${DB}-$(date +%Y%m%d-%H%M%S).archive.gz"

mongodump --uri="$MONGODB_URI" --db="$DB" --gzip --archive="$ARCHIVO"
# Un archivo vacío o diminuto es una copia rota: mejor fallar que dormirse tranquilo.
[ "$(wc -c < "$ARCHIVO")" -gt 1024 ] || { echo "ERROR: copia sospechosamente chica: $ARCHIVO" >&2; exit 1; }
echo "OK: $ARCHIVO"

if [ -n "${BACKUP_S3_URI:-}" ]; then aws s3 cp "$ARCHIVO" "$BACKUP_S3_URI"; fi

# shellcheck disable=SC2012
ls -1t "$DESTINO"/"$DB"-*.archive.gz | tail -n +"$(( ${CONSERVAR:-14} + 1 ))" | xargs -r rm --
