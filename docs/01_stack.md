# 01_stack.md — Stack Técnico del Proyecto

## Nombre del proyecto
Sistema Administrativo Universal para Negocios (adaptable por módulos a distintos clientes:
tiendas, ferreterías, gimnasios, imprentas, talleres, distribuidores, locales de ropa, etc.)

## Backend
- **Framework:** Laravel 13
- **Lenguaje:** PHP 8.3+
- **Base de datos:** PostgreSQL, MySQL/MariaDB o SQLite (el CI prueba los tres). Las
  diferencias de dialecto SQL se resuelven en `App\Support\PeriodoSql`, nunca en los controllers
- **API:** API REST (todo el sistema debe poder operarse vía API, no solo vía Blade)
- **Autenticación API:** Laravel Sanctum (decidido)
- **ORM:** Eloquent (obligatorio — prohibido SQL crudo, ver `04_system_prompt.md`)

## Frontend
- **Motor de plantillas:** Blade
- **CSS:** Tailwind (decidido) + design system propio en `public/css/rhythm.css`

## Principio arquitectónico clave
Separar en dos grandes dominios de datos:

1. **Núcleo / Plataforma:** usuarios, roles, permisos, configuración, auditoría.
2. **Negocio:** productos, clientes, ventas, compras, stock, proveedores.

Esta separación es la que permite reusar el núcleo entre distintos clientes cambiando solo el
dominio de negocio.

## Decisiones explícitamente descartadas (no hacer, al menos no en v1)
- ❌ Multi-tenant / SaaS / Workspaces
- ❌ IA / predicción de ventas
- ❌ Modo offline
- ❌ Constructor visual de automatizaciones
- ❌ Microservicios
- ❌ WebSockets para todo

## Infraestructura
- **Contenedor:** Docker con FrankenPHP (`Dockerfile`)
- **Deploy:** Render vía blueprint (`render.yaml`), con PostgreSQL gestionado
- **Local sin PHP:** `docker-compose.yml`
- **CI:** GitHub Actions, tests contra los tres motores + Pint

## Notas
- Editar este archivo solo si cambia una decisión de stack real (ej: se decide JWT en vez de
  Sanctum, o se elige Tailwind en vez de Bootstrap). No es un archivo que cambie sesión a sesión.
