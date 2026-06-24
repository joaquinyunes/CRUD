# 04_system_prompt.md — Reglas de Comportamiento

Estas reglas aplican siempre, en cualquier microfase, sin excepción.

## Seguridad (no negociables)

1. **Nunca SQL crudo.** Siempre Eloquent o Query Builder parametrizado. Prohibido
   `DB::select("SELECT * FROM productos WHERE id = $id")` o cualquier concatenación de variables
   en una query.
2. **Nunca imprimir sin escapar.** Siempre `{{ $variable }}`. Prohibido `{!! $variable !!}` salvo
   que se justifique explícitamente (ej: contenido HTML controlado por el propio sistema, nunca
   input de usuario).
3. **CSRF:** confiar en la protección nativa de Laravel, no desactivarla.
4. **Permisos granulares, no genéricos.** Siempre formato `modulo.accion` (`productos.crear`,
   `productos.editar`, `productos.eliminar`, `productos.ver`). Nunca un permiso tipo
   "puede gestionar productos".
5. **Rate limiting** en login y en cualquier endpoint público sensible.
6. **2FA** es opcional y solo aplica a administradores, no obligar a otros roles.
7. **Todo movimiento de stock pasa por `movimientos_stock`.** Nunca un `UPDATE` directo al campo
   `stock` de `productos` desde un controlador de venta/compra.
8. **Eliminado lógico, no DELETE físico**, en productos, clientes, proveedores (estado
   activo/archivado/eliminado), salvo que se pida explícitamente lo contrario.

## Anti-alucinación (crítico)

9. **No inventar campos, tablas ni relaciones** que no estén en `03_schemas.md`. Si una
   funcionalidad pedida requiere un campo que no existe, decirlo explícitamente y proponer el
   cambio de schema antes de escribir código que lo use.
10. **No inventar estado de stock, precios ni datos de negocio.** Si no hay dato real disponible
    en el contexto entregado, pedirlo o dejar un placeholder claramente marcado como tal
    (`// TODO: valor real pendiente de definir`), nunca inventar un número y presentarlo como dato real.
11. **No asumir reglas de negocio no confirmadas.** Si algo del flujo (ej: condiciones de pago,
    políticas de devolución, validaciones de stock negativo) no está definido en estos archivos,
    preguntar antes de implementar una regla propia.

## Alcance de cada sesión

12. **Respetar el límite de 5 archivos de código por microfase** definido en `02_fases.md`. Si una
    tarea pedida excedería ese límite, avisar y proponer dividirla en dos microfases.
13. **No adelantarse a niveles superiores.** Si estamos en Nivel 1, no meter lógica de Nivel 3/4
    (ej: campos personalizados, multi-tenant) "por si después se necesita". Eso es justamente lo
    que este proyecto busca evitar.
14. **Al cerrar la sesión**, generar el `05_handoff.md` actualizado y, si hubo cambios de base de
    datos, el `03_schemas.md` actualizado.

## Reglas de negocio específicas (completar según el cliente final)

> Esta sección queda como plantilla. Cuando definas reglas concretas del negocio real (por
> ejemplo: "si se detecta un pago, pedir comprobante", "los pedidos personalizados requieren 50%
> de anticipo", condiciones de cancelación, etc.), agregalas aquí como lista numerada, igual de
> explícita que las reglas de seguridad arriba. Mientras esta sección esté vacía, Claude no debe
> asumir ninguna regla de pago/cobro/anticipo que no esté escrita acá.
