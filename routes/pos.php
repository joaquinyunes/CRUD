<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])
        ->middleware('permiso:pos.usar')->name('index');

    Route::get('/buscar', [PosController::class, 'buscar'])
        ->middleware('permiso:pos.usar')->name('buscar');

    Route::post('/vender', [PosController::class, 'store'])
        ->middleware('permiso:pos.usar')->name('store');

    Route::get('/ticket/{venta}', [PosController::class, 'ticket'])
        ->middleware('permiso:pos.usar')->name('ticket');
});
