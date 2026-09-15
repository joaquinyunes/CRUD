# Hosting gratis — POS (Laravel 13)

## Estado

| Fase | Qué | Estado |
|------|-----|--------|
| 1 | Deploy gratis en Render + Postgres Neon + storage R2 | **Hecho** |
| 2 | Migración de Eloquent/SQL a MongoDB | **En curso** — catálogo (categorías + unidades de medida) migrado y probado; el resto de los módulos sigue en SQL |
| 3 | Split API + SPA para poder usar Vercel | Pendiente — cambio de arquitectura |

### Fase 2 — progreso real

**Migrado y verificado contra un Mongo real (local, `mongod` 7.0):**

- `Categoria` y `UnidadMedida` → `MongoDB\Laravel\Eloquent\Model`, colecciones `categorias` / `unidades_medida`, con índice único en `nombre` ([2026_09_14_000002_indices_catalogo_mongo.php](../database/migrations/2026_09_14_000002_indices_catalogo_mongo.php)).
- `Producto` y `Promocion` (siguen en SQL) usan `MongoDB\Laravel\Eloquent\HybridRelations` para que `belongsTo(Categoria::class)` / `belongsTo(UnidadMedida::class)` crucen de SQL a Mongo — incluye `with('categoria')` (eager load).
- `productos.categoria_id`, `productos.unidad_medida_id`, `promociones.categoria_id` pasaron de `bigint` con FK a `string(24)` (ObjectId) sin FK — [2026_09_14_000003_mongo_ids_en_productos.php](../database/migrations/2026_09_14_000003_mongo_ids_en_productos.php).
- `auditoria.modelo_id` ensanchada a `string` porque ahora puede guardar tanto ids SQL como ObjectId — [2026_09_14_000001_widen_auditoria_modelo_id.php](../database/migrations/2026_09_14_000001_widen_auditoria_modelo_id.php).
- Comando `php artisan catalogo:migrar-a-mongo [--dry-run]` — copia los datos existentes y reescribe las FK. Idempotente.
- Reglas de validación `exists:categorias,id` → `exists:mongodb.categorias,_id` (y equivalente para unidades_medida) en `ProductoController` (web+api), `PromocionController`, `RecuentoController`.
- Bug real encontrado y corregido de paso: `PrecioService::coincide()` comparaba `(int) categoria_id` — con ObjectId eso siempre daba `0 === 0` (falso positivo). Ahora compara como string.
- `ReporteController::ganancias()` hacía `leftJoin('categorias', ...)` — imposible contra Mongo. Se separó en dos pasos (trae productos de SQL, resuelve nombre de categoría con `Categoria::whereIn('_id', ...)`).
- Tests: `Tests\TestCase::setUp()` trunca `categorias`/`unidades_medida` en Mongo antes de cada test (RefreshDatabase no toca Mongo). `phpunit.xml` usa `MONGODB_DATABASE=pos_test`, separada de la de desarrollo.
- **91/94 tests pasan.** Las 3 fallas restantes (`RegistrationTest` x2, `ExampleTest` x1) son preexistentes en la rama, no relacionadas con Mongo — confirmado corriendo la suite contra el código sin estos cambios.

**Cómo se probó:** se instaló `ext-mongodb` (PECL 2.5.2) en el PHP local y se levantó un `mongod` standalone en `127.0.0.1:27017` para correr migraciones, el comando de copia de datos y la suite completa contra Mongo real — no es un mock.

**Clientes y proveedores — migrado y probado (ronda 2):**

