# Backups y restauración (S10)

Dos capas, para que una no dependa de la otra:

| Capa | Qué es | Cuándo | Dónde queda |
|---|---|---|---|
| **A. Aplicación** | `php artisan backup:crear` (JSON comprimido de toda la base) | Todas las noches 03:00, se conservan 14 (`routes/console.php`) | `FILESYSTEM_DISK` — **con `s3` sobrevive a un deploy; con `local` se borra** |
| **B. Base** | `scripts/backup-mongodump.sh` (`mongodump --gzip`) | Una vez por noche desde un cron job (Render Cron Job con imagen que traiga `mongodump`, o cron de un servidor) | Carpeta o `BACKUP_S3_URI` |

La capa B sirve aunque la aplicación esté caída. En Atlas de pago también se pueden activar los backups propios de Atlas (Cloud Backup): es una tercera capa y se factura aparte.

## Restaurar
- **Desde la pantalla** *Backup → Restaurar*: reemplaza todo y es todo-o-nada (si el archivo está dañado a mitad, la base queda como estaba — hay un test).
- **Desde la base:** `MONGODB_DATABASE=crud_erp_prueba ./scripts/restore-mongodump.sh backups/crud_erp-AAAAMMDD-HHMMSS.archive.gz` y después `php artisan mongo:indices`. Probar **siempre** primero contra otra base.
- Un respaldo de la versión SQL anterior **no** se restaura (el sistema lo rechaza a propósito).

## Registro de pruebas de restauración
| Fecha | Dónde | Qué se hizo | Resultado |
|---|---|---|---|
| 08/11/2026 | MongoDB 8.2.6 local, replica set de un nodo, `APP_ENV=production` para el alta | `backup:crear` → cambiar el nombre del usuario administrador a «DATO-CAMBIADO» → `BackupService::restaurar()` del respaldo hecho antes | Volvió «DATO-ORIGINAL» ✅ |
| _pendiente 👤_ | Atlas del cliente / Render | Hacer un respaldo desde la pantalla, cambiar un dato, restaurar, comprobar | — |
| _pendiente 👤_ | Atlas del cliente | `backup-mongodump.sh` y `restore-mongodump.sh` contra una base de prueba (acá **no se corrió**: `mongodump` no está instalado en la máquina de desarrollo) | — |

> No se vende sin la segunda y la tercera fila completas en la instancia real del cliente.

## Atlas: usuarios con el mínimo privilegio
- Usuario de la **app**: `readWrite` sobre `crud_erp`. Idealmente con un rol propio que sobre la colección `auditoria` tenga solo `find` e `insert` (la auditoría no se reescribe).
- Usuario de **backup**: rol `backup` (solo lectura) — no el de la app.
