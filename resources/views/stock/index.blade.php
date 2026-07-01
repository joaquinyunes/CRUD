@extends('layouts.app')

@section('page_title', 'Movimientos de Stock')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Movimientos de Stock</h1>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('stock.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Producto</label>
                    <select name="producto_id" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todos</option>
                        @foreach($productos as $prod)
                            <option value="{{ $prod->id }}" {{ request('producto_id') == $prod->id ? 'selected' : '' }}>
                                {{ $prod->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Tipo</label>
                    <select name="tipo" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todos</option>
                        <option value="entrada"   {{ request('tipo') === 'entrada'   ? 'selected' : '' }}>Entrada</option>
                        <option value="salida"    {{ request('tipo') === 'salida'    ? 'selected' : '' }}>Salida</option>
                        <option value="ajuste"    {{ request('tipo') === 'ajuste'    ? 'selected' : '' }}>Ajuste</option>
                        <option value="devolucion" {{ request('tipo') === 'devolucion' ? 'selected' : '' }}>Devolución</option>
                    </select>
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

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                @if(request()->hasAny(['producto_id','tipo','fecha_desde','fecha_hasta']))
                    <a href="{{ route('stock.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Producto</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tipo</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cantidad</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Referencia</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($movimientos as $mov)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $mov->producto->nombre ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($mov->tipo === 'entrada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Entrada</span>
                                @elseif($mov->tipo === 'salida')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Salida</span>
                                @elseif($mov->tipo === 'ajuste')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Ajuste</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Devolución</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-center font-medium
                                {{ $mov->tipo === 'salida' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $mov->tipo === 'salida' ? '-' : '+' }}{{ $mov->cantidad }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                @if($mov->referencia_tipo && $mov->referencia_id)
                                    {{ ucfirst($mov->referencia_tipo) }} #{{ $mov->referencia_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $mov->user->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron movimientos de stock.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movimientos->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $movimientos->firstItem() }}–{{ $movimientos->lastItem() }}
                    de {{ $movimientos->total() }} movimientos
                </span>
                {{ $movimientos->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
