# Plan de este proyecto (copia del 02/11/2026)

> Sacado de `soluciones-repos/PLAN-POR-PROYECTO.md` (la maestra, con tablero y reglas de todas las sesiones). Marcá `[x]` acá y en la maestra. Reglas: sin `git push`, sin secretos, credenciales de prueba al final, no tocar `veterinaria-system`.

## 5. crud-erp (ERP Laravel) — código terminado, falta desplegar
Ramas: base `main` `ebf2145` · `mongodb` `44585b3` (fases 0-8) · `chore/seguridad-ci` `0997823` (middleware de encabezados + test). Árbol limpio.

- [ ] 1 🤖 **Integrar** `chore/seguridad-ci` sobre `mongodb`. Verif.: `php artisan test` con `MONGODB_DATABASE=crud_erp_test_x` (≥269 + el test de encabezados).
- [ ] 2 🤖 Re-ejecutar PHPStan y Pint: 0 errores (solo se tiene el informe de la sesión).
- [ ] 3 🤖 Construir la imagen Docker y validar `render.yaml` (la sesión no pudo).
- [ ] 4 🤖 `FacturacionProvider` con un proveedor certificado (WP-38) — recién cuando elijas proveedor.
- [ ] 5 👤 Atlas: replica set, plan que no se pause, usuario `readWrite` sobre `crud_erp`, regla de red.
- [ ] 6 👤 Render → Blueprint con `render.yaml`; `MONGODB_URI`, `APP_KEY`, `DB_SEED_ON_BOOT=false`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` (12+), `FILESYSTEM_DISK=s3` con sus credenciales.
- [ ] 7 👤 Prueba en producción: login · venta en efectivo con caja abierta · devolución · cierre con diferencia 0 · backup y restauración reales.
- [ ] 8 👤 Borrar el Postgres vencido `crud-erp-db`.
- [ ] 9 👤 Elegir proveedor de facturación + contador matriculado.

Salida: ☐ tests/PHPStan/Pint verdes ☐ imagen Docker construye ☐ `/up` responde 200 en Render ☐ la lista de prueba del paso 7 tildada.

Pasos 🤖 = los hace una sesión. 👤 = solo el dueño.
