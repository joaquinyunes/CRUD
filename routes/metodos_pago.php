<?php

use App\Http\Controllers\MetodoPagoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('metodos-pago')->name('metodos-pago.')->group(function () {
    Route::get('/', [MetodoPagoController::class, 'index'])
        ->middleware('permiso:configuracion.ver')
        ->name('index');

    Route::post('/', [MetodoPagoController::class, 'store'])
        ->middleware('permiso:configuracion.editar')
        ->name('store');

    Route::put('/{metodo_pago}', [MetodoPagoController::class, 'update'])
        ->middleware('permiso:configuracion.editar')
        ->name('update');

    Route::delete('/{metodo_pago}', [MetodoPagoController::class, 'destroy'])
        ->middleware('permiso:configuracion.editar')
        ->name('destroy');
});
