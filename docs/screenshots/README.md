# Capturas para el README

Estas capturas son lo primero que mira un reclutador o un cliente. Conviene que estén hechas con
datos realistas, no con una base vacía.

## Preparar el sistema

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=DemoSeeder
php artisan serve
```

Entrar con `admin@admin.com` / `password`.

## Cómo tomarlas

- Ventana del navegador en **1440 × 900**, sin barra de marcadores.
- Zoom al 100%.
- Ocultar la barra de direcciones si se puede (modo presentación / F11 y recortar).
- Formato **PNG**, ancho final 1440 px.
- Sin datos personales reales en pantalla.

## Lista

| Archivo | URL | Qué se tiene que ver |
|---|---|---|
| `dashboard.png` | `/dashboard` | Métricas del mes, gráfico de ventas diarias y top de productos |
| `venta-nueva.png` | `/ventas/crear` | Formulario con dos o tres líneas cargadas y el total calculado |
| `productos.png` | `/productos` | Listado con productos en stock crítico visibles |
| `stock-depositos.png` | `/depositos` | Dos depósitos con stock distinto |
| `caja.png` | `/caja` | Caja abierta con movimientos del día |
| `cuenta-corriente.png` | `/cuentas/clientes` | Un cliente con saldo pendiente |
| `reporte-ganancias.png` | `/reportes/ganancias` | Tabla de margen por producto |
| `roles.png` | `/roles` | Matriz de permisos del rol vendedor |
| `comprobante-pdf.png` | `/pdf/venta/1` | El PDF generado |

## Después

Reemplazar la tabla de la sección **Capturas** del `README.md` por las imágenes:

```markdown
| Dashboard | Nueva venta |
|---|---|
| ![Dashboard](docs/screenshots/dashboard.png) | ![Nueva venta](docs/screenshots/venta-nueva.png) |
```

Un GIF corto de una venta completa (cargar producto por código de barras → cobrar → PDF) vale más que
todas las capturas juntas. Grabarlo con ScreenToGif o LICEcap y guardarlo como `venta-completa.gif`.
