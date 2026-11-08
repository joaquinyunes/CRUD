# Checklist de traspaso al cliente (V5)

Marcar con fecha y firma de quien entrega y de quien recibe. **Nada se entrega con cuentas a nombre del vendedor.**

## Cuentas (a nombre del cliente, con su correo y su pago)
- [ ] GitHub: el repositorio es del cliente (o una copia) y tiene acceso de administrador.
- [ ] Render: workspace/servicio a nombre del cliente (o invitación como colaborador del vendedor mientras dure el Mensual).
- [ ] MongoDB Atlas: organización y proyecto del cliente; usuario de la app (`readWrite` sobre `crud_erp`) y usuario de backup distintos.
- [ ] Dominio: titular el cliente; DNS apunta al servicio; certificado activo.
- [ ] Bucket S3/R2/B2 privado para adjuntos y backups, claves de la app solo para ese bucket.
- [ ] Proveedor de facturación y contador (cuando se elijan): cuentas del cliente.
- [ ] Gestor de claves del cliente con: `ADMIN_PASSWORD` inicial (ya cambiada), `APP_KEY`, `MONGODB_URI`, códigos de recuperación del 2FA.

## Sistema
- [ ] `/up` responde 200 en el dominio final.
- [ ] El administrador activó el **doble factor** y guardó los códigos de recuperación.
- [ ] `DB_SEED_ON_BOOT=false` (sin datos de demostración en el entorno real).
- [ ] Datos reales cargados y revisados por el cliente (productos, clientes, proveedores, saldos iniciales).
- [ ] Prueba E10 hecha **en producción** con el cliente: login · venta en efectivo con caja abierta · devolución · cierre con diferencia 0.
- [ ] **Backup y restauración reales** (`docs/BACKUPS.md`): fecha ____ resultado ____
- [ ] Aviso de caída activo (`URL_PRODUCCION` o servicio de uptime) y el mail de aviso es el del cliente.
- [ ] Usuarios y roles creados para cada empleado; ninguno comparte contraseña.

## Documentación entregada
- [ ] Manual del dueño (PDF) · [ ] Runbook · [ ] `docs/BACKUPS.md` · [ ] `docs/SEGURIDAD.md`
- [ ] Contrato de licencia/mantenimiento firmado (V6) · [ ] Política de privacidad y términos (S12)

## Capacitación
- [ ] Sesión realizada (fecha ____, asistentes ____) · [ ] El dueño hizo solo: una venta, una devolución, cierre de caja y una copia de seguridad.

Entrega: ____/____/____   Firma entrega: ____________   Firma recibe: ____________
