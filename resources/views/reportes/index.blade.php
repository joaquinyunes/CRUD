@extends('layouts.app')

@section('page_title', 'Reportes')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Reportes</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('reportes.ventas-periodo') }}"
               class="block bg-white dark:bg-gray-800 shadow rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Ventas por período</div>
                <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    ${{ number_format($ventasHoy, 2, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-gray-400">Hoy</div>
            </a>

            <a href="{{ route('reportes.productos-vendidos') }}"
               class="block bg-white dark:bg-gray-800 shadow rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Productos más vendidos</div>
                <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    ${{ number_format($ventasMes, 2, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-gray-400">Este mes · {{ $totalVentasMes }} ventas</div>
            </a>

            <a href="{{ route('reportes.mejores-clientes') }}"
               class="block bg-white dark:bg-gray-800 shadow rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Mejores clientes</div>
                <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    {{ $productosStockCritico + $productosAgotados }}
                </div>
                <div class="mt-1 text-xs text-gray-400">Productos con stock bajo</div>
            </a>

            <a href="{{ route('reportes.stock-critico') }}"
               class="block bg-white dark:bg-gray-800 shadow rounded-lg p-5 hover:shadow-md transition">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock crítico</div>
                <div class="mt-2 text-2xl font-semibold text-gray-800 dark:text-gray-100">
                    {{ $productosAgotados }}
                </div>
                <div class="mt-1 text-xs text-gray-400">Productos agotados</div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Accesos rápidos</h2>
                <div class="space-y-2">
                    <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'diario']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md">
                        → Ventas diarias (últimos 30 días)
                    </a>
                    <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'semanal']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md">
                        → Ventas semanales (últimas 12 semanas)
                    </a>
                    <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'mensual']) }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md">
                        → Ventas mensuales (últimos 12 meses)
                    </a>
                    <a href="{{ route('reportes.productos-vendidos') }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md">
                        → Top 10 productos más vendidos
                    </a>
                    <a href="{{ route('reportes.mejores-clientes') }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md">
                        → Top 10 mejores clientes
                    </a>
                    <a href="{{ route('reportes.stock-critico') }}"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md">
                        → Stock crítico y agotado
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Resumen del mes</h2>
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Ventas completadas</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $totalVentasMes }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Facturación total</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">${{ number_format($ventasMes, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Ticket promedio</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            ${{ $totalVentasMes > 0 ? number_format($ventasMes / $totalVentasMes, 2, ',', '.') : '0.00' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Stock crítico</dt>
                        <dd class="text-sm font-medium text-yellow-600 dark:text-yellow-400">{{ $productosStockCritico }} productos</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Productos agotados</dt>
                        <dd class="text-sm font-medium text-red-600 dark:text-red-400">{{ $productosAgotados }} productos</dd>
                    </div>
                </dl>
            </div>
        </div>

    </div>
</div>
@endsection
