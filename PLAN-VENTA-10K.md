# crud-erp (ERP Laravel) — plan de venta (actualizado 08/11/2026)
Leé primero `..\PLAN-VENTA-10K.md` (veredicto, paquetes **S** y **V**). Rama de trabajo: `integracion` (= `mongodb` fases 0–8 + `chore/seguridad-ci` + esta tanda). **Es un ERP genérico: no vender sin rubro** (el jurado 3 pidió una sola frase y un solo comprador). Lo que te toca a vos: [`docs/PENDIENTES-USUARIO.md`](docs/PENDIENTES-USUARIO.md).

## Números reales (corridos el 08/11/2026, PHP 8.3.35, MongoDB 8.2.6 replica set local)
| Verificación | Resultado |
|---|---|
| `php artisan test` (`MONGODB_DATABASE=crud_erp_test_x`) | **285 / 285 pasan · 1.055 aserciones · 6 min 8 s** (antes de esta tanda: 269 / 269, 990 aserciones, 12 min 51 s — sobre `mongodb`+`seguridad-ci` ya integradas) |
| `composer audit` | **0 avisos** (antes: 8 avisos en 5 paquetes, symfony; subidos a 7.4.23) |
| `npm audit --omit=dev` | **0** (el `npm audit` completo marca 5, solo de build: Tailwind 3) |
| PHPStan (nivel 5, Larastan) | **0 errores** (en nivel 6 hay 145, todos de tipos genéricos/`array` sin documentar: no se subió) |
| Pint | **pasa** |
| `npm run build` (Vite) | **ok** |
| App en modo producción (`APP_ENV=production`, `APP_DEBUG=false`) contra Mongo local | `/up` 200 · `/login` 200 con encabezados · cookies `Secure; HttpOnly; SameSite=Lax` · 404 sin trazas · `/dashboard` sin sesión → 302 |
| Restauración de backup (Mongo real local) | dato cambiado → restaurado = original ✅ (`docs/BACKUPS.md`) |
| Historial de git, patrones de secretos del CI | 110 commits, **0 coincidencias** (gitleaks no instalado) |
| **Imagen Docker** | ❌ **no construida**: no hay Docker en esta máquina y GitHub Actions está bloqueado por facturación |
| CI de GitHub | ❌ no corrió (cuenta bloqueada por facturación, 08/11/2026) |

## A. Pasos
- [x] **E1** 🤖 `chore/seguridad-ci` integrada sobre `mongodb` en la rama `integracion` (avance directo, sin conflictos). Tests: 269/269 en esa base; ahora 285/285.
- [x] **E2** 🤖 `composer audit` 8 → 0; tests después: pasan.
- [x] **E3** 🤖 PHPStan 0 errores (nivel 5) y Pint verde.
- [ ] **E4** 🤖 **Parcial.** Hecho: `render.yaml` válido (1 servicio, sin bloque `databases`, `/up` como health check, `APP_DEBUG=false`), `npm run build`, la app en modo producción responde `/up` 200, job «Docker» agregado al CI. **No hecho:** `docker build` y `docker run` — sin Docker en la máquina y CI bloqueado. Queda como 👤 hasta que el job corra en verde.
- [x] **E5** 🤖 Seguridad Laravel: ver [`docs/SEGURIDAD.md`](docs/SEGURIDAD.md). Nuevo en esta tanda: vendedor ve solo lo suyo (web, API y PDF), 2FA obligatorio para el administrador en producción (también en la API), auditoría de solo agregar, CORS cerrado, contraseña ≥ 12 en todo el sistema, cookie `Secure` por defecto en producción, CSP parcialmente aplicada, Dependabot, rescate `usuario:quitar-doble-factor`. 16 tests nuevos en total (13 de seguridad). **Queda:** CSP de `script-src` en «bloquear» y sesión en servidor (S2, S4), `security.txt`, S12 (👤).
- [x] **E6** 🤖 Concurrencia y caja: ya existían ventas simultáneas sin repetir número y sin stock negativo (6 procesos × 5 sobre 20 de stock → 20 ventas y 10 rechazos); se agregaron **el último ítem lo vende uno solo** (6 procesos sobre stock 1) y **cierre de caja con diferencia 0**.
- [x] **E7** 🤖 `FacturacionProvider` (contrato + `FacturacionInterna`: comprobante sin CAE y sin validez fiscal). El proveedor real se enchufa cuando elijas proveedor y contador (👤); el sistema nunca escribe un CAE a mano.
- [ ] 👤 **E8** Atlas · [ ] 👤 **E9** Render · [ ] 👤 **E10** prueba en producción · [ ] 👤 **E11** borrar `crud-erp-db`, proveedor + contador, **elegir rubro**.

## B. Para vender
- [x] **V1** paquetes con precio (propuesta a validar con un comprador) — `docs/VENTA.md`
- [x] **V2** frase + tabla a 3 años con precios leídos el 06–08/11/2026 y fuente — `docs/VENTA.md`. **Conclusión incómoda: a 3 años sale 1,2–5,1 veces más caro que la suscripción comparada; hay que vender por otra razón que el precio.** La comparación con el sistema del rubro queda para cuando haya rubro.
- [ ] V3 demo aparte (no asignado en esta tanda)
- [ ] **V4** 🤖 `scripts/nueva-instancia.sh` (genera claves, valida, levanta con Docker `--local` o imprime los pasos de Render) — probado el modo sin Docker (genera `.env`, rechaza nombres inválidos); **el modo `--local` no se probó (sin Docker)**. «Probado de cero en < 1 hora» queda 👤.
- [ ] **V5** 🤖 **Parcial.** Hecho: manual del dueño en PDF (8 páginas, `docs/manual/manual-dueno.pdf`), runbook, checklist de traspaso, guiones de video. **Falta:** grabar los 4 videos (👤).
- [ ] V6 contrato y licencia (👤, abogado)
- [x] **V7** 🤖 `/up` + workflow `uptime.yml` (activo con la variable `URL_PRODUCCION`) + `docs/MONITOREO.md`. Falta cargar la URL, un uptime que no dependa de GitHub y Sentry (👤: requieren cuentas/DSN).
- [x] **V8** 🤖 `docs/venta/index.html` (problema → qué hace → capturas → precio → caso real → contacto). Con marcas rojas `[..]` para completar (contacto, razón social, caso real, video): 👤.
- [ ] V10 piloto (👤)

## Salida
☑ tests/PHPStan/Pint verdes ☑ `composer audit` limpio ☐ imagen Docker construye ☐ `/up` 200 en Render ☐ lista de prueba E10 tildada ☐ backup restaurado **en Atlas/Render** (sí en Mongo local)
