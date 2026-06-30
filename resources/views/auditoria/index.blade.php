@extends('layouts.app')

@section('page_title', 'Auditoría')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Auditoría</h1>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('auditoria.index') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Acción o modelo…"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Modelo</label>
                    <select name="modelo" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                        <option value="">Todos</option>
                        @foreach ($modelos as $m)
                            <option value="{{ $m }}" @selected(request('modelo') == $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Usuario</label>
                    <select name="usuario_id" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $usr)
                            <option value="{{ $usr->id }}" @selected(request('usuario_id') == $usr->id)>{{ $usr->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Desde</label>
                    <input type="date" name="desde" value="{{ request('desde') }}"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Hasta</label>
                    <input type="date" name="hasta" value="{{ request('hasta') }}"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <div class="sm:col-span-6 flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                        Filtrar
                    </button>
                    <a href="{{ route('auditoria.index') }}" class="text-sm text-gray-500 hover:underline self-center">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha/Hora</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Usuario</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">IP</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acción</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Modelo</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($registros as $registro)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200 whitespace-nowrap">
                                {{ $registro->created_at?->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                {{ $registro->user->name ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $registro->ip }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                @php
                                    $colores = [
                                        'created'  => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                        'updated'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                        'deleted'  => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                        'restored' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                                    ];
                                    $clase = $colores[$registro->accion] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs rounded-full font-medium {{ $clase }}">
                                    {{ $registro->accion }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $registro->modelo_afectado ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 text-right">
                                {{ $registro->modelo_id ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right">
                                <button type="button"
                                        class="text-indigo-600 hover:underline btn-ver-detalle"
                                        data-fecha="{{ $registro->created_at?->format('d/m/Y H:i:s') }}"
                                        data-usuario="{{ $registro->user->name ?? '—' }}"
                                        data-ip="{{ $registro->ip }}"
                                        data-accion="{{ $registro->accion }}"
                                        data-modelo="{{ $registro->modelo_afectado ?? '—' }}"
                                        data-modelo-id="{{ $registro->modelo_id ?? '—' }}"
                                        data-anterior="{{ json_encode($registro->valor_anterior) }}"
                                        data-nuevo="{{ json_encode($registro->valor_nuevo) }}">
                                    Ver detalle
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay registros de auditoría para los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $registros->links() }}

    </div>
</div>

<div id="modal-detalle" class="fixed inset-0 bg-gray-900/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-2xl w-full max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Detalle de auditoría</h2>
            <button type="button" class="cerrar-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">&times;</button>
        </div>

        <div class="px-5 py-4 space-y-4 text-sm">
            <div class="grid grid-cols-2 gap-3 text-gray-700 dark:text-gray-200">
                <div><span class="font-medium">Fecha:</span> <span class="detalle-fecha"></span></div>
                <div><span class="font-medium">Usuario:</span> <span class="detalle-usuario"></span></div>
                <div><span class="font-medium">IP:</span> <span class="detalle-ip"></span></div>
                <div><span class="font-medium">Acción:</span> <span class="detalle-accion"></span></div>
                <div><span class="font-medium">Modelo:</span> <span class="detalle-modelo"></span></div>
                <div><span class="font-medium">ID:</span> <span class="detalle-modelo-id"></span></div>
            </div>

            <div class="bloque-valor-anterior hidden">
                <h3 class="font-medium text-gray-700 dark:text-gray-200 mb-2">Valor anterior</h3>
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <tbody class="tabla-valor-anterior divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                </table>
            </div>

            <div class="bloque-valor-nuevo hidden">
                <h3 class="font-medium text-gray-700 dark:text-gray-200 mb-2">Valor nuevo</h3>
                <table class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <tbody class="tabla-valor-nuevo divide-y divide-gray-100 dark:divide-gray-700"></tbody>
                </table>
            </div>
        </div>

        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button type="button" class="cerrar-modal px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal-detalle');

    function pintarTabla(tbody, jsonString) {
        tbody.innerHTML = '';
        const bloque = tbody.closest('[class*="bloque-"]');

        let datos = null;
        try { datos = JSON.parse(jsonString); } catch (e) { datos = null; }

        if (!datos || typeof datos !== 'object' || Object.keys(datos).length === 0) {
            bloque.classList.add('hidden');
            return;
        }

        bloque.classList.remove('hidden');

        Object.entries(datos).forEach(([clave, valor]) => {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td class="px-3 py-2 font-medium text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/40 w-1/3">' + clave + '</td>' +
                '<td class="px-3 py-2 text-gray-700 dark:text-gray-200 break-all">' +
                (valor === null || valor === undefined ? '—' : (typeof valor === 'object' ? JSON.stringify(valor) : String(valor))) +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    document.querySelectorAll('.btn-ver-detalle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            modal.querySelector('.detalle-fecha').textContent = btn.dataset.fecha || '—';
            modal.querySelector('.detalle-usuario').textContent = btn.dataset.usuario || '—';
            modal.querySelector('.detalle-ip').textContent = btn.dataset.ip || '—';
            modal.querySelector('.detalle-accion').textContent = btn.dataset.accion || '—';
            modal.querySelector('.detalle-modelo').textContent = btn.dataset.modelo || '—';
            modal.querySelector('.detalle-modelo-id').textContent = btn.dataset.modeloId || '—';

            pintarTabla(modal.querySelector('.tabla-valor-anterior'), btn.dataset.anterior);
            pintarTabla(modal.querySelector('.tabla-valor-nuevo'), btn.dataset.nuevo);

            modal.classList.remove('hidden');
        });
    });

    modal.querySelectorAll('.cerrar-modal').forEach(function (btn) {
        btn.addEventListener('click', function () { modal.classList.add('hidden'); });
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.classList.add('hidden');
    });
});
</script>
@endsection
