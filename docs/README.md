# Carpeta /docs/claude_context/

Este es el "cerebro persistente" del proyecto. Sirve para que cualquier sesión nueva de Claude
(o cualquier desarrollador del equipo) entienda en 30 segundos qué es el sistema, en qué punto
está, y qué sigue — sin tener que repetir todo el contexto a mano cada vez.

## Los 6 archivos

| Archivo | Para qué sirve | ¿Cambia entre sesiones? |
|---|---|---|
| `00_prompt_universal.md` | El prompt que pegás al abrir cualquier chat nuevo. Sirve para TODAS las microfases. | No (es fijo) |
| `01_stack.md` | Stack técnico exacto del proyecto. | Casi nunca |
| `02_fases.md` | Mapa completo del proyecto dividido en Niveles y Microfases, con checkboxes de estado. | Sí, marcás lo que se completa |
| `03_schemas.md` | **Foto actual** de la base de datos (no historial, no diffs). | Sí, se reescribe con el estado real |
| `04_system_prompt.md` | Reglas de comportamiento fijas para Claude (seguridad, permisos, qué no debe inventar). | Rara vez |
| `05_handoff.md` | **Foto actual** de en qué quedó la última sesión y cuál es el próximo paso. | Sí, se reescribe cada sesión |

## La regla de oro: por qué no necesitás el archivo anterior

`03_schemas.md` y `05_handoff.md` NO son un log acumulativo de "sesión 1.1, sesión 1.2, sesión 1.3...".
Son una **foto del estado actual**. Cada vez que termina una sesión, se sobreescriben con el estado
real del proyecto en ese momento. Por eso:

- Nunca necesitás subir 5 handoffs viejos, solo el último.
- Nunca necesitás el schema de hace 3 semanas, solo el de ahora.
- Cualquier microfase se puede abrir, en cualquier orden razonable, con estos 6 archivos y nada más.

## Regla de las microfases (máx. 5 archivos de código por sesión)

Para que cada sesión de Claude sea manejable y no se "pierda", cada microfase del archivo
`02_fases.md` está diseñada para tocar **como máximo 5 archivos de código** (migración, modelo,
controlador, rutas, vista — o la combinación que corresponda). Si una microfase necesita más,
se parte en dos.

## Flujo de trabajo

1. Abrís un chat nuevo.
2. Subís los 5 archivos numerados (00 a 05... en realidad 01 a 05; el 00 es el prompt que pegás directo en el chat, no se "sube").
3. Pegás el contenido de `00_prompt_universal.md` como tu primer mensaje, reemplazando el campo `[MICROFASE]` por la que querés trabajar (ej: "2.1 Ventas").
4. Claude confirma que entendió el contexto y arranca.
5. Al final de la sesión, le pedís a Claude: **"Generame el 05_handoff.md actualizado y el 03_schemas.md actualizado si hubo cambios en la base de datos"**, los pegás reemplazando los archivos viejos.
