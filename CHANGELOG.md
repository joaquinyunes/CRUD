# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

## [No publicado]

### Seguridad
- **Doble factor de autenticación por aplicación (TOTP).** Donde antes había una columna
  `two_factor_enabled` sin implementación detrás, ahora está el circuito completo: alta con QR,
  confirmación contra un código real, ocho códigos de recuperación de un solo uso y baja pidiendo
  la contraseña. Con el doble factor activo, la contraseña correcta ya no abre sesión. El secreto
  y los códigos se guardan cifrados con APP_KEY.
- `TRUSTED_PROXIES` acota de qué proxies se acepta un `X-Forwarded-For`, que es la IP con la que se
  cuentan los intentos de login. El default sigue siendo `*`, que es lo correcto detrás de un PaaS.

### Corregido
- **El perfil del usuario y el editor de permisos salían completamente en blanco.** Las dos usan el
  layout como componente (`<x-app-layout>`), que entrega el contenido en `$slot`, y el layout solo
  renderizaba `@yield('content')`. Nadie podía cambiar su nombre, su correo ni su contraseña, y la
  pantalla de permisos que el README muestra en una captura estaba vacía. Los tests que había solo
  miraban el código de respuesta, y un 200 en blanco no delata nada.
- **La API numeraba los comprobantes con `MAX(id) + 1`.** Sin bloqueo, ignorando el prefijo
  configurado, reutilizando números al borrar una venta y siguiendo los ids en vez de la serie: una
  venta cargada por API y otra por pantalla podían salir con el mismo número. Era una tercera
  implementación de la numeración, además de las dos que ya se habían unificado.
- **Los totales se calculaban en punto flotante.** Cien líneas de 0,07 no daban 7,00. En un
  comprobante eso significa que el total impreso no coincide con la suma de los renglones. Ahora la
  aritmética entra en centavos enteros y es exacta por construcción.
- La API ignoraba el descuento, el depósito y los medios de pago de una venta: los aceptaba y los
  tiraba. Y no aplicaba el límite de crédito del cliente ni rechazaba vender sobre un cliente
  archivado, porque esas reglas vivían sueltas en el controller web.
- Las reglas de validación de la API habían divergido de las de la web: el documento del cliente
  aceptaba 20 caracteres por una puerta y 50 por la otra, el estado "eliminado" no existía por API,
  el proveedor no validaba estado, y el código de un producto borrado bloqueaba la reutilización
  solo en una de las dos. Los valores por defecto del alta de un producto vivían dentro del
  controller web, así que la misma alta por API guardaba nulos.

### Cambiado
- **Emitir una venta o una compra pasa a un servicio.** Estaba implementado dos veces, una por cada
  puerta, y ahí es donde se escaparon la numeración y las reglas de negocio. `VentaController` pasó
  de 372 a 144 líneas.
- **Ningún controller valida inline.** Los catorce que quedaban pasaron a FormRequests, y
  presupuestos y órdenes de compra se suman a las reglas compartidas de ventas y compras, que
  estaban copiadas por cuarta y quinta vez. Al unificar aparecieron reglas que faltaban en algunos
  módulos: descuento mayor al 100%, entrega estimada anterior a la orden, evento que termina antes
  de empezar, movimiento de caja en cero, código de método de pago repetido al editar.
- **Los formularios de carga dejan de embeber el catálogo.** Traían la lista completa de productos,
  clientes y proveedores dentro del HTML, una vez como `<option>` por cada fila y otra como JSON.
  Medido contra una base con 5.000 productos: el formulario de venta embebía 2 MB y ahora pesa
  35 KB. En su lugar hay un autocompletado que busca contra endpoints a medida que se escribe,
  funciona con teclado y avisa antes de enviar si se escribió algo sin elegir de la lista.

### Seguridad
- **La API exige permisos.** Todas las rutas de `routes/api.php` colgaban solo de `auth:sanctum`:
  cualquier token autenticado podía crear y borrar productos, clientes, proveedores, ventas y
  compras, aunque su dueño fuera un usuario de solo lectura. Ahora cada verbo pide el permiso que le
  corresponde, igual que la interfaz web.
- **Límite de intentos en la API.** `/api/login` estaba abierto a fuerza bruta y el resto de la API
  no tenía tope. 5 intentos por minuto para el login, 60 pedidos por minuto y por usuario para el
  resto.
