<?php

use App\Http\Controllers\UnidadMedidaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('unidades-medida')->name('unidades-medida.')->group(function () {
    Route::get('/', [UnidadMedidaController::class, 'index'])
        ->middleware('permiso:configuracion.ver')
        ->name('index');

    Route::post('/', [UnidadMedidaController::class, 'store'])
        ->middleware('permiso:configuracion.editar')
        ->name('store');

    Route::put('/{unidad_medida}', [UnidadMedidaController::class, 'update'])
        ->middleware('permiso:configuracion.editar')
        ->name('update');

    Route::delete('/{unidad_medida}', [UnidadMedidaController::class, 'destroy'])
        ->middleware('permiso:configuracion.editar')
        ->name('destroy');
});
