<?php

use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/', [ReporteController::class, 'index'])
        ->middleware('permiso:reportes.ver')
        ->name('index');

    Route::get('/ventas-por-periodo', [ReporteController::class, 'ventasPorPeriodo'])
        ->middleware('permiso:reportes.ver')
        ->name('ventas-periodo');

    Route::get('/productos-mas-vendidos', [ReporteController::class, 'productosMasVendidos'])
        ->middleware('permiso:reportes.ver')
        ->name('productos-vendidos');

    Route::get('/mejores-clientes', [ReporteController::class, 'mejoresClientes'])
        ->middleware('permiso:reportes.ver')
        ->name('mejores-clientes');

    Route::get('/stock-critico', [ReporteController::class, 'stockCritico'])
        ->middleware('permiso:reportes.ver')
        ->name('stock-critico');
});
