# Lo que te toca a vos (👤) — crud-erp

Ordenado por lo que desbloquea. Lo marcado ⛔ frena la venta.

## 0. Antes que nada
1. ⛔ **Elegir el rubro** al que se vende (E11). El ERP es genérico y el tribunal pidió una sola frase y un solo comprador. Con el rubro: reescribir `docs/VENTA.md` (frase y tabla de costo contra el sistema de ese rubro, leyendo su precio ese día) y la página `docs/venta/index.html`.
2. ⛔ **GitHub Actions está bloqueado por facturación** («your account is locked due to a billing issue», 08/11/2026). Mientras siga así: ningún job del CI corre (tests, PHPStan, Pint, `composer audit`, escaneo de secretos, **build de la imagen Docker**) y el aviso de caída (`uptime.yml`) tampoco. Resolverlo en github.com → Settings → Billing.
3. ⛔ **Imagen Docker sin construir:** no hay Docker en la máquina de desarrollo y el CI está bloqueado (punto 2). Cuando el CI vuelva, el job «Imagen Docker» la construye y comprueba `/up`; o en cualquier máquina con Docker: `docker build -t crud-erp . && ./scripts/nueva-instancia.sh prueba tu@mail.com --local`.

## 1. Cuentas e infraestructura (E8–E10)
- **E8 Atlas:** cluster que no se pause (el M0 se pausa y no tiene backups), usuario `readWrite` sobre `crud_erp` (idealmente con rol sin `update/remove` sobre `auditoria`), regla de red. Guía: `docs/DESPLIEGUE-MONGODB.md`.
- **E9 Render:** Blueprint desde `render.yaml`; cargar `MONGODB_URI`, `APP_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` (12+), `DB_SEED_ON_BOOT=false`, `FILESYSTEM_DISK=s3` + `AWS_*`. Un plan de pago (el Free se duerme y pierde el disco). `scripts/nueva-instancia.sh` genera las claves.
- **E10 Prueba en producción:** login · venta en efectivo con caja abierta · devolución · cierre con diferencia 0 · **backup y restauración reales** (anotar el resultado en `docs/BACKUPS.md`).
- **E11 / V10:** borrar el Postgres vencido `crud-erp-db` en Render; **conseguir el cliente piloto** (seña o uso real).

## 2. Seguridad
- Activar el 2FA del administrador (obligatorio en producción) y guardar los códigos.
- `security.txt`: decidir el correo de contacto y crear `public/.well-known/security.txt`.
- Correr **gitleaks** una vez sobre el historial (acá se corrió el mismo conjunto de patrones del CI: 0 hallazgos, pero gitleaks no está instalado).
- Cuando haya presupuesto: migrar a Tailwind 4 (cierra las 5 vulnerabilidades solo de build) y sacar los scripts en línea para pasar el `script-src` de la CSP a «bloquear».
- **S12:** política de privacidad y términos con un profesional (Ley 25.326); pantalla/procedimiento de exportar o borrar los datos de un cliente.

## 3. Comercial
- **V1:** validar los precios de `docs/VENTA.md` con un comprador. **Ojo:** a 3 años el sistema sale más caro que las suscripciones comparadas; hay que venderlo por otra razón (ver la «Lectura honesta»).
- **V6:** contrato y licencia con abogado (cesión o licencia, alcance del soporte, qué no incluye).
- **V5:** grabar los 4 videos (`docs/manual/GUIONES-VIDEO.md`).
- **V8:** completar `[..]` en `docs/venta/index.html` (contacto, razón social, caso real) y grabar el video de 20–30 s.
- **V7:** dar de alta el aviso de caída (variable `URL_PRODUCCION` o un servicio de uptime) y, si se quiere, Sentry (`docs/MONITOREO.md`).

## 4. Integraciones (E7, paquete Completo)
- Elegir **proveedor de facturación certificado y contador matriculado**; recién ahí implementar `FacturacionProvider` real (el contrato y el comprobante interno ya están; el sistema nunca escribe un CAE a mano).
- Mercado Pago y WhatsApp: credenciales del cliente; **no están hechos** en este proyecto.
