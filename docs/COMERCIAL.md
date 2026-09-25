# Sistema Administrativo — Propuesta comercial

Documento para presentar el sistema a un comercio que necesita ordenar su operación.
No es documentación técnica: acá no hay una sola línea de código.

---

## El problema

La mayoría de los comercios chicos y medianos trabajan con una de estas tres cosas:

1. **Cuaderno y calculadora.** No hay forma de saber qué producto deja ganancia.
2. **Planillas de Excel.** Se rompen, se duplican, y nadie sabe cuál es la última versión.
3. **Un sistema caro y genérico.** Se paga una licencia mensual por decenas de módulos que no se usan,
   y cualquier cambio depende de un soporte que tarda semanas.

El resultado es siempre el mismo: **no se sabe cuánto stock hay realmente, ni quién debe plata, ni qué
producto conviene reponer.**

---

## Qué hace el sistema

| Necesidad del comercio | Qué resuelve |
|---|---|
| "No sé cuánto stock tengo" | Stock en tiempo real por depósito, con cada movimiento trazado |
| "Vendí algo que no tenía" | El sistema bloquea la venta si no hay stock disponible |
| "No sé quién me debe" | Cuenta corriente de clientes con saldo e imputación de pagos |
| "La caja nunca cierra" | Apertura, movimientos y arqueo con la diferencia calculada |
| "No sé qué me deja ganancia" | Reporte de rentabilidad con margen por producto y por categoría |
| "Mi empleado ve cosas que no debería" | Roles y permisos por módulo, y cada vendedor ve sólo sus ventas |
| "Perdí datos y no sé quién los tocó" | Auditoría completa: quién cambió qué y cuándo |
| "Necesito el comprobante" | PDF de venta, presupuesto y comprobante de pago |
| "Tengo todo en Excel" | Importación de productos, clientes y proveedores desde Excel/CSV |

---

## Módulos incluidos

**Operación diaria:** ventas, compras, presupuestos, órdenes de compra, caja diaria, devoluciones.
**Control:** stock multi-depósito, transferencias, cuenta corriente de clientes y proveedores.
**Gestión:** productos, categorías, unidades de medida, clientes, proveedores, métodos de pago.
**Dirección:** dashboard, reportes de ventas, compras, rentabilidad, stock crítico y rankings.
**Administración:** usuarios, roles y permisos, auditoría, configuración, backup, tareas y calendario.

---

## Cómo se entrega

1. **Relevamiento.** Una reunión para entender el rubro y qué módulos hacen falta. Los que no se usan
   se sacan del menú: el sistema tiene que ser simple para quien lo opera todos los días.
2. **Adaptación.** Datos del negocio, impuestos, numeración de comprobantes, formato de los PDFs.
3. **Migración.** Los productos, clientes y proveedores que ya están en Excel se cargan al sistema.
4. **Instalación.** En un servidor propio o en la nube, con backup automático.
5. **Capacitación.** Una sesión con el personal que lo va a usar, más un manual corto.
6. **Soporte.** Período de acompañamiento para ajustar lo que aparezca en el uso real.

---

## Preguntas frecuentes

**¿Funciona sin internet?**
Sí, si se instala en una computadora del local. Si se instala en la nube, necesita conexión.

**¿Sirve para mi rubro?**
El núcleo (usuarios, permisos, auditoría, stock, caja) es el mismo para todos. Lo que cambia es qué
módulos de negocio se activan. Se adapta a ferretería, kiosco, distribuidora, taller, indumentaria y
similares.

**¿Puedo usarlo desde el celular?**
La interfaz es web y responsive. Además el sistema tiene API, así que se puede conectar a otra
aplicación más adelante.

**¿Facturación electrónica / AFIP?**
No está incluido hoy. El sistema emite comprobantes internos en PDF. La integración fiscal se cotiza
aparte.

**¿Qué pasa si necesito un módulo nuevo?**
Se cotiza como desarrollo adicional. La arquitectura está pensada para agregar módulos sin tocar los
existentes.

**¿De quién son los datos?**
Del comercio. Se entregan exportables en Excel y CSV en cualquier momento, y el backup es propio.

---

## Qué NO hace

Conviene decirlo de entrada, para no vender humo:

- No emite factura electrónica AFIP (se puede integrar, se cotiza aparte).
- No es multi-empresa: una instalación, un comercio.
- No tiene app nativa de iOS/Android (sí interfaz web responsive y API).
- No hace liquidación de sueldos ni contabilidad general.

---

## Contacto

Joaquín Yunes — [github.com/joaquinyunes](https://github.com/joaquinyunes)