- `Cliente` y `Proveedor` → modelos Mongo. `Venta`, `Compra`, `Presupuesto`, `OrdenCompra` (SQL) usan `HybridRelations` para sus `belongsTo`.
- FK convertidas a string: `ventas.cliente_id`, `presupuestos.cliente_id`, `compras.proveedor_id`, `ordenes_compra.proveedor_id`, `productos.proveedor_id` — [2026_09_15_000001_mongo_ids_en_ventas_compras.php](../database/migrations/2026_09_15_000001_mongo_ids_en_ventas_compras.php).
- `Venta::scopeBuscar`/`Compra::scopeBuscar` y los `orWhereHas('cliente'|'proveedor', ...)` en las APIs (`whereHas` no puede cruzar SQL↔Mongo) se reescribieron: resuelven ids en Mongo primero, después `whereIn` en SQL.
- `ReporteController::mejoresClientes()` y `proveedoresRanking()` tenían `join('clientes'|'proveedores', ...)` SQL — imposible contra Mongo. Se separaron en dos pasos igual que `ganancias()`.
- `PosService::cotizar()` tenía `?int $clienteId` — con ObjectId eso es un `TypeError` garantizado en cada cotización con cliente. Corregido a `?string`.
- `resources/views/pos/index.blade.php`: el `<select>` de cliente usaba `x-model.number` (Alpine) — convierte el ObjectId a `NaN`. Cambiado a `x-model`.

**Dos bugs reales de fondo encontrados y corregidos** (no eran evidentes hasta correr contra un Mongo real):

1. **`HybridRelations::hasMany()`/`belongsTo()` en la dirección Mongo→SQL rompe la conexión.** Cuando un modelo Mongo (`Cliente`, `Proveedor`, `UnidadMedida`) define `hasMany`/`belongsTo` hacia un modelo SQL, el paquete delega a `parent::hasMany()` (core de Eloquent), que copia la conexión del padre (`mongodb`) al modelo relacionado vía `newRelatedInstance()`. Resultado: la relación arma una query SQL pero contra la conexión Mongo → `Call to a member function prepare() on null`, solo se manifiesta al ejecutar la relación (`->get()`) dentro de un request HTTP real, no en llamadas directas de test. Se arma la relación a mano (`new HasMany(Modelo::query(), $this, 'fk', '_id')`) en `Cliente::ventas()`, `Cliente::listaPrecio()`, `Proveedor::compras()`, `UnidadMedida::productos()`.
2. **La propiedad para nombrar la colección es `$table`, no `$collection`.** `mongodb/laravel-mongodb` 5.x reutiliza la propiedad estándar de Eloquent (`$table`); `$collection` no existe en el paquete y se ignora en silencio. `Categoria`/`Cliente` "andaban" de casualidad porque el plural en inglés (`categorias`, `clientes`) coincide con el español; `Proveedor`/`UnidadMedida` escribían en `proveedors`/`unidad_medidas` (plural en inglés) mientras las migraciones de índices creaban `proveedores`/`unidades_medida` vacías — la validación `exists:mongodb.proveedores,_id` fallaba para proveedores que sí existían. Los 4 modelos usan `$table` ahora.

Ambos bugs solo aparecieron corriendo contra un Mongo real (local, standalone) — con datos de prueba o mocks no se hubieran visto.

**Falta en Fase 2** (no migrado todavía): productos en sí, stock/depósitos/lotes, caja, cuenta corriente (la lógica, no clientes/proveedores que ya están), devoluciones, reportes con más joins. Los módulos de stock/ventas ya migrados (Venta, Compra) funcionan con SQL — falta decidir si conviene llevarlos a Mongo dado que necesitarían **replica set** para transacciones multi-documento (un `mongod` standalone no las soporta; Atlas M0 sí).

**Para levantar Mongo real en este entorno:** `MONGODB_URI` en `.env` apunta a `mongodb://127.0.0.1:27017` por default. En producción, usar Atlas M0 (`mongodb+srv://...`).

---

## Fase 1 — Deploy gratis (funciona sin tocar el modelo de datos)

### Arquitectura

```
Render (Web Service, plan free, Docker)
  ├─ nginx + php-fpm + queue:work + schedule:work   (supervisord, 1 contenedor)
  ├─ DB  → Neon Postgres (free perpetuo, 0.5 GB)
  └─ Uploads → Cloudflare R2 (free, 10 GB, API S3)
```

El plan free de Render **duerme a los 15 min** de inactividad (arranque en frío ~30-50 s)
y da 750 horas/mes. No tiene cron jobs ni workers propios: por eso la cola y el
scheduler corren **dentro del mismo contenedor** vía `docker/supervisord.conf`.

### Pasos

1. **Base de datos — Neon**
   - Crear proyecto en https://neon.tech (free).
   - Copiar el connection string: `postgres://user:pass@ep-xxx.neon.tech/neondb?sslmode=require`

