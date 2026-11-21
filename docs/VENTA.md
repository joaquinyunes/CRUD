# Venta — paquetes (V1) y costo a 3 años (V2)

> **Antes de ofrecer nada:** el tribunal pidió (1) un producto en producción con un cliente que pague, (2) un contrato de mantenimiento, (3) una sola frase y un solo comprador. Este documento ordena los números; **no reemplaza el cliente piloto (V10)**. Los precios propios son una **propuesta a validar con un comprador real**, no un dato de mercado.

## Frase de venta (V2)
> *Para comercios con stock y caja (ferreterías, kioscos grandes, distribuidoras, tiendas) que hoy llevan todo en planillas: un sistema propio con ventas, stock por depósito, caja con arqueo y cuenta corriente, que se paga una vez en lugar de por mes y por usuario.*

El ERP es genérico: **el rubro lo elige el dueño** (E11). En cuanto haya rubro, la frase se reescribe con el problema de ese rubro (ver la skill `erp-pos` para la base y `backend-por-nicho` para qué usa la competencia del rubro).

## Paquetes (V1) — propuesta
| Paquete | Qué incluye | Precio propuesto (USD) |
|---|---|---|
| **Instalación** | Instancia propia (`scripts/nueva-instancia.sh`), dominio, datos reales cargados (importación CSV de productos, clientes y proveedores), capacitación de una sesión, manual del dueño | **3.000** una vez |
| **Mensual** | Hosting (Render + Atlas), backups con prueba de restauración trimestral, actualizaciones de seguridad (`composer audit` y Dependabot), soporte por escrito con **respuesta en 1 día hábil** (urgente = sistema caído: 4 h hábiles), hasta 2 h/mes de cambios chicos | **120** por mes |
| **Completo** | Instalación + migración de datos del cliente + **12 meses de Mensual incluidos** + integraciones locales (Mercado Pago, WhatsApp, factura electrónica con proveedor certificado) | **10.000** |

Qué **no** incluye ninguno: facturación fiscal propia (el sistema nunca calcula un CAE: lo da el proveedor certificado que elija el cliente, con su contador), desarrollo a medida fuera de las horas del Mensual, el costo de las cuentas de terceros (Atlas, Render, proveedor de facturación, API de WhatsApp), capacitación adicional. Los datos son del cliente. *El contrato y la licencia (V6) los redacta un abogado: 👤.*

**Estado de las integraciones del paquete Completo:** Mercado Pago y WhatsApp **no están hechos** en este proyecto; `FacturacionProvider` existe como contrato con comprobante interno sin validez fiscal. Vender «Completo» hoy es vender trabajo por hacer: no se promete fecha hasta tener credenciales y proveedor elegido.

## Costo a 3 años contra suscripciones (V2)
Precios leídos de los sitios el **06–08/11/2026**; en pesos, mensuales, **más IVA**, sin ajustes (en la práctica los proveedores los suben seguido, así que la suscripción real cuesta más). Dólar oficial venta **ARS 1.570** (dolarapi.com, actualizado 05/11/2026).

| Opción | Mensual | 12 meses | **36 meses** | Fuente |
|---|---|---|---|---|
| Contabilium **Standard** (1.500 comprobantes, 2 usuarios, 2 puntos de venta; sin multi-depósito) | ARS 122.000 ≈ USD 78 | USD 932 | **USD 2.797** (anual con 10 %: USD 2.518) | contabilium.com/ar/planes |
| Contabilium **Pro** (5.000 comprobantes, 10 usuarios) | ARS 179.000 ≈ USD 114 | USD 1.368 | **USD 4.104** | ídem |
| Contabilium **Full** (10.000 comprobantes, usuarios y POS ilimitados) | ARS 245.000 ≈ USD 156 | USD 1.873 | **USD 5.618** | ídem |
| Colppy **Essential** (300 comprobantes/mes; el stock se cobra aparte) | ARS 128.500 ≈ USD 82 | USD 982 | **USD 2.946** | colppy.com/precios |
| Colppy **Platinum** (1.000 comprobantes/mes) | ARS 189.500 ≈ USD 121 | USD 1.448 | **USD 4.345** | ídem |
| **Este sistema, paquete Completo** | 12 meses incluidos, luego USD 120 | USD 10.000 | **USD 12.880** (10.000 + 24 × 120) | propuesta propia |
| **Este sistema, Instalación + Mensual** | USD 120 | USD 4.440 | **USD 7.320** (3.000 + 36 × 120) | propuesta propia |

Costo de infraestructura que sostiene el Mensual (referencia, no es margen): Render Starter ≈ USD 7/mes (512 MB, 0,5 CPU; render.com/pricing, 08/11/2026) + Atlas Flex ≈ USD 8–30/mes (mongodb.com/pricing, 08/11/2026) = **USD 15–37/mes**; el plan gratuito de Render se duerme a los 15 min y Atlas M0 no tiene backups, así que no sirven para un cliente.

