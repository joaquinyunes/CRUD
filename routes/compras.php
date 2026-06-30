<?php

use App\Http\Controllers\CompraController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('compras')->name('compras.')->group(function () {
    Route::get('/', [CompraController::class, 'index'])
        ->middleware('permiso:compras.ver')
        ->name('index');

    Route::get('/crear', [CompraController::class, 'create'])
        ->middleware('permiso:compras.crear')
        ->name('create');

    Route::post('/', [CompraController::class, 'store'])
        ->middleware('permiso:compras.crear')
        ->name('store');

    Route::get('/{compra}', [CompraController::class, 'show'])
        ->middleware('permiso:compras.ver')
        ->name('show');

    Route::get('/{compra}/editar', [CompraController::class, 'edit'])
        ->middleware('permiso:compras.editar')
        ->name('edit');

    Route::put('/{compra}', [CompraController::class, 'update'])
        ->middleware('permiso:compras.editar')
        ->name('update');

    Route::delete('/{compra}', [CompraController::class, 'destroy'])
        ->middleware('permiso:compras.eliminar')
        ->name('destroy');
});
