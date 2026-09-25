# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

## [No publicado]

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
