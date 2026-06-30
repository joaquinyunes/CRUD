<?php

use App\Http\Controllers\ProveedorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('proveedores')->name('proveedores.')->group(function () {
    Route::get('/', [ProveedorController::class, 'index'])
        ->middleware('permiso:proveedores.ver')
        ->name('index');

    Route::get('/crear', [ProveedorController::class, 'create'])
        ->middleware('permiso:proveedores.crear')
        ->name('create');

    Route::post('/', [ProveedorController::class, 'store'])
        ->middleware('permiso:proveedores.crear')
        ->name('store');

    Route::get('/{proveedor}/editar', [ProveedorController::class, 'edit'])
        ->middleware('permiso:proveedores.editar')
        ->name('edit');

    Route::put('/{proveedor}', [ProveedorController::class, 'update'])
        ->middleware('permiso:proveedores.editar')
        ->name('update');

    Route::delete('/{proveedor}', [ProveedorController::class, 'destroy'])
        ->middleware('permiso:proveedores.eliminar')
        ->name('destroy');
});
