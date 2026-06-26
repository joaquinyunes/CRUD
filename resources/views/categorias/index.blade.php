@extends('layouts.app')

@section('page_title', 'Categorías')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Categorías</h1>

            @if (auth()->user()->role?->tienePermiso('categorias.crear'))
                <a href="{{ route('categorias.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nueva categoría
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('categorias.index') }}" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ $buscar }}"
                           placeholder="Nombre de la categoría"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Estado</label>
                    <select name="estado" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="" @selected($estadoFiltro === '')>Todos</option>
                        <option value="activo" @selected($estadoFiltro === 'activo')>Activas</option>
                        <option value="inactivo" @selected($estadoFiltro === 'inactivo')>Inactivas</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                @if ($buscar || $estadoFiltro)
                    <a href="{{ route('categorias.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Descripción</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($categorias as $categoria)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $categoria->nombre }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $categoria->descripcion ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">
                                @if ($categoria->estado)
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">Activa</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Inactiva</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right text-sm space-x-3">
                                @if (auth()->user()->role?->tienePermiso('categorias.editar'))
                                    <a href="{{ route('categorias.edit', $categoria) }}" class="text-indigo-600 hover:underline">Editar</a>
                                @endif

                                @if ($categoria->estado && auth()->user()->role?->tienePermiso('categorias.eliminar'))
                                    <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" class="inline"
                                          onsubmit="return confirm('¿Desactivar esta categoría?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Desactivar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay categorías para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $categorias->links() }}
    </div>
</div>
@endsection
