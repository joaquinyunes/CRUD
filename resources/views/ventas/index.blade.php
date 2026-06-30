@extends('layouts.app')

@section('page_title', 'Ventas')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Ventas</h1>

            @if (auth()->user()->role?->tienePermiso('ventas.crear'))
                <a href="{{ route('ventas.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nueva venta
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Número o cliente…"
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
                    <a href="{{ route('ventas.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Número</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cliente</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vendedor</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($ventas as $venta)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $venta->numero }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">
                                {{ $venta->cliente->nombre ?? '—' }} {{ $venta->cliente->apellido ?? '' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $venta->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">${{ number_format($venta->total, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($venta->estado === 'completada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Completada</span>
                                @elseif($venta->estado === 'pendiente')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pendiente</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Cancelada</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $venta->user->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (auth()->user()->role?->tienePermiso('ventas.ver'))
                                        <a href="{{ route('ventas.show', $venta) }}" class="text-gray-600 hover:underline dark:text-gray-400">Ver</a>
                                    @endif

                                    @if (auth()->user()->role?->tienePermiso('ventas.editar'))
                                        <a href="{{ route('ventas.edit', $venta) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    @endif

                                    @if (auth()->user()->role?->tienePermiso('ventas.eliminar'))
                                        <form method="POST" action="{{ route('ventas.destroy', $venta) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline"
                                                    onclick="return confirm('¿Eliminar esta venta?')">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron ventas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ventas->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $ventas->firstItem() }}–{{ $ventas->lastItem() }}
                    de {{ $ventas->total() }} ventas
                </span>
                {{ $ventas->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
