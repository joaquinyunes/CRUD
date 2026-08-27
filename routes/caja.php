<?php

use App\Http\Controllers\CajaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('caja')->name('caja.')->group(function () {
    Route::get('/', [CajaController::class, 'index'])
        ->middleware('permiso:caja.ver')->name('index');

    Route::post('/abrir', [CajaController::class, 'abrir'])
        ->middleware('permiso:caja.operar')->name('abrir');

    Route::post('/movimiento', [CajaController::class, 'movimiento'])
        ->middleware('permiso:caja.operar')->name('movimiento');

    Route::post('/cerrar', [CajaController::class, 'cerrar'])
        ->middleware('permiso:caja.operar')->name('cerrar');
});
