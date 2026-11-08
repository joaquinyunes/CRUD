# Runbook — «qué hago si se cae» (V5)

Para quien da soporte. Orden: **diagnosticar → contener → restaurar → avisar**. Tiempos de respuesta comprometidos: ver `VENTA.md` (caído = 4 h hábiles).

## 1. Triage (2 minutos)
| Chequeo | Cómo | Si falla |
|---|---|---|
| ¿El sitio responde? | `curl -i https://<dominio>/up` | 200 = vivo y con Mongo. 503/500 → paso 2. Sin respuesta → paso 3 |
| ¿Es de la base? | Atlas → *Database → Clusters* (¿pausado? ¿alertas?) y *Network Access* (¿cambió la IP de Render?) | Despertar/restaurar el cluster; revisar la lista de IP |
| ¿Es del último deploy? | Render → *Events* y *Logs* | Rollback al deploy anterior (Render → *Deploys* → *Rollback*) |

## 2. `/up` da 500/503
1. Logs de Render: buscar `MongoDB todavía no responde` o `No suitable servers found`.
2. **`MONGODB_URI` vencida o contraseña rotada** → actualizar la variable en Render y *Manual Deploy*.
3. **IP no permitida** → Atlas *Network Access*: agregar las IP de salida del servicio.
4. **Cluster pausado** (plan gratuito) → reanudar en Atlas; el cliente debe pasar a un plan que no se pause.

## 3. No hay respuesta
- Render *Suspended* por horas del plan Free o por pago → subir a un plan de pago.
- Dominio: revisar DNS (CNAME hacia el servicio) y el certificado en Render.

## 4. Los datos están mal (borrado o import equivocado)
1. **No tocar nada más.** Anotar la hora.
2. *Backup* de la pantalla: crear uno ahora (para no perder lo posterior) y bajar el último de la noche anterior.
3. Restaurar en una base de prueba primero (`docs/BACKUPS.md`), verificar el dato, y recién entonces restaurar en producción **avisando al cliente de que se pierde lo cargado después de la copia**.

## 5. Un administrador perdió el doble factor
`php artisan usuario:quitar-doble-factor correo@cliente.com` (consola de Render → *Shell*; no existe en el plan Free). Verificar la identidad del cliente por otro canal antes. En el próximo ingreso lo vuelve a activar.

## 6. Sospecha de acceso indebido
1. Desactivar el usuario sospechoso (se desconecta en el acto) y cambiar `APP_KEY` solo si hay indicio de filtración (cierra todas las sesiones).
2. Revisar *Auditoría* (no se puede borrar) para ver qué hizo.
3. Rotar `MONGODB_URI`, `ADMIN_PASSWORD` y las claves de S3 si pudieron verse.
4. Avisar al cliente por escrito el mismo día.

## 7. Registro de incidentes
Fecha · quién avisó · síntoma · causa · qué se hizo · cuánto duró. Guardarlo en la carpeta del cliente.
