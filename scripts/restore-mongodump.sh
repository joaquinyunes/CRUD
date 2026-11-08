#!/bin/sh
# Restaura una copia hecha con backup-mongodump.sh. DESTRUYE lo que haya en la base destino
# (--drop). Probar SIEMPRE primero contra otra base: MONGODB_DATABASE=crud_erp_prueba.
#
#   MONGODB_URI="mongodb+srv://..." ./scripts/restore-mongodump.sh backups/crud_erp-2026....archive.gz [base_origen]
set -eu
: "${MONGODB_URI:?Falta MONGODB_URI}"
ARCHIVO="${1:?Indicá el archivo .archive.gz}"
ORIGEN="${2:-crud_erp}"
DESTINO="${MONGODB_DATABASE:-$ORIGEN}"
echo "Se va a reemplazar la base '$DESTINO' con '$ARCHIVO'. Ctrl+C en 5 s para cancelar."
sleep 5
mongorestore --uri="$MONGODB_URI" --gzip --archive="$ARCHIVO" --drop \
  --nsFrom="${ORIGEN}.*" --nsTo="${DESTINO}.*"
echo "OK. Después: php artisan mongo:indices  y entrar al sistema a comprobar un dato."
