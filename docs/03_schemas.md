# 03_schemas.md — Foto Actual de la Base de Datos

> Este archivo es una FOTO, no un historial. Cada sesión que modifique la base de datos debe
> reescribir este archivo completo con el estado real, no agregar un changelog al final.

**Última actualización:** Microfase 2.3 completada — tabla `movimientos_stock`, servicio StockService, observers de Venta/Compra.

---

## Dominio Núcleo / Plataforma

### `users`
| Campo | Tipo | Notas |
|---|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| email_verified_at | timestamp nullable | |
| password | string | hash |
| role_id | FK → roles.id nullable | seteado en null al eliminar rol |
| two_factor_enabled | boolean | default false |
| remember_token | string nullable | |
| created_at / updated_at | timestamp | |

### `roles`
| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| nombre | string | Administrador, Supervisor, Empleado, Cliente |

### `permissions`
| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| clave | string | formato `modulo.accion`, ej: `productos.crear` |

### `role_has_permissions` (pivote)
| Campo | Tipo |
|---|---|
| role_id | FK → roles.id |
| permission_id | FK → permissions.id |

### `configuracion`
| Campo | Tipo |
|---|---|
| id | bigint PK |
| nombre_empresa | string |
| logo | string nullable |
| direccion | string nullable |
| telefono | string nullable |
| email | string nullable |
| moneda | string default 'ARS' |
| iva | decimal nullable |

### `auditoria`
| Campo | Tipo |
|---|---|
| id | bigint PK |
| user_id | FK → users.id |
| ip | string |
| accion | string |
| modelo_afectado | string nullable |
| modelo_id | bigint nullable |
| valor_anterior | json nullable |
| valor_nuevo | json nullable |
| created_at | timestamp |

---

## Dominio Negocio

### `categorias`
| Campo | Tipo |
|---|---|
| id | bigint PK |
| nombre | string |
| descripcion | string nullable |
| estado | boolean default true |

### `productos`
| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| codigo | string unique | |
| nombre | string | |
| descripcion | text nullable | |
| categoria_id | FK → categorias.id | |
| marca | string nullable | |
| precio_compra | decimal(10,2) | |
| precio_venta | decimal(10,2) | |
| stock | integer | default 0 — **no se edita directamente, ver `movimientos_stock`** |
| stock_minimo | integer | default 0 |
| imagen | string nullable | |
| estado | enum('activo','inactivo','eliminado') | default 'activo' (eliminado lógico) |

### `clientes`
| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| nombre | string | |
| apellido | string | |
| documento | string nullable | |
| email | string nullable | |
| telefono | string nullable | |
| direccion | string nullable | |
| observaciones | text nullable | |
| estado | enum('activo','archivado','eliminado') | default 'activo' (eliminado lógico) |
| created_at / updated_at | timestamp | |

### `proveedores`
| Campo | Tipo |
|---|---|
| id | bigint PK |
| nombre | string |
| cuit | string nullable |
| telefono | string nullable |
| email | string nullable |
| direccion | string nullable |

### `ventas` (cabecera)
| Campo | Tipo |
|---|---|
| id | bigint PK |
| numero | string unique |
| cliente_id | FK → clientes.id |
| fecha | date |
| total | decimal(10,2) |
| estado | string |
| user_id | FK → users.id |

### `ventas_detalle`
| Campo | Tipo |
|---|---|
| id | bigint PK |
| venta_id | FK → ventas.id |
| producto_id | FK → productos.id |
| cantidad | integer |
| precio | decimal(10,2) |
| subtotal | decimal(10,2) |

### `compras` (cabecera)
| Campo | Tipo |
|---|---|
| id | bigint PK |
| proveedor_id | FK → proveedores.id |
| fecha | date |
| total | decimal(10,2) |
| estado | string |

### `compras_detalle`
| Campo | Tipo |
|---|---|
| id | bigint PK |
| compra_id | FK → compras.id |
| producto_id | FK → productos.id |
| cantidad | integer |
| precio | decimal(10,2) |
| subtotal | decimal(10,2) |

### `movimientos_stock`
| Campo | Tipo | Notas |
|---|---|---|
| id | bigint PK | |
| producto_id | FK → productos.id | |
| tipo | enum('entrada','salida','ajuste','devolucion') | |
| cantidad | integer | |
| user_id | FK → users.id | quién hizo el movimiento |
| referencia_tipo | string nullable | ej: 'venta', 'compra' |
| referencia_id | bigint nullable | |
| created_at | timestamp | |

### `archivos` (Nivel 3)
| Campo | Tipo |
|---|---|
| id | bigint PK |
| nombre | string |
| ruta | string |
| tipo | string |
| tamano | integer |
| relacionado_tipo | string nullable |
| relacionado_id | bigint nullable |

---

## Pendiente de definir (no inventar, decidir antes de implementar)
- Estructura final de `campos_personalizados` (Nivel 4): EAV vs columna JSON.
- Estructura de `estados` configurables (Nivel 4): ¿tabla genérica `estados` + `estado_id` en cada
  módulo, o enum por módulo?
