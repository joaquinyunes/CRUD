# Contexto completo — hosting gratis (Render/Vercel) + migración a MongoDB

Documento de traspaso. Sirve para retomar el trabajo en otra sesión (con Claude
o con quien sea) sin releer todo el chat. Rama: `feat/pos-retail`.

## 1. Pedido original y decisión de arquitectura

Se pidió poder hostear este POS (Laravel 13 / PHP 8.3, ~41 modelos, Blade
renderizado en servidor, sin API SPA) gratis en **Vercel + Render**, con
**MongoDB** como base de datos permanente.

Decisión, explicada y confirmada en el chat:

- **Vercel no puede hostear esto tal cual.** No ejecuta PHP nativo (solo un
  runtime comunitario, `vercel-php`, sin cola, sin cron, sin storage
  persistente). Esta app es Blade server-rendered, no una API + SPA. Meter
  Vercel de verdad requiere una Fase 3 (ver más abajo): separar en API Laravel
  + frontend nuevo. Hasta esa fase, **Vercel queda afuera del deploy real**.
- **Render sí sirve** para correr la app completa (nginx + php-fpm + cola +
  scheduler en un contenedor Docker), plan free.
- **MongoDB sí se está migrando de verdad**, no es una promesa: hay un módulo
  completo (categorías + unidades de medida) migrado, probado contra un Mongo
  real, con 91/94 tests en verde. El resto de los modelos sigue en SQL y se
  migra por fases porque tocar todo de una implicaría reescribir relaciones,
  transacciones (stock, caja) y queries con JOIN sin poder probarlas — alto
  riesgo de romper el POS en producción.

## 2. Fase 1 — Deploy gratis (Render), sin Mongo — HECHA

Arquitectura: Render (Web Service, Docker, plan free) → nginx + php-fpm +
`queue:work` + `schedule:work` en un solo contenedor (supervisord, porque el
plan free no da workers ni cron aparte). DB relacional en **Neon** (Postgres
free perpetuo). Uploads en **Cloudflare R2** (el disco de Render es efímero).

Archivos creados:

- [`Dockerfile`](../Dockerfile) — build multi-stage (Vite + Composer) sobre
  `php:8.3-fpm-alpine`. Incluye `pdo_pgsql`, `pdo_mysql`, `mongodb` (pecl),
  `redis`, `gd`, `zip`, `intl`, `opcache`, `pcntl`.
- [`docker/nginx.conf`](../docker/nginx.conf), [`docker/supervisord.conf`](../docker/supervisord.conf),
  [`docker/php.ini`](../docker/php.ini), [`docker/entrypoint.sh`](../docker/entrypoint.sh)
  (cachea config/rutas/vistas, corre `migrate --force`, resuelve `$PORT` dinámico).
- [`.dockerignore`](../.dockerignore)
- [`render.yaml`](../render.yaml) — blueprint del Web Service. Variables con
  `sync: false` hay que completarlas a mano en el dashboard de Render (o yo
  las puedo cargar por MCP, ver sección 6).
- `composer require league/flysystem-aws-s3-v3` (driver S3 para R2).

Pasos de deploy están en [`docs/HOSTING.md`](HOSTING.md) sección Fase 1.

## 3. Fase 2 — Migración a MongoDB — EN CURSO

