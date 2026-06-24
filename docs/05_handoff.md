# 05_handoff.md — Estado Actual del Proyecto

> Este archivo es una FOTO del estado actual, no un historial acumulado. Cada sesión lo
> sobreescribe completo. No agregues "Handoff sesión 1.1", "Handoff sesión 1.2", etc. uno debajo
> del otro: siempre hay un único handoff vigente.

---

## Estado Final
- **Microfase completada:** 1.1 Setup + Autenticación
- **Objetivo alcanzado:** Laravel 13.17 instalado con Breeze (Blade + Tailwind + Dark mode).
  MySQL 8.4 configurado y corriendo en localhost:3306. Login, logout, recuperar contraseña
  y verificación de email funcionales. Registro público eliminado (solo admins crean usuarios).
  Migraciones ejecutadas: `users`, `cache`, `jobs`, `roles`. Seeders: 4 roles
  (Administrador, Supervisor, Empleado, Cliente) + usuario admin (admin@sistema.local / admin123).
  User model con relación `belongsTo(Role)`, `role_id` nullable FK y `two_factor_enabled`.
- **Bloqueos/Problemas:** Ninguno. MySQL se inicia manualmente (no como servicio Windows).
  Pendiente: configurar el inicio automático de MySQL o recordar ejecutar `mysqld` antes
  de cada sesión.

## Contexto técnico entregado
- **Decisiones clave:** Tailwind CSS, Laravel Sanctum (para API futura), sin auto-registro público.
  Base de datos: `sistema_administrativo` en MySQL 8.4. Usuario admin seedeado.
- **Archivos tocados:** `.env`, migración `users`, `routes/auth.php`, `app/Models/User.php`,
  `resources/views/auth/register.blade.php` (eliminado). Creados: `app/Models/Role.php`,
  migración `create_roles_table`, `database/seeders/DatabaseSeeder.php`.
- **Última instrucción ejecutada:** `php artisan migrate --force` + `db:seed`.
- **Próximo paso inmediato:** **Microfase 1.2 — Roles y Permisos granulares**
  (migraciones `permissions`/`role_has_permissions`, modelo Permission, middleware de permisos,
  seeder de permisos por módulo, vistas de gestión de roles).


