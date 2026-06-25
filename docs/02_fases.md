# 02_fases.md — Mapa de Niveles y Microfases

Cada microfase está pensada para tocar **máximo 5 archivos de código** en una sola sesión
(migración, modelo, controlador, rutas/policy, vista — o la combinación que aplique). Marcá el
estado a mano: `[ ]` pendiente, `[~]` en curso, `[x]` terminada.

---

## NIVEL 1 — Fundaciones (núcleo de plataforma)

- [x] **1.1 Setup + Autenticación**
  Login, logout, recuperar contraseña, verificación de email.
  Archivos típicos: migración `users`, `AuthController`, rutas `auth.php`, vistas login/registro, `.env` config mail.

- [x] **1.2 Roles y Permisos granulares**
  Roles (Administrador, Supervisor, Empleado, Cliente). Permisos del tipo `modulo.accion`
  (ej: `productos.crear`, `productos.editar`), no permisos genéricos.
  Archivos típicos: migraciones `roles`/`permissions`, modelos, middleware de permisos, seeder.

- [ ] **1.3 Dashboard base + Layout**
  Layout general (sidebar, navbar), dashboard con métricas de "hoy" (ventas, ganancias,
  clientes nuevos) y métricas generales (productos, usuarios, stock crítico).
  Archivos típicos: `DashboardController`, layout Blade, vista dashboard, rutas, CSS base.

- [ ] **1.4 Productos + Categorías**
  CRUD de productos (código, nombre, descripción, categoría, marca, precio compra/venta, stock,
  stock mínimo, imagen, estado) con eliminado lógico, buscar/filtrar, duplicar producto.
  Archivos típicos: migraciones `productos`/`categorias`, modelos, `ProductoController`, vistas.

- [ ] **1.5 Clientes**
  CRUD de clientes (nombre, apellido, documento, email, teléfono, dirección, observaciones).
  Archivos típicos: migración `clientes`, modelo, `ClienteController`, vistas.

- [ ] **1.6 Auditoría**
  Registro de logs de seguridad y actividad: IP, usuario, fecha, acción. Base para el historial
  de actividad ("Juan creó venta #145").
  Archivos típicos: migración `auditoria`, modelo `Auditoria`, trait/observer de logging, vista de consulta.

---

## NIVEL 2 — Operación del negocio

- [ ] **2.1 Ventas**
  Cabecera (número, cliente, fecha, total, estado, usuario) + detalle (producto, cantidad,
  precio, subtotal).
  Archivos típicos: migraciones `ventas`/`ventas_detalle`, modelos, `VentaController`, vista.

- [ ] **2.2 Compras + Proveedores**
  Proveedores (nombre, CUIT, teléfono, email, dirección) y compras (proveedor, fecha, total,
  estado) con su detalle.
  Archivos típicos: migraciones `proveedores`/`compras`/`compras_detalle`, modelos, controladores, vistas.

- [ ] **2.3 Stock (movimientos_stock)**
  Nunca modificar stock directamente: todo pasa por `movimientos_stock` (entrada, salida,
  ajuste, devolución), disparado por ventas/compras.
  Archivos típicos: migración `movimientos_stock`, modelo, servicio `StockService`, listener/observer.

- [ ] **2.4 Reportes**
  Ventas por día/semana/mes, productos más/menos vendidos, mejores clientes, stock
  crítico/agotado.
  Archivos típicos: `ReporteController`, queries/consultas, vistas, rutas.

- [ ] **2.5 Exportación**
  Exportar a Excel, CSV, PDF desde los módulos de reportes/productos/ventas.
  Archivos típicos: clases `Export` (Laravel Excel), `PdfController` o similar, botones en vistas.

---

## NIVEL 3 — Extensiones

- [ ] **3.1 Sistema de Archivos**
  Subida de JPG/PNG/PDF, guardando nombre, ruta, tipo, tamaño.
  Archivos típicos: migración `archivos`, modelo, `ArchivoController`, componente de upload.

- [ ] **3.2 Notificaciones**
  Centro de notificaciones: stock bajo, venta realizada, nuevo cliente, nuevo pedido, nueva compra.
  Archivos típicos: migración `notificaciones`, modelo, listener/evento, vista de campana.

- [ ] **3.3 Tareas**
  Tareas asignables a usuarios ("llamar proveedor", "revisar stock").
  Archivos típicos: migración `tareas`, modelo, `TareaController`, vista kanban/lista.

- [ ] **3.4 Calendario**
  Entregas, reuniones, reparaciones, reservas.
  Archivos típicos: migración `eventos`, modelo, `CalendarioController`, vista (FullCalendar u otro).

- [ ] **3.5 API completa**
  Endpoints REST para todos los módulos previos (`GET/POST/PUT/DELETE /productos`, etc.),
  con JWT o Sanctum (definido en `01_stack.md`).
  Archivos típicos: controladores API (`Api/ProductoController`, etc.), `routes/api.php`, resources/transformers.

---

## NIVEL 4 — Avanzado / Adaptabilidad multi-cliente

- [ ] **4.1 Campos personalizados**
  Permitir agregar campos por entidad sin tocar la base de datos (ej: "material/color" para una
  imprenta, "patente/kilometraje" para un taller) — vía tabla EAV o JSON column.
  Archivos típicos: migración `campos_personalizados`, modelo, trait `TieneCamposPersonalizados`, vista dinámica.

- [ ] **4.2 Estados configurables**
  Estados de pedido/reparación configurables desde panel, no hardcodeados.
  Archivos típicos: migración `estados`, modelo, seeder por tipo de negocio, selector en vistas.

- [ ] **4.3 Importación Excel**
  Importar productos/clientes/proveedores desde `.xlsx`.
  Archivos típicos: clase `Import` (Laravel Excel), `ImportController`, vista de carga, validaciones.

- [ ] **4.4 Backups**
  Botón "Generar Backup" → descarga base de datos + archivos.
  Archivos típicos: comando Artisan `backup:run`, `BackupController`, vista, configuración de `spatie/laravel-backup`.

- [ ] **4.5 Versionado**
  Historial de cambios de campos sensibles (ej: precio) con posibilidad de volver atrás.
  Archivos típicos: migración `versiones` o uso de `spatie/laravel-activitylog`, trait, vista de historial.

---

## Estado general del proyecto
*(Actualizar esta línea cada sesión, o dejar que se actualice solo desde `05_handoff.md`)*

**Última microfase completada:** 1.2 — Roles y Permisos granulares (completa).
**Microfase en curso:** 1.3 — Dashboard base + Layout.
