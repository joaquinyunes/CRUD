# Monitoreo (V7)

## Qué hay
- **`/up`**: responde 200 solo si MongoDB contesta (`AppServiceProvider`). Lo usan Render (health check) y el aviso de abajo.
- **Aviso de caída**: `.github/workflows/uptime.yml` consulta `/up` cada 10 minutos (3 intentos); si falla, GitHub manda un correo al dueño del repositorio. Se activa cargando la variable **`URL_PRODUCCION`** (Settings → Secrets and variables → Actions → Variables). Sin la variable no hace nada. *Depende de que GitHub Actions funcione: el 08/11/2026 la cuenta figuraba bloqueada por facturación.*
- **Logs**: `LOG_CHANNEL=stderr` → se leen en el panel de Render.

## Qué falta (👤)
1. Una alternativa que no dependa de GitHub: UptimeRobot / Better Stack sobre `https://<dominio>/up` con aviso por mail y WhatsApp. (Verificar el precio vigente en su sitio antes de ofrecerlo; el plan gratuito de estos servicios suele chequear cada 5 min.)
2. **Errores a un panel (Sentry u otro):** no se instaló el SDK porque requiere un DSN de una cuenta del cliente. Cuando exista: `composer require sentry/sentry-laravel`, `php artisan sentry:publish --dsn=…` y cargar `SENTRY_LARAVEL_DSN` en Render.
3. **Render no se duerme**: el plan *Free* apaga el servicio tras 15 min sin tráfico y tarda ~1 minuto en volver, además de perder el disco local (fuente: render.com/docs/free, 08/11/2026). Una instalación vendida va en un plan de pago (**Starter ≈ USD 7/mes**, 512 MB, según render.com/pricing al 08/11/2026) o en un servidor propio. Atlas: M0 es gratis pero **sin backups**; Flex ≈ USD 8–30/mes (mongodb.com/pricing, 08/11/2026).
4. Cargar `URL_PRODUCCION` apenas haya dominio.
