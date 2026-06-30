<?php

use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('ventas')->name('ventas.')->group(function () {
    Route::get('/', [VentaController::class, 'index'])
        ->middleware('permiso:ventas.ver')
        ->name('index');

    Route::get('/crear', [VentaController::class, 'create'])
        ->middleware('permiso:ventas.crear')
        ->name('create');

    Route::post('/', [VentaController::class, 'store'])
        ->middleware('permiso:ventas.crear')
        ->name('store');

    Route::get('/{venta}', [VentaController::class, 'show'])
        ->middleware('permiso:ventas.ver')
        ->name('show');

    Route::get('/{venta}/editar', [VentaController::class, 'edit'])
        ->middleware('permiso:ventas.editar')
        ->name('edit');

    Route::put('/{venta}', [VentaController::class, 'update'])
        ->middleware('permiso:ventas.editar')
        ->name('update');

    Route::delete('/{venta}', [VentaController::class, 'destroy'])
        ->middleware('permiso:ventas.eliminar')
        ->name('destroy');
});
