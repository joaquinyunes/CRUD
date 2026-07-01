@extends('layouts.app')

@section('page_title', 'Stock crítico')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Stock crítico</h1>
            <a href="{{ route('reportes.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Volver
            </a>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-4">
                Productos agotados ({{ $agotados->count() }})
            </h2>

            @if($agotados->count())
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Código</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Producto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Categoría</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Stock</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mínimo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($agotados as $prod)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $prod->codigo }}</td>
                                <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $prod->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $prod->categoria->nombre ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        0
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-center text-gray-500 dark:text-gray-400">{{ $prod->stock_minimo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay productos agotados.</p>
            @endif
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-sm font-semibold text-yellow-700 dark:text-yellow-400 mb-4">
                Productos con stock bajo ({{ $criticos->count() }})
            </h2>

            @if($criticos->count())
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Código</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Producto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Categoría</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Stock</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mínimo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($criticos as $prod)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $prod->codigo }}</td>
                                <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $prod->nombre }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $prod->categoria->nombre ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        {{ $prod->stock }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-center text-gray-500 dark:text-gray-400">{{ $prod->stock_minimo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay productos con stock crítico.</p>
            @endif
        </div>

    </div>
</div>
@endsection
