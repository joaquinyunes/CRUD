<?php

use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('productos')->name('productos.')->group(function () {
    Route::get('/', [ProductoController::class, 'index'])
        ->middleware('permiso:productos.ver')
        ->name('index');

    Route::get('/crear', [ProductoController::class, 'create'])
        ->middleware('permiso:productos.crear')
        ->name('create');

    Route::post('/', [ProductoController::class, 'store'])
        ->middleware('permiso:productos.crear')
        ->name('store');

    Route::get('/{producto}/editar', [ProductoController::class, 'edit'])
        ->middleware('permiso:productos.editar')
        ->name('edit');

    Route::put('/{producto}', [ProductoController::class, 'update'])
        ->middleware('permiso:productos.editar')
        ->name('update');

    Route::delete('/{producto}', [ProductoController::class, 'destroy'])
        ->middleware('permiso:productos.eliminar')
        ->name('destroy');

    Route::post('/{producto}/duplicar', [ProductoController::class, 'duplicar'])
        ->middleware('permiso:productos.crear')
        ->name('duplicar');
});
