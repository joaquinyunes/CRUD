# Entorno de demo (V3)

Regla: **el entorno real nunca se usa para mostrar** y **la demo nunca apunta a una base de cliente**. Son servicios y bases distintos.

| | Real (`render.yaml`) | Demo (`render.demo.yaml`) |
|---|---|---|
| Servicio Render | `crud-erp` | `crud-erp-demo` |
| Base (`MONGODB_DATABASE`) | `crud_erp` (o `crud_erp_<cliente>`) | `crud_erp_demo` |
| Datos | los del cliente; `DB_SEED_ON_BOOT=false` | de ejemplo (`DemoSeeder`); `DB_SEED_ON_BOOT=true` |
| Cuentas | `ADMIN_EMAIL` / `ADMIN_PASSWORD` (12+), 2FA obligatorio | **conocidas**: `admin@admin.com` / `password` (y los demás roles de `DatabaseSeeder`) |
| Cartel | no | **«DEMO — datos de ejemplo, no son de un negocio real»** en todas las pantallas (`DEMO_MODE=true`) |
| Reinicio | nunca | opcional: `DB_SEED_ALWAYS=true` lo deja limpio en cada arranque |

**Cambio del 09/11/2026:** `render.yaml` traía `DB_SEED_ON_BOOT=true`, es decir, un Blueprint nuevo creaba cuentas con contraseña conocida. Ahora el real arranca en `false` y la demo tiene su propio archivo.

## Cómo se levanta
1. Atlas: crear **otro usuario de base** con `readWrite` **solo** sobre `crud_erp_demo` (así, aunque se filtre, no toca nada real).
2. Render → New → Blueprint → indicar el archivo `render.demo.yaml`. Cargar `APP_KEY` y `MONGODB_URI` (la del usuario de demo).
3. Primer arranque: crea índices y siembra los datos de ejemplo.

## Qué decirle al que mira
Los datos son inventados a propósito y el cartel lo dice. No mostrar capturas de la demo como si fueran de un cliente (las de `docs/screenshots/` y las del manual son de la demo y así están rotuladas).

## Verificado
Tests automáticos (`SeguridadLaravelTest`): el blueprint real no siembra demo, el de demo sí, con base y cartel propios; el cartel aparece solo con `DEMO_MODE`. **No desplegado:** crear el servicio es 👤 (cuentas de Render y Atlas).
