> **Actualización 08/11/2026:** el estado vigente está en `PLAN-VENTA-10K.md` (rama `integracion`: 285/285 tests, `composer audit` 0, PHPStan/Pint verdes) y los pendientes del dueño en `docs/PENDIENTES-USUARIO.md`. Lo de abajo es el estado del 31/10.

> Archivo vivo: qué se hizo y qué falta en este proyecto. Misma base que `soluciones-repos/ESTADO-PROYECTOS/crud-erp.md` (31/10/2026). Cada sesión lo actualiza al terminar.

# crud-erp (ERP Laravel) — rama `mongodb` · `44585b3`

## Hecho
- Port a **MongoDB fases 0-8** (`PLAN-CRUD-MONGODB.md`). El auditor del 28/10 re-ejecutó **269 de 269 tests (987 aserciones)**. Las sesiones informaron PHPStan y Pint en 0 errores (no re-ejecutados).
- `render.yaml`, `docs/DESPLIEGUE-MONGODB.md` (guía con los pasos 👤), CI propio.
- Árbol limpio, todo commiteado.

## Falta (código)
- Construir la imagen Docker y validar el YAML: la sesión no pudo.
- `FacturacionProvider` con un **proveedor certificado** de ARCA (WP-38). Nunca escribir CAE a mano.
- Fase 9 (datos viejos): **cerrada por decisión**, arranca de cero.

## Te toca a vos
1. Atlas: cluster con replica set, plan que no se pause, usuario `readWrite` sobre `crud_erp`, regla de red.
2. Render → Blueprint desde `render.yaml`; cargar `MONGODB_URI` y `APP_KEY` (`php artisan key:generate --show`). Para instalación real: `DB_SEED_ON_BOOT=false`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` (12+), `FILESYSTEM_DISK=s3` con credenciales.
3. Comprobar en producción: login, venta en efectivo con caja abierta, devolución, cierre con diferencia 0, **backup y restauración reales**.
4. Borrar el Postgres vencido `crud-erp-db` después de verificar.
5. Contador matriculado + elección del proveedor de facturación.

## Plataformas hoy
Render `crud-erp` (docker, plan gratis) sin respuesta: base vencida. Vercel: no tiene (no aplica, es Laravel).
