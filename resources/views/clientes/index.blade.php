@extends('layouts.app')

@section('page_title', 'Clientes')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Clientes</h1>

            @if (auth()->user()->role?->tienePermiso('clientes.crear'))
                <a href="{{ route('clientes.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nuevo cliente
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('clientes.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ $buscar }}"
                           placeholder="Nombre, apellido o documento…"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Estado</label>
                    <select name="estado" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="activo" @selected($estado === 'activo')>Activos</option>
                        <option value="archivado" @selected($estado === 'archivado')>Archivados</option>
                        <option value="todos" @selected($estado === 'todos')>Todos</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                <a href="{{ route('clientes.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre y Apellido</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Documento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Teléfono</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($clientes as $cliente)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                                {{ $cliente->nombre }} {{ $cliente->apellido }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $cliente->documento ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $cliente->email ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $cliente->telefono ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">
                                @if ($cliente->estado === 'activo')
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Archivado</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right text-sm space-x-3">
                                @if (auth()->user()->role?->tienePermiso('clientes.editar'))
                                    <a href="{{ route('clientes.edit', $cliente) }}" class="text-indigo-600 hover:underline">Editar</a>
                                @endif

                                @if ($cliente->estado === 'activo' && auth()->user()->role?->tienePermiso('clientes.eliminar'))
                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" class="inline"
                                          onsubmit="return confirm('¿Archivar a {{ $cliente->nombre }} {{ $cliente->apellido }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Archivar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron clientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $clientes->links() }}

    </div>
</div>
@endsection
