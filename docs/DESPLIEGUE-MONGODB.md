# Despliegue con MongoDB Atlas

El sistema usa **MongoDB** (con replica set: las ventas, el stock y la caja usan transacciones). Esta guía
deja lista la puesta en producción en Render con Atlas. Los pasos marcados con 👤 los tiene que hacer una
persona con acceso a las cuentas de Atlas y de Render; el repositorio ya trae todo lo demás.

## 1. Atlas 👤

1. Crear un proyecto y un cluster. Todos los clusters de Atlas son replica set.
2. **Plan que no se pause.** El plan gratuito (M0) se pausa por inactividad, justo lo que pasó con el
   Postgres gratuito de Render. Elegir un plan pago o uno que Atlas indique como "no se pausa" y
   **verificarlo en la consola de Atlas**, porque los planes y sus condiciones cambian.
3. *Database Access*: crear un usuario con rol `readWrite` sobre la base `crud_erp` (no usar el usuario
   administrador del proyecto).
4. *Network Access*: permitir las IP de salida del servicio de Render (o `0.0.0.0/0` solo si se acepta
   el riesgo y la contraseña es fuerte).
5. Copiar la cadena de conexión (`mongodb+srv://<usuario>:<clave>@<cluster>.mongodb.net/?retryWrites=true&w=majority`).

## 2. Render 👤

1. **New → Blueprint** y conectar el repositorio. Render lee [`render.yaml`](../render.yaml).
2. Cargar a mano las dos variables que figuran con `sync: false` (nunca van al repositorio):
   - `MONGODB_URI`: la cadena de Atlas del paso anterior.
   - `APP_KEY`: la salida de `php artisan key:generate --show` (con el prefijo `base64:`).
3. Para una instalación real: `DB_SEED_ON_BOOT=false`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` (12 caracteres o
   más), y `FILESYSTEM_DISK=s3` con las variables de AWS/R2/B2 (el disco del contenedor se borra en cada deploy,
   y con él los backups).
4. Deploy. El arranque (`docker/entrypoint.sh`) verifica la conexión con reintentos, crea los índices con
   `php artisan mongo:indices` y, según la configuración, siembra la demo o prepara la instalación.
5. Render usa `/up` como health check: responde 200 **solo si MongoDB contesta**.

## 3. Comprobar en producción 👤

Lo que cierra la fase de despliegue: en `https://<tu-servicio>.onrender.com`

- [ ] Iniciar sesión.
- [ ] Registrar una venta en efectivo con una caja abierta.
- [ ] Registrar una devolución de esa venta.
- [ ] Cerrar la caja y revisar que la diferencia sea 0.
- [ ] **Backup y restauración reales**: crear un respaldo desde *Backup*, anotar un dato, cambiarlo,
      restaurar el respaldo y comprobar que el dato volvió. (Con `FILESYSTEM_DISK=s3` el respaldo
      sobrevive a un nuevo deploy.)

## 4. Apagar el Postgres vencido 👤

Una vez comprobado lo anterior, borrar la base `crud-erp-db` de Render (ya no la usa nadie) y
confirmar que `render.yaml` no tiene bloque `databases:`.

## Datos anteriores

Esta migración parte de una base vacía. Si había datos del Postgres que conservar, hay que importarlos
desde un respaldo antes de abrir el sistema: ver la fase 9 de `PLAN-CRUD-MONGODB.md`. Un respaldo `.json.gz` hecho con la versión SQL **no** se puede
restaurar acá (el sistema lo rechaza a propósito: es de otro motor).

## Desarrollo local

```bash
docker compose up --build        # app + mongod de un nodo como replica set
```

o un `mongod --replSet rs0` propio y `MONGODB_URI=mongodb://127.0.0.1:27017/?replicaSet=rs0`.
