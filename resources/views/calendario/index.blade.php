@extends('layouts.app')

@section('page_title', 'Calendario')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Calendario</h2>
    <button onclick="abrirModalCrear()" class="r-btn r-btn-primary r-btn-sm">Nuevo evento</button>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.1">
    <div id="calendar"></div>
</div>

@endsection

{{-- Modal --}}
<div id="evento-modal" style="position:fixed; inset:0; z-index:50; display:none; overflow-y:auto;" aria-modal="true">
    <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px;">
        <div style="position:fixed; inset:0; background:rgba(28,36,34,0.5); backdrop-filter:blur(4px);" onclick="cerrarModal()"></div>
        <div class="r-card-flat" style="position:relative; z-index:10; max-width:480px; width:100%; padding:var(--space-6);">
            <h3 id="modal-titulo" class="r-display-m" style="font-size:1.25rem; margin-bottom:var(--space-4);">Nuevo evento</h3>
            <form id="evento-form" method="POST" style="display:flex; flex-direction:column; gap:var(--space-4);">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">
                <input type="hidden" id="evento-id" value="">
                <div>
                    <label class="r-label">Título *</label>
                    <input type="text" name="titulo" id="titulo" class="r-input" required>
                </div>
                <div>
                    <label class="r-label">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="2" class="r-input"></textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4);">
                    <div>
                        <label class="r-label">Inicio *</label>
                        <input type="datetime-local" name="inicio" id="inicio" class="r-input" required>
                    </div>
                    <div>
                        <label class="r-label">Fin</label>
                        <input type="datetime-local" name="fin" id="fin" class="r-input">
                    </div>
                </div>
                <div class="r-flex r-items-center r-gap-4">
                    <div style="flex:1;">
                        <label class="r-label">Color</label>
                        <input type="color" name="color" id="color" value="#E2A13B" style="height:40px; width:80px; border:1.5px solid var(--color-line); border-radius:var(--border-radius-sm); cursor:pointer;">
                    </div>
                    <div class="r-flex r-items-center r-gap-2" style="margin-top:20px;">
                        <input type="checkbox" name="todo_el_dia" id="todo_el_dia" value="1" style="accent-color:var(--color-marigold);">
                        <label for="todo_el_dia" style="font-size:0.875rem; color:var(--color-ink-soft);">Todo el día</label>
                    </div>
                </div>
                <div class="r-flex r-items-center r-justify-between r-mt-4" style="padding-top:var(--space-4); border-top:1px solid var(--color-line);">
                    <button type="button" onclick="cerrarModal()" style="font-size:0.875rem; color:var(--color-ink-soft); cursor:pointer; background:none; border:none;">Cancelar</button>
                    <button type="submit" class="r-btn r-btn-accent r-btn-sm">Guardar</button>
                </div>
            </form>
            <button id="btn-eliminar" onclick="eliminarEvento()" style="position:absolute; top:var(--space-4); right:var(--space-4); color:#dc2626; background:none; border:none; cursor:pointer; font-size:0.875rem; display:none;">Eliminar</button>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const eventos = @json($eventos);
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
        events: eventos.map(e => ({ id: e.id, title: e.title, start: e.start, end: e.end, allDay: e.allDay, color: e.color, description: e.description })),
        editable: true,
        selectable: true,
        select: function(info) { abrirModalCrear(); document.getElementById('inicio').value = info.startStr.replace('Z', '').substring(0, 16); if (info.endStr) document.getElementById('fin').value = info.endStr.replace('Z', '').substring(0, 16); },
        eventClick: function(info) { editarEvento(info.event.id); },
        eventDrop: function(info) { fetch('{{ route("calendario.mover", ":id") }}'.replace(':id', info.event.id), { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }, body: JSON.stringify({ start: info.event.startStr, end: info.event.endStr }) }); }
    });
    calendar.render();
    window.calendar = calendar;
});
function abrirModalCrear() { document.getElementById('modal-titulo').textContent = 'Nuevo evento'; document.getElementById('evento-form').action = '{{ route("calendario.store") }}'; document.getElementById('form-method').value = 'POST'; document.getElementById('evento-id').value = ''; document.getElementById('titulo').value = ''; document.getElementById('descripcion').value = ''; document.getElementById('inicio').value = ''; document.getElementById('fin').value = ''; document.getElementById('color').value = '#E2A13B'; document.getElementById('todo_el_dia').checked = false; document.getElementById('btn-eliminar').style.display = 'none'; document.getElementById('evento-modal').style.display = 'block'; }
function editarEvento(id) { fetch('{{ route("calendario.eventos-json") }}').then(r => r.json()).then(eventos => { const ev = eventos.find(e => e.id == id); if (!ev) return; document.getElementById('modal-titulo').textContent = 'Editar evento'; document.getElementById('evento-form').action = '{{ route("calendario.update", ":id") }}'.replace(':id', id); document.getElementById('form-method').value = 'PUT'; document.getElementById('evento-id').value = id; document.getElementById('titulo').value = ev.title; document.getElementById('descripcion').value = ev.description || ''; document.getElementById('inicio').value = ev.start ? ev.start.substring(0, 16) : ''; document.getElementById('fin').value = ev.end ? ev.end.substring(0, 16) : ''; document.getElementById('color').value = ev.color || '#E2A13B'; document.getElementById('todo_el_dia').checked = ev.allDay || false; document.getElementById('btn-eliminar').style.display = 'block'; document.getElementById('btn-eliminar').dataset.id = id; document.getElementById('evento-modal').style.display = 'block'; }); }
function cerrarModal() { document.getElementById('evento-modal').style.display = 'none'; }
function eliminarEvento() { const id = document.getElementById('btn-eliminar').dataset.id; if (!confirm('¿Eliminar este evento?')) return; const form = document.createElement('form'); form.method = 'POST'; form.action = '{{ route("calendario.destroy", ":id") }}'.replace(':id', id); form.innerHTML = '@csrf @method("DELETE")'; document.body.appendChild(form); form.submit(); }
</script>