### Lectura honesta
- **A 3 años el sistema propio sale entre 1,2 y 5,1 veces más caro que la suscripción** (USD 7.320–12.880 contra USD 2.518–5.996). Aun contra el plan más caro (Contabilium Full, USD 156/mes) el punto de equilibrio está a ~7 años con Instalación + Mensual y a ~20 años con el Completo: **no hay ventaja de costo y no hay que venderlo por precio.**
- Lo que sí cambia, y es lo que hay que probar con un comprador: no se paga por usuario ni por comprobante, los datos y el código son del cliente, el sistema se adapta al rubro, y las integraciones locales las controla él. Si el comprador compara solo precio, pierde el sistema.
- Para que la tabla favorezca, hay tres caminos que decide el dueño: bajar el Completo (p. ej. Instalación 3.000 + Mensual 120 = USD 7.320 a 3 años), vender por rubro con funciones que las suscripciones no tienen, o venderlo a quien necesita muchos usuarios/CUIT/puntos de venta (donde la suscripción escala: Contabilium cobra USD 18 por punto de venta y USD 50 por CUIT extra al mes).
- **Nota sobre lo comparado:** Contabilium y Colppy son sistemas de facturación y contabilidad con stock; no son iguales a este ERP (que no factura). Comparar con el sistema del **rubro elegido** (Fudo, Tiendanube, etc.) cuando haya rubro, leyendo su precio ese día.


---

## V2 por rubro: comercio chico — kiosco, almacén, ferretería (leído el 21/11/2026)

**Rubro elegido: comercio chico con stock y caja (kiosco, maxikiosco, ferretería de barrio).** Es el que mejor encaja con lo que el sistema ya hace (ventas, stock por depósito, caja con arqueo, cuenta corriente, roles) y el que tiene un competidor con precio público claro.

Competidor: **YoFacturo** (yo-facturo.com/kioscos y /punto-de-venta, páginas «Actualizado: 10 de noviembre de 2026»). Precios mensuales en pesos; la página no aclara si incluyen IVA. Prueba gratis de 30 días sin tarjeta. Dólar oficial venta **ARS 1.570** (dolarapi.com, 05/11/2026).

| Plan de YoFacturo | Mensual | 36 meses | En USD | Qué trae (según su página) |
|---|---|---|---|---|
| Emprendedor | ARS 20.000 (16.000/mes pagando el año) | 720.000 (anual: 576.000) | **USD 459** (anual: 367) | POS y factura electrónica ARCA, caja y stock, clientes, cobro por Mercado Pago; un punto de venta |
| Básico | ARS 50.000 | 1.800.000 | **USD 1.146** | + roles y permisos, proveedores, presupuestos, cuentas de clientes, integraciones (lo más parecido a este sistema) |
| Completo | ARS 75.000 | 2.700.000 | **USD 1.720** | + tienda online propia, fidelización, catálogo QR |
| **Este sistema, Instalación + Mensual** | USD 120 | — | **USD 7.320** | sin factura electrónica propia (la da un proveedor certificado) |
| **Este sistema, Completo** | USD 10.000 + 24 × 120 | — | **USD 12.880** | ídem, con integraciones |

Extras de YoFacturo (mensual): asistente IA 15.000, mensajería IA 25.000, repartidores 30.000, multi-identidad fiscal 30.000.

### Conclusión sobre este rubro
- **El sistema propio sale 6 a 16 veces más caro que la suscripción** (USD 7.320–12.880 contra USD 459–1.146) y **no trae factura electrónica**, que es lo primero que pide un kiosco. **USD 10.000 no se sostiene en este rubro**: un kiosco paga USD 13–32 por mes y no compra un sistema.
- Lo que *no* da la suscripción y sí este sistema: datos y código propios, sin cuota por usuario ni por caja, adaptación a la forma de trabajar del negocio. Eso vale para **ferreterías/distribuidoras medianas** (varios depósitos, muchas cuentas corrientes de clientes, 5+ usuarios), no para un kiosco.
- Recomendación: no vender el «Completo» a un comercio chico. Si el piloto es un comercio chico, ofrecer **Instalación reducida + Mensual** y asumir que es un caso de referencia, no el producto de USD 10.000. El comprador de USD 10.000 es un negocio que hoy paga más de USD 150/mes en suscripciones y herramientas sueltas (Premium de YoFacturo: desde ARS 150.000 ≈ USD 96/mes, 36 meses ≈ USD 3.440).
- Antes de poner un precio final: validar con un comercio real cuánto paga hoy y qué le falta.
