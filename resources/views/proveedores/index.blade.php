@extends('layouts.app')

@section('page_title', 'Proveedores')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Proveedores</h1>

            @if (auth()->user()->role?->tienePermiso('proveedores.crear'))
                <a href="{{ route('proveedores.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nuevo proveedor
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('proveedores.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Nombre, CUIT o email…"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                @if(request()->has('buscar'))
                    <a href="{{ route('proveedores.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CUIT</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Teléfono</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dirección</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($proveedores as $proveedor)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $proveedor->nombre }}</td>
                            <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $proveedor->cuit ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $proveedor->telefono ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $proveedor->email ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $proveedor->direccion ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (auth()->user()->role?->tienePermiso('proveedores.editar'))
                                        <a href="{{ route('proveedores.edit', $proveedor) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    @endif

                                    @if (auth()->user()->role?->tienePermiso('proveedores.eliminar'))
                                        <form method="POST" action="{{ route('proveedores.destroy', $proveedor) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline"
                                                    onclick="return confirm('¿Eliminar este proveedor?')">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron proveedores.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($proveedores->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $proveedores->firstItem() }}–{{ $proveedores->lastItem() }}
                    de {{ $proveedores->total() }} proveedores
                </span>
                {{ $proveedores->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
