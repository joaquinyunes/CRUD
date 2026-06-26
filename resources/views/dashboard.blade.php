@extends('layouts.app')

@section('title', 'Dashboard — ' . config('app.name'))
@section('page_title', 'Dashboard')

@section('content')

<div class="mb-8">
    <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">Hoy</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ventas realizadas</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $ventasHoy }}</p>
            <p class="text-xs text-gray-400 mt-1">Disponible en fase 2.1</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ingresos del día</p>
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                ${{ number_format($ingresoHoy, 2, ',', '.') }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Disponible en fase 2.1</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Clientes nuevos</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $clientesNuevos }}</p>
            <p class="text-xs text-gray-400 mt-1">Disponible en fase 1.5</p>
        </div>

    </div>
</div>

<div>
    <h2 class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">Sistema</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Productos</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalProductos }}</p>
            <p class="text-xs text-gray-400 mt-1">Disponible en fase 1.4</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Usuarios</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalUsuarios }}</p>
            <p class="text-xs text-green-500 mt-1">✓ Dato real</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Stock crítico</p>
            <p class="text-3xl font-bold {{ $stockCritico > 0 ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">
                {{ $stockCritico }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Disponible en fase 1.4</p>
        </div>

    </div>
</div>

@endsection