- **Un usuario desactivado deja de entrar en el acto**, tenga sesión abierta o un token ya emitido.
- **Dependencias con CVEs conocidos, actualizadas**: dompdf (lectura de archivos locales vía SVG en
  data-URI, entre otras cinco), guzzle, phpspreadsheet, league/commonmark y seis paquetes de npm.
  `composer audit` y `npm audit` ahora corren en el CI.
- La auditoría nunca copia hashes de contraseña ni tokens.

### Corregido
- **Dos ventas simultáneas podían llevarse el mismo número.** El consecutivo salía de buscar el
  máximo con `lockForUpdate()`, y un `SELECT ... FOR UPDATE` que no devuelve filas no bloquea nada:
  con la tabla vacía, las dos transacciones veían "no hay ninguna" y las dos tomaban el 1. Ahora hay
  una tabla de contadores con una fila por serie, y es esa fila la que se bloquea.
- **La numeración usaba sintaxis exclusiva de MySQL.** `CAST(... AS UNSIGNED)` no existe en
  PostgreSQL. El corte del número se hace en PHP y el problema desaparece en los tres motores.
- **`VentaController` tenía su propia copia del numerador**, en paralelo al helper que el proyecto
  presentaba como el único lugar donde se decide un número.
- **Borrar un proveedor con compras, o un usuario con ventas, devolvía un error 500** de clave
  foránea. Ahora se archivan o se desactivan, y el sistema lo explica.
- **`uniq_id()` no existe.** Importar un CSV de productos sin columna `codigo` tiraba un fatal que ni
  siquiera entraba al `catch`. Además el código ahora se deriva del nombre, así que reimportar la
  misma planilla actualiza en vez de duplicar.
- **`CalendarioController::mover()` declaraba `RedirectResponse` y devolvía JSON**: TypeError al
  arrastrar un evento.
- **`Setting::obtener()` cacheaba el valor por defecto.** Dos llamadas con defaults distintos para la
  misma clave se pisaban durante una hora, y una configuración cargada después del primer arranque no
  se veía hasta que expiraba la caché.
- El trait de auditoría estaba aplicado a dos *controllers*, donde no hace absolutamente nada.
- La vista del registro público no existía: el controller que la devolvía era código muerto con una
  ruta que nunca se registró. Eliminado (en este sistema las cuentas las crea un administrador).
- `two_factor_enabled` era una columna sin una sola línea de implementación detrás. Eliminada: una
  bandera de seguridad que no hace nada es peor que no tenerla.
- El modelo de usuario no implementaba `MustVerifyEmail`, así que el middleware `verified` —presente
  en los 32 archivos de rutas— no verificaba nada.
- La tabla de categorías no tenía `created_at` ni `updated_at`, pero la API los devolvía igual.
- Un proveedor archivado ya no desaparece del formulario de la compra que lo referencia.

### Cambiado
- **La auditoría pasa de 3 modelos a todos los módulos.** Antes solo registraba categorías, clientes
  y productos: no quedaba rastro de quién anuló una venta, cambió un precio de compra o le dio
  permisos de administrador a alguien. Ahora también se registra lo que pasa sin sesión iniciada
  (consola, colas, tareas programadas), que es justo lo que más interesa poder rastrear.
- **"Administrador" deja de ser un nombre con poderes especiales.** El middleware comparaba
  `$rol->nombre === 'Administrador' || === 'admin'` y salteaba toda la verificación. Ahora "poder
  todo" es el permiso comodín `*`, como cualquier otro: se puede crear un rol "Dueño" con acceso
  total, o sacarle una parte al administrador, sin tocar código.
- Los permisos de cada rol se cachean, y el cambio se invalida solo: el middleware hacía dos
  consultas en cada request para no descubrir nada nuevo.
- **Validación fuera de los controllers.** Ventas y compras comparten un `ComprobanteRequest`: las
  reglas estaban duplicadas palabra por palabra en los dos, que es exactamente cómo se
  desincronizan. Un descuento en porcentaje mayor a 100 ahora se rechaza.
- El entrypoint del contenedor ya no decide si sembrar contando usuarios con `tinker` y asumiendo
  "base vacía" ante cualquier error: como `DemoSeeder` borra y regenera las tablas de negocio, ese
  default se llevaba puestos los datos de un cliente. La decisión vive en `demo:sembrar`, en PHP, y
  ante la duda no toca nada.

