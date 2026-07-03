@extends('layouts.app')

@section('page_title', 'Calendario')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Calendario</h1>

            <button onclick="abrirModalCrear()"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                Nuevo evento
            </button>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <div id="calendar"></div>
        </div>

    </div>
</div>

<!-- Modal Crear/Editar -->
<div id="evento-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75" onclick="cerrarModal()"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6 z-10">
            <h3 id="modal-titulo" class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Nuevo evento</h3>

            <form id="evento-form" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">
                <input type="hidden" id="evento-id" value="">

                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título <span class="text-red-500">*</span></label>
                    <input type="text" name="titulo" id="titulo"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           required>
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Inicio <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="inicio" id="inicio"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               required>
                    </div>
                    <div>
                        <label for="fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fin</label>
                        <input type="datetime-local" name="fin" id="fin"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color</label>
                        <input type="color" name="color" id="color" value="#4f46e5"
                               class="mt-1 h-10 w-20 rounded-md border-gray-300 dark:border-gray-600 cursor-pointer">
                    </div>
                    <div class="flex items-center gap-2 mt-5">
                        <input type="checkbox" name="todo_el_dia" id="todo_el_dia" value="1"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="todo_el_dia" class="text-sm text-gray-700 dark:text-gray-300">Todo el día</label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="cerrarModal()"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                        Guardar
                    </button>
                </div>
            </form>

            <button id="btn-eliminar" onclick="eliminarEvento()" class="hidden absolute top-4 right-4 text-red-500 hover:text-red-700 text-sm">
                Eliminar
            </button>
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
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: eventos.map(e => ({
            id: e.id,
            title: e.title,
            start: e.start,
            end: e.end,
            allDay: e.allDay,
            color: e.color,
            description: e.description
        })),
        editable: true,
        selectable: true,
        select: function(info) {
            abrirModalCrear();
            document.getElementById('inicio').value = info.startStr.replace('Z', '').substring(0, 16);
            if (info.endStr) {
                document.getElementById('fin').value = info.endStr.replace('Z', '').substring(0, 16);
            }
        },
        eventClick: function(info) {
            editarEvento(info.event.id);
        },
        eventDrop: function(info) {
            fetch('{{ route("calendario.mover", ":id") }}'.replace(':id', info.event.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    start: info.event.startStr,
                    end: info.event.endStr
                })
            });
        }
    });

    calendar.render();
    window.calendar = calendar;
});

function abrirModalCrear() {
    document.getElementById('modal-titulo').textContent = 'Nuevo evento';
    document.getElementById('evento-form').action = '{{ route("calendario.store") }}';
    document.getElementById('form-method').value = 'POST';
    document.getElementById('evento-id').value = '';
    document.getElementById('titulo').value = '';
    document.getElementById('descripcion').value = '';
    document.getElementById('inicio').value = '';
    document.getElementById('fin').value = '';
    document.getElementById('color').value = '#4f46e5';
    document.getElementById('todo_el_dia').checked = false;
    document.getElementById('btn-eliminar').classList.add('hidden');
    document.getElementById('evento-modal').classList.remove('hidden');
}

function editarEvento(id) {
    fetch('{{ route("calendario.eventos-json") }}')
        .then(r => r.json())
        .then(eventos => {
            const ev = eventos.find(e => e.id == id);
            if (!ev) return;

            document.getElementById('modal-titulo').textContent = 'Editar evento';
            document.getElementById('evento-form').action = '{{ route("calendario.update", ":id") }}'.replace(':id', id);
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('evento-id').value = id;
            document.getElementById('titulo').value = ev.title;
            document.getElementById('descripcion').value = ev.description || '';
            document.getElementById('inicio').value = ev.start ? ev.start.substring(0, 16) : '';
            document.getElementById('fin').value = ev.end ? ev.end.substring(0, 16) : '';
            document.getElementById('color').value = ev.color || '#4f46e5';
            document.getElementById('todo_el_dia').checked = ev.allDay || false;
            document.getElementById('btn-eliminar').classList.remove('hidden');
            document.getElementById('btn-eliminar').dataset.id = id;
            document.getElementById('evento-modal').classList.remove('hidden');
        });
}

function cerrarModal() {
    document.getElementById('evento-modal').classList.add('hidden');
}

function eliminarEvento() {
    const id = document.getElementById('btn-eliminar').dataset.id;
    if (!confirm('¿Eliminar este evento?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("calendario.destroy", ":id") }}'.replace(':id', id);
    form.innerHTML = '@csrf @method("DELETE")';
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
