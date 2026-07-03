@extends('layouts.app')

@section('page_title', 'Tareas')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Tareas</h1>

            <div class="flex items-center gap-2">
                <div class="flex bg-gray-200 dark:bg-gray-700 rounded-md p-0.5">
                    <a href="{{ route('tareas.index', ['vista' => 'lista']) }}"
                       class="px-3 py-1 text-sm rounded-md {{ request('vista', 'lista') === 'lista' ? 'bg-white dark:bg-gray-600 shadow text-gray-800 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
                        Lista
                    </a>
                    <a href="{{ route('tareas.index', ['vista' => 'kanban']) }}"
                       class="px-3 py-1 text-sm rounded-md {{ request('vista') === 'kanban' ? 'bg-white dark:bg-gray-600 shadow text-gray-800 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
                        Kanban
                    </a>
                </div>

                <a href="{{ route('tareas.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nueva tarea
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('tareas.index') }}" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="vista" value="{{ request('vista', 'lista') }}">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Título de la tarea…"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Estado</label>
                    <select name="estado" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todos</option>
                        <option value="pendiente"   {{ request('estado') === 'pendiente'   ? 'selected' : '' }}>Pendiente</option>
                        <option value="en_progreso" {{ request('estado') === 'en_progreso' ? 'selected' : '' }}>En progreso</option>
                        <option value="completada"  {{ request('estado') === 'completada'  ? 'selected' : '' }}>Completada</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Prioridad</label>
                    <select name="prioridad" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todas</option>
                        <option value="alta"  {{ request('prioridad') === 'alta'  ? 'selected' : '' }}>Alta</option>
                        <option value="media" {{ request('prioridad') === 'media' ? 'selected' : '' }}>Media</option>
                        <option value="baja"  {{ request('prioridad') === 'baja'  ? 'selected' : '' }}>Baja</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Asignada a</label>
                    <select name="asignada_a" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todas</option>
                        @foreach($usuarios as $usr)
                            <option value="{{ $usr->id }}" {{ request('asignada_a') == $usr->id ? 'selected' : '' }}>
                                {{ $usr->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                @if(request()->hasAny(['buscar','estado','prioridad','asignada_a']))
                    <a href="{{ route('tareas.index', ['vista' => request('vista', 'lista')]) }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Título</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Prioridad</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Asignada a</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha límite</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($tareas as $tarea)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 {{ $tarea->estaVencida ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">
                                {{ $tarea->titulo }}
                                @if($tarea->descripcion)
                                    <span class="text-xs text-gray-400 block truncate max-w-[300px]">{{ $tarea->descripcion }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($tarea->prioridad === 'alta')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Alta</span>
                                @elseif($tarea->prioridad === 'media')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Media</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Baja</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($tarea->estado === 'completada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Completada</span>
                                @elseif($tarea->estado === 'en_progreso')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">En progreso</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $tarea->asignada->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm {{ $tarea->estaVencida ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $tarea->fecha_limite ? $tarea->fecha_limite->format('d/m/Y') : '—' }}
                                @if($tarea->estaVencida)
                                    <span class="text-xs block">Vencida</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($tarea->estado !== 'completada')
                                        @if($tarea->estado === 'pendiente')
                                            <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'en_progreso']) }}">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:underline text-xs">Iniciar</button>
                                            </form>
                                        @else
                                            <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'completada']) }}">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:underline text-xs">Completar</button>
                                            </form>
                                        @endif
                                    @endif

                                    <a href="{{ route('tareas.edit', $tarea) }}" class="text-indigo-600 hover:underline">Editar</a>

                                    <form method="POST" action="{{ route('tareas.destroy', $tarea) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline"
                                                onclick="return confirm('¿Eliminar esta tarea?')">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron tareas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tareas->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $tareas->firstItem() }}–{{ $tareas->lastItem() }}
                    de {{ $tareas->total() }} tareas
                </span>
                {{ $tareas->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