### Agregado
- **Análisis estático con PHPStan/Larastan nivel 5, sin errores**, en el CI. Encontró tres bugs de
  runtime que la suite no tocaba.
- El CI verifica además que las migraciones corran de punta a punta contra una base limpia en los
  tres motores.
- **Tareas programadas**: los presupuestos vencen solos al pasar su validez (el listado ya ofrecía
  filtrar por "vencido", pero nadie escribía nunca ese estado), aviso diario de stock crítico, y
  purga de tokens de API vencidos y jobs fallidos.
- **Worker de colas**: las notificaciones de venta, compra y stock bajo salieron del request. Corre
  dentro del contenedor en la demo y como servicio aparte en una instalación real.
- Driver de S3 instalado y documentado: en un servidor cuyo disco se borra en cada deploy, los
  adjuntos, los PDFs y los backups no pueden vivir en `storage/`.
- Índices en la tabla de auditoría, que ahora crece mucho más rápido.
- Métodos de pago y unidades de medida en uso se desactivan en vez de borrarse.
- Tests: 114 en total (antes 75), con la numeración, los permisos de la API, las bajas, la auditoría
  y la validación de comprobantes cubiertos por primera vez.

### Agregado
- Despliegue con Docker (FrankenPHP) y blueprint de Render con PostgreSQL enlazado.
- `docker-compose.yml` para levantar el sistema completo en local sin instalar PHP.
- Integración continua en GitHub Actions: la suite corre contra SQLite, MySQL y PostgreSQL, más
  verificación de estilo con Pint.
- `App\Support\PeriodoSql`: expresiones de agrupación por día, semana y mes independientes del motor.
- Tests de portabilidad de los reportes por período.
- Diagrama de arquitectura, banner del proyecto y capturas de pantalla generadas
  automaticamente con Playwright (`docs/capturas.cjs`).
- Licencia source available.
- Datos de demostracion para caja diaria, multi-deposito y cuenta corriente: antes
  esos modulos quedaban vacios en la demo.
- `pint.json` que respeta la alineacion de arrays ya usada en el proyecto.
- Tests de `CalculadorTotales`, del estado de pago y de la ganancia del panel.

### Cambiado
- Los reportes de ventas y compras por período ya no dependen de `YEARWEEK()` ni `DATE_FORMAT()`, que
  son exclusivas de MySQL. Antes fallaban en PostgreSQL y en SQLite.
- El gráfico de ventas diarias del dashboard agrupaba por la columna `fecha` mientras seleccionaba
  `DATE(fecha)`, lo que rompía en PostgreSQL. Ahora agrupa por la misma expresión que selecciona.
- `.env.example` documenta los tres motores soportados y usa español como idioma por defecto.
- Metadatos de `composer.json`: nombre, descripción y licencia propios del proyecto.

### Corregido
- `DemoSeeder` usaba `PRAGMA foreign_keys` y `sqlite_sequence`, exclusivos de SQLite:
  el seed de demostracion fallaba en PostgreSQL, que es lo que corre en el deploy.
- `DemoSeeder` guardaba en `ventas.total` el subtotal sin descuento ni impuesto,
  mientras que la aplicacion guarda ahi el total final. Los importes del panel no
  coincidian con los del comprobante.
- `DemoSeeder` dependia de `UniversalSeeder` sin llamarlo: `migrate --seed` dejaba
  el sistema sin unidades de medida y el seed de demo abortaba.
- La ganancia del mes del panel se calculaba como ventas menos compras, asi que
  reponer stock figuraba como perdida. Ahora es el margen bruto sobre lo vendido,
  la misma formula que usa el reporte de rentabilidad.
- Los tests de registro publico y el de la ruta raiz eran scaffolding de Breeze que
  no reflejaba el sistema: se reemplazaron por tests del comportamiento real.

### Eliminado
- Archivos que no pertenecían al repositorio: `my.ini`, `mysql_error.txt`, `mysql_output.txt`.

## Versiones anteriores

El historial previo está en los mensajes de commit: presupuestos, órdenes de compra con recepción
parcial, multi-depósito, políticas de autorización por dueño, caja diaria, cuenta corriente,
devoluciones, anulación de comprobantes, lector de código de barras y traducciones al español.