**Actualización (ronda 2):** Cliente y Proveedor también migrados y probados
contra Mongo real (91/94 tests). Se encontraron y corrigieron dos bugs reales
del paquete `mongodb/laravel-mongodb` — detalle completo en
[`docs/HOSTING.md`](HOSTING.md#dos-bugs-reales-de-fondo-encontrados-y-corregidos):
(1) `HybridRelations::hasMany()`/`belongsTo()` Mongo→SQL copia mal la conexión
al modelo relacionado y solo falla dentro de un request HTTP real; (2) la
propiedad correcta para el nombre de colección es `$table`, no `$collection`
— esta última se ignora en silencio y Eloquent cae al plural en inglés
(`proveedors` en vez de `proveedores`). Ninguno de los dos se veía sin correr
contra un Mongo real, por eso vale la pena seguir haciéndolo en cada módulo.

### 3.1 Qué está migrado y verificado

**Categoria** y **UnidadMedida** son ahora modelos Mongo
(`MongoDB\Laravel\Eloquent\Model`), colecciones `categorias` /
`unidades_medida`. **Producto** y **Promocion** siguen en SQL pero usan
`MongoDB\Laravel\Eloquent\HybridRelations` para que sus `belongsTo(Categoria::class)`
/ `belongsTo(UnidadMedida::class)` (incluido `->with('categoria')`, eager
loading) funcionen cruzando SQL → Mongo. Esto es soporte oficial del paquete
`mongodb/laravel-mongodb` (5.11), no una técnica improvisada.

Cambios concretos:

| Archivo | Cambio |
|---|---|
| [`app/Models/Categoria.php`](../app/Models/Categoria.php) | Modelo Mongo, colección `categorias` |
| [`app/Models/UnidadMedida.php`](../app/Models/UnidadMedida.php) | Modelo Mongo, colección `unidades_medida`, `HybridRelations` para `productos()` (Mongo→SQL) |
| [`app/Models/Producto.php`](../app/Models/Producto.php) | `use HybridRelations` |
| [`app/Models/Promocion.php`](../app/Models/Promocion.php) | `use HybridRelations` |
| [`config/database.php`](../config/database.php) | Conexión `mongodb` (dsn `MONGODB_URI`, db `MONGODB_DATABASE`) |
| [`database/migrations/2026_09_14_000001_widen_auditoria_modelo_id.php`](../database/migrations/2026_09_14_000001_widen_auditoria_modelo_id.php) | `auditoria.modelo_id` de `bigint` a `string(64)` (ahora guarda ObjectId además de ids SQL) |
| [`database/migrations/2026_09_14_000002_indices_catalogo_mongo.php`](../database/migrations/2026_09_14_000002_indices_catalogo_mongo.php) | Índices Mongo (`nombre` único, `estado`) en ambas colecciones |
| [`database/migrations/2026_09_14_000003_mongo_ids_en_productos.php`](../database/migrations/2026_09_14_000003_mongo_ids_en_productos.php) | `productos.categoria_id`, `productos.unidad_medida_id`, `promociones.categoria_id`: de `bigint` con FK a `string(24)` sin FK |
| [`app/Console/Commands/MigrarCatalogoAMongo.php`](../app/Console/Commands/MigrarCatalogoAMongo.php) | `php artisan catalogo:migrar-a-mongo [--dry-run]` — copia filas SQL → Mongo, reescribe las FK dependientes. Idempotente (usa `firstOrCreate` por nombre) |
| `app/Http/Controllers/{ProductoController,Api/ProductoController,PromocionController,RecuentoController}.php` | `exists:categorias,id` → `exists:mongodb.categorias,_id` (Laravel soporta `exists:conexion.tabla,columna`) |
| `app/Http/Controllers/ImportController.php` | `resolveCategoria()` devolvía `?int`, ahora `?string` (el id es un ObjectId) |
| [`app/Services/PrecioService.php`](../app/Services/PrecioService.php) | **Bug real corregido:** comparaba `(int) $promo->categoria_id === (int) $producto->categoria_id` — con ObjectId eso da `0 === 0` siempre verdadero (falso positivo silencioso). Ahora compara como string |
| [`app/Http/Controllers/ReporteController.php`](../app/Http/Controllers/ReporteController.php) | `ganancias()` hacía `leftJoin('categorias', ...)` — imposible contra Mongo. Se separó: trae productos por SQL, resuelve nombre de categoría con `Categoria::whereIn('_id', ...)->pluck('nombre','_id')` |
| [`tests/TestCase.php`](../tests/TestCase.php) | `setUp()` trunca `categorias`/`unidades_medida` en Mongo antes de cada test (RefreshDatabase no toca Mongo) |
| [`phpunit.xml`](../phpunit.xml) | `MONGODB_DATABASE=pos_test`, separada de la de desarrollo |
| [`.env.example`](../.env.example) | `MONGODB_URI`, `MONGODB_DATABASE` |
| `composer.json`/`composer.lock` | `mongodb/laravel-mongodb` ^5.11 |

### 3.2 Cómo se verificó (no es teórico)

1. Se instaló `ext-mongodb` (PECL 2.5.2, build `8.3-ts-vs16-x64`) en el PHP
   local de Windows (`C:\php\ext\php_mongodb.dll`, habilitado en `php.ini`).
2. Se descargó y levantó un `mongod` 7.0.14 standalone en
   `127.0.0.1:27017` (datos en `%TEMP%\mongo\data`) — **es el que usa
   `MONGODB_URI` por default en `.env.example`**.
3. Se corrieron migraciones (`php artisan migrate`) contra ese Mongo real →
   índices creados.
4. Se corrió `php artisan catalogo:migrar-a-mongo` contra los datos reales de
   dev (seeders `DemoSeeder`/`UniversalSeeder`) → 10 categorías + 15 unidades
   copiadas, 26 productos y 0 promociones con `categoria_id` reescrito, 24
   productos con `unidad_medida_id` reescrito.
5. Suite completa: **91/94 tests pasan.** Las 3 fallas (`RegistrationTest`
   x2, `ExampleTest` x1) son preexistentes en la rama — se confirmó
   corriéndolas también con `git stash` (sin estos cambios) y fallan igual.

**El `mongod` local queda corriendo** en la máquina para seguir desarrollando
(no es para producción). Si se reinicia la PC hay que volver a levantarlo:

```bash
"<ruta_temp>/mongo/mongodb-win32-x86_64-windows-7.0.14/bin/mongod.exe" \
  --dbpath "%TEMP%\mongo\data" --logpath "%TEMP%\mongo\logs\mongod.log" \
  --port 27017 --bind_ip 127.0.0.1
```

(o instalar MongoDB Community normal como servicio de Windows, es más prolijo
para el día a día).

### 3.3 Qué falta en Fase 2

Categoria/UnidadMedida eran el caso más simple: sin transacciones
multi-documento, sin datos embebidos, sin `belongsToMany`/pivots propios.
Falta migrar (orden sugerido, cada uno es su propia sub-fase con tests en
verde antes de seguir):

1. **Clientes / Proveedores** — probablemente sin complicaciones grandes.
2. **Stock / depósitos / lotes** — tiene `belongsToMany` con pivot
   (`stock_deposito`) y queries `DB::table()` crudas; hay que decidir si el
   pivot se modela embebido o como colección aparte.
3. **Ventas / compras / caja** — tiene los `DB::transaction` (13 en
   total) que dependen de `StockDocumentoService` (ver memoria del proyecto:
   ventas/compras aplican stock explícito, con anulación en vez de borrado).
   Mongo necesita **replica set** para transacciones multi-documento — Atlas
   M0 ya lo da; un `mongod` standalone local **no**. Para probar esta fase
   hace falta Atlas real o iniciar un replica set local de un solo nodo.
4. **Cuenta corriente / devoluciones / presupuestos / OC / reportes** —
   última, porque son los que más `join`/`DB::raw` tienen.

## 4. Fase 3 — Vercel — NO EMPEZADA

Requiere partir la app en API Laravel (queda en Render) + frontend nuevo
(Next.js/Vite) en Vercel, portando ~40 vistas Blade a componentes y
rehaciendo el service worker offline del POS del lado del cliente nuevo. Es
un frontend completo desde cero, no una config de deploy. No se tocó nada de
esto todavía.

## 5. Qué necesito de vos para seguir / para el deploy real

- **MongoDB Atlas M0** (free perpetuo, con replica set): crear el cluster en
  https://mongodb.com/try — **no puedo crearte la cuenta yo**. Con el
  connection string (`mongodb+srv://...`) seteado en `MONGODB_URI` puedo
  seguir la Fase 2 (stock/ventas) y probarla de verdad en vez de con el
  `mongod` local.
- El **MCP de MongoDB Atlas ya está conectado** a esta sesión pero
  **deshabilitado a nivel organización** — un Organization Owner tiene que
  habilitar "AI client access" en la configuración de Atlas si querés que yo
  liste/inspeccione tus clusters directamente en vez de que me pases el
  connection string a mano.
- **Neon** (Postgres, Fase 1): connection string para `DB_URL`.
- **Cloudflare R2** (Fase 1): Access Key, Secret, bucket, endpoint.
- Decisión: ¿seguimos Fase 2 módulo por módulo, o priorizamos cerrar el
  deploy de Fase 1 en Render ya mismo con lo que hay (SQL + el catálogo en
  Mongo, que ya funciona)?

## 6. Accesos ya conectados en esta sesión

- **Render MCP**: workspace `My Workspace` (`joaquinyunesoficial@gmail.com`)
  conectado — puedo crear el Web Service directo (`create_web_service`) en
  vez de que lo hagas manual en la UI, en cuanto tengas las env vars reales.
- **MongoDB Atlas MCP**: conectado pero sin acceso (ver punto 5).

## 7. Archivos de referencia

- [`docs/HOSTING.md`](HOSTING.md) — runbook paso a paso de deploy (Fase 1) y
  checklist técnico de Fase 2/3.
- Este archivo (`docs/CONTEXTO_HOSTING_MONGO.md`) — contexto narrativo y
  estado, para retomar sin releer el chat.