2. **Storage — Cloudflare R2**
   - R2 → Create bucket (ej. `pos-uploads`).
   - R2 → Manage API Tokens → crear token con permiso Object Read & Write.
   - Anotar: Access Key ID, Secret Access Key, y el endpoint
     `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`

3. **App — Render**
   - New → Blueprint → seleccionar este repo (usa `render.yaml`).
   - Completar las env vars marcadas `sync: false`:
     | Var | Valor |
     |-----|-------|
     | `APP_KEY` | salida de `php artisan key:generate --show` |
     | `APP_URL` | `https://<tu-servicio>.onrender.com` |
     | `DB_URL` | connection string de Neon |
     | `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | token de R2 |
     | `AWS_BUCKET` | `pos-uploads` |
     | `AWS_ENDPOINT` | `https://<ACCOUNT_ID>.r2.cloudflarestorage.com` |
   - Deploy. El `entrypoint.sh` corre `migrate --force` solo.

4. **Primer usuario**
   - Render → Shell del servicio: `php artisan tinker` o un seeder de admin.

### Alternativa sin arranque en frío: Fly.io

`fly launch` con un volumen de 1 GB, DB SQLite en el volumen (cero servicio de DB),
uploads al volumen (`FILESYSTEM_DISK=local`). Free allowance alcanza para 1 VM
`shared-cpu-1x`. Mismo `Dockerfile`. Pedir `fly.toml` si se va por acá.

### Local con Docker

```bash
docker build -t pos .
docker run --rm -p 8080:8080 --env-file .env pos
```

---

## Fase 2 — MongoDB

**Costo real:** reescritura de la capa de datos. 41 modelos, 41 migraciones,
13 bloques `DB::transaction`, ~64 usos de relaciones/`whereHas`/`DB::raw`.

Pasos:

1. `composer require mongodb/laravel-mongodb` + extensión `ext-mongodb` (ya está en el Dockerfile lista para habilitar).
2. `config/database.php`: connection `mongodb` con `MONGODB_URI` (MongoDB Atlas M0, free perpetuo, 512 MB — **con replica set**, necesario para transacciones).
3. Cada modelo en `app/Models`: `extends MongoDB\Laravel\Eloquent\Model`, `protected $connection = 'mongodb'`.
4. Migraciones: Mongo no tiene schema. Se conservan solo para crear **índices** (`Schema::connection('mongodb')->create(... $collection->index(...))`). El resto se borra.
5. Claves foráneas: pasan de `unsignedBigInteger` a `string` (ObjectId). Revisar route-model binding y todos los `->where('*_id', ...)`.
6. Relaciones `belongsToMany` / pivots: se modelan embebidas o con arrays de ids (`belongsToMany` de laravel-mongodb).
7. Reportes con `join` / `DB::raw` / agregados SQL → pipeline de agregación de Mongo.
8. `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`: hoy en `database` (SQL). Mover a `mongodb`, `redis` o `file`.
9. Reescribir los 13 `DB::transaction` con la sesión de Mongo.
10. Suite de tests: `RefreshDatabase` no aplica igual — usar base Atlas de test o `mongodb-memory-server`.

Orden sugerido de fases: catálogo (productos/categorías/unidades) → clientes/proveedores →
stock/depósitos/lotes → ventas/compras/caja → cuenta corriente/devoluciones →
presupuestos/OC → reportes. Cada fase: convertir modelos + queries + tests en verde.

## Fase 3 — Vercel

Vercel no ejecuta PHP nativo. Para usarlo de verdad:

1. Frontend nuevo (Next.js / Vite SPA) en Vercel que consume `routes/api.php`.
2. Backend Laravel queda como API pura en Render/Fly (sesión → Sanctum token o JWT).
3. Portar cada vista Blade (`resources/views/**`, ~40 módulos) a componentes del SPA.
4. El service worker offline del POS (`ff4223f`) se rehace en el cliente nuevo.

Es un front-end nuevo completo. Hasta entonces, Vercel solo serviría assets estáticos
y no aporta nada sobre el deploy de Render de la Fase 1.
