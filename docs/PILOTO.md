# Kit del piloto (V10) — un cliente real antes de seguir

El piloto lo hace el usuario con un comercio real; este kit deja todo listo para que dure **30 días** y termine con una decisión (seguir, ajustar o parar). Sin piloto no se construye nada más.

## 1. Perfil del comercio piloto
Comercio con stock y caja, 2–6 personas usando el sistema, que hoy lleve ventas/stock en planillas o en un sistema que le queda corto. **Mejor una ferretería o distribuidora chica que un kiosco** (ver `docs/VENTA.md`: el kiosco no paga por esto). Que tenga a alguien que decida y atienda el teléfono.

## 2. Antes de arrancar (semana 0)
- [ ] Reunión de 45 min: qué hace hoy, cuánto paga hoy en sistemas/planillas, qué le duele (anotarlo textual).
- [ ] Datos a migrar: productos, clientes, proveedores, saldos iniciales (CSV; probar con 5 filas).
- [ ] Instancia propia (`scripts/nueva-instancia.sh`), dominio, 2FA del dueño, usuarios y roles por persona.
- [ ] Definir con el comercio **quién carga qué** y cuándo se abre/cierra la caja.
- [ ] Acordar por escrito: duración (30 días), precio del piloto, qué pasa al terminar (contrato borrador abajo).

## 3. Durante el piloto (semanas 1–4)
| Semana | Foco | Se mide |
|---|---|---|
| 1 | Carga de datos y primeras ventas en paralelo con lo que usa hoy | Ventas cargadas / ventas reales del día |
| 2 | Caja diaria y devoluciones | Días con cierre de caja en 0; diferencias y su causa |
| 3 | Compras, stock mínimo y cuenta corriente | Faltantes evitados; deudas de clientes cobradas |
| 4 | Dejar de usar lo anterior | Cuántas tareas siguen fuera del sistema |

Contacto: una vez por semana (15 min) y un canal de WhatsApp para dudas. Registrar cada incidente (fecha, qué pasó, cuánto tardó la respuesta) en `docs/RUNBOOK.md` §7.

## 4. Métricas de éxito (decidir antes, no después)
- **Uso:** ≥ 80 % de las ventas del comercio cargadas en el sistema a partir de la semana 3.
- **Caja:** ≥ 80 % de los cierres con diferencia 0 o explicada.
- **Stock:** diferencia entre stock del sistema y conteo físico (muestra de 20 productos) ≤ 5 %.
- **Soporte:** respuesta dentro del compromiso (1 día hábil; caído: 4 h hábiles) en el 100 % de los casos.
- **Disposición a pagar:** el comercio acepta pagar **al menos X** (definir X con el usuario antes de arrancar) por mes o por la instalación. Es la métrica que decide.
- **Qué falta:** lista de lo que el comercio pidió y no existe (factura electrónica, Mercado Pago, etc.), ordenada por cuántas veces lo pidió.

## 5. Cierre (día 30)
- [ ] Reunión de cierre con las métricas de arriba.
- [ ] Decisión: **seguir** (firma de contrato y seña) / **ajustar** (qué y plazo) / **parar** (por qué).
- [ ] Pedir una referencia o testimonio por escrito (con permiso para usarlo en `docs/venta/index.html`).
- [ ] Copia de seguridad y restauración probadas en la instancia del cliente (`docs/BACKUPS.md`).

## 6. Contrato — BORRADOR (lo revisa y redacta un abogado: V6)
> **Este texto es un esqueleto para ordenar la conversación; no tiene validez legal sin revisión profesional.**

1. **Partes y objeto.** [Proveedor] licencia a [Cliente] el uso del Sistema Administrativo para su comercio [domicilio].
2. **Alcance.** Instalación, carga inicial de datos, capacitación, soporte y mantenimiento según el plan elegido (`docs/VENTA.md`).
3. **No incluye.** Facturación fiscal propia (la emite un proveedor certificado elegido por el cliente con su contador), desarrollo a medida fuera de las horas del plan, costos de cuentas de terceros (nube, base de datos, proveedor de facturación, mensajería).
4. **Soporte.** Respuesta por escrito dentro de 1 día hábil; sistema caído: 4 horas hábiles. Horario [ ].
5. **Datos.** Los datos del cliente son del cliente; se entregan en formato exportable a pedido y se borran al terminar el contrato, salvo obligación legal. Tratamiento conforme a la Ley 25.326.
6. **Seguridad.** Copias diarias, doble factor del administrador, actualizaciones de seguridad. El cliente cuida sus claves y usuarios.
7. **Precio y plazo.** [Instalación] una vez; [Mensual] por mes; el piloto dura 30 días y se convierte o termina según el cierre.
8. **Responsabilidad.** Limitada al monto abonado en los últimos 12 meses; no cubre pérdidas indirectas.
9. **Propiedad intelectual y código.** [Cesión o licencia: a definir con el abogado].
10. **Terminación.** Aviso de 30 días; entrega de una copia de los datos.
