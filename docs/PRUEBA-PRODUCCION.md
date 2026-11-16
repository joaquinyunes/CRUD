# Prueba en producción (E10) — checklist 👤

Se hace **con el cliente** en la instancia real, ya con Atlas, Render y dominio configurados (`docs/DESPLIEGUE-MONGODB.md`).
Fecha: ____ · Instancia: ____ · Quién prueba: ____

## Antes de empezar
- [ ] `curl -i https://<dominio>/up` → 200
- [ ] `DB_SEED_ON_BOOT=false` y `APP_DEBUG=false` (Render → Environment)
- [ ] Entrar con `ADMIN_EMAIL`/`ADMIN_PASSWORD` y **activar el doble factor**; guardar los códigos de recuperación

## Recorrido (anotar ✅/❌ en cada paso y, si falla, captura y hora)
1. [ ] **Login** y cierre de sesión; 5 intentos fallidos bloquean unos minutos.
2. [ ] **Caja:** abrir con un monto inicial (ej. 1.000).
3. [ ] **Venta en efectivo** con la caja abierta (1 producto, monto exacto). El stock baja en 1; el comprobante es correlativo.
4. [ ] **Intento de vender sin stock** → el sistema lo rechaza.
5. [ ] **Devolución** parcial de esa venta: el stock sube y baja el saldo.
6. [ ] **Egreso de caja** (ej. 50 con concepto).
7. [ ] **Cierre de caja** contando el efectivo: esperado = inicial + ingresos − egresos. **Diferencia 0** ✅ / distinta ❌ (anotar el motivo).
8. [ ] **Usuario vendedor** (rol Empleado): hace una venta y **no ve** las del administrador.
9. [ ] **Backup y restauración reales:** *Backup → Crear*; anotar un dato (ej. nombre de un cliente); cambiarlo; *Restaurar*; el dato vuelve. Con `FILESYSTEM_DISK=s3`, repetir tras un *Manual Deploy* y comprobar que el respaldo sigue ahí.
10. [ ] `scripts/backup-mongodump.sh` y `restore-mongodump.sh` contra una base de prueba (`docs/BACKUPS.md`).
11. [ ] **Reinicio:** *Manual Deploy*; los datos siguen y el administrador no se recrea.
12. [ ] **Aviso de caída:** variable `URL_PRODUCCION` cargada y, apagando el servicio unos minutos, llega el mail.

## Cierre
- [ ] Anotar fecha y resultado en `docs/BACKUPS.md` (registro de restauraciones) y en `PLAN-VENTA-10K.md` (E10).
- [ ] Borrar el Postgres vencido `crud-erp-db` de Render **después** de este recorrido (E11).
