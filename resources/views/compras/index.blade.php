@extends('layouts.app')

@section('page_title', 'Compras')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Compras</h1>

            @if (auth()->user()->role?->tienePermiso('compras.crear'))
                <a href="{{ route('compras.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nueva compra
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('compras.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Número o proveedor…"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Fecha desde</label>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Fecha hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Estado</label>
                    <select name="estado" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todos</option>
                        <option value="pendiente"  {{ request('estado') === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                        <option value="completada" {{ request('estado') === 'completada' ? 'selected' : '' }}>Completada</option>
                        <option value="cancelada"  {{ request('estado') === 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                @if(request()->hasAny(['buscar','fecha_desde','fecha_hasta','estado']))
                    <a href="{{ route('compras.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Número</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Proveedor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($compras as $compra)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $compra->numero }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $compra->proveedor->nombre ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $compra->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">${{ number_format($compra->total, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($compra->estado === 'completada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Completada</span>
                                @elseif($compra->estado === 'pendiente')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pendiente</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Cancelada</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (auth()->user()->role?->tienePermiso('compras.ver'))
                                        <a href="{{ route('compras.show', $compra) }}" class="text-gray-600 hover:underline dark:text-gray-400">Ver</a>
                                    @endif

                                    @if (auth()->user()->role?->tienePermiso('compras.editar'))
                                        <a href="{{ route('compras.edit', $compra) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    @endif

                                    @if (auth()->user()->role?->tienePermiso('compras.eliminar'))
                                        <form method="POST" action="{{ route('compras.destroy', $compra) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline"
                                                    onclick="return confirm('¿Eliminar esta compra?')">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron compras.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($compras->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $compras->firstItem() }}–{{ $compras->lastItem() }}
                    de {{ $compras->total() }} compras
                </span>
                {{ $compras->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
