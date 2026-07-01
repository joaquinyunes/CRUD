@extends('layouts.app')

@section('page_title', 'Productos más vendidos')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Productos más vendidos</h1>
            <a href="{{ route('reportes.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Volver
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <div class="flex gap-2">
                <a href="{{ route('reportes.productos-vendidos', ['limit' => 5]) }}"
                   class="px-4 py-2 text-sm rounded-md {{ $limit == 5 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-100 hover:bg-gray-300' }}">
                    Top 5
                </a>
                <a href="{{ route('reportes.productos-vendidos', ['limit' => 10]) }}"
                   class="px-4 py-2 text-sm rounded-md {{ $limit == 10 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-100 hover:bg-gray-300' }}">
                    Top 10
                </a>
                <a href="{{ route('reportes.productos-vendidos', ['limit' => 20]) }}"
                   class="px-4 py-2 text-sm rounded-md {{ $limit == 20 ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-100 hover:bg-gray-300' }}">
                    Top 20
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 w-12 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Código</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Producto</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unidades vendidas</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total facturado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($datos as $index => $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm text-center text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $item->codigo }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $item->nombre }}</td>
                            <td class="px-4 py-2 text-sm text-center font-medium text-gray-800 dark:text-gray-100">{{ $item->total_vendido }}</td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-gray-800 dark:text-gray-100">
                                ${{ number_format($item->total_facturado, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay ventas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($datos->count())
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Total</td>
                            <td class="px-4 py-2 text-sm text-center font-semibold text-gray-700 dark:text-gray-300">
                                {{ $datos->sum('total_vendido') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-semibold text-gray-700 dark:text-gray-300">
                                ${{ number_format($datos->sum('total_facturado'), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

    </div>
</div>
@endsection
