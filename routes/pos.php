<?php

use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

// Service worker del POS — servido desde la raíz para tener alcance total.
Route::get('/sw.js', function () {
    return response()->file(public_path('sw.js'), [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache',
    ]);
})->name('pos.sw');

Route::middleware(['auth', 'verified'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', [PosController::class, 'index'])
        ->middleware('permiso:pos.usar')->name('index');

    Route::get('/buscar', [PosController::class, 'buscar'])
        ->middleware('permiso:pos.usar')->name('buscar');

    Route::get('/catalogo', [PosController::class, 'catalogo'])
        ->middleware('permiso:pos.usar')->name('catalogo');

    Route::post('/cotizar', [PosController::class, 'cotizar'])
        ->middleware('permiso:pos.usar')->name('cotizar');

    Route::post('/vender', [PosController::class, 'store'])
        ->middleware('permiso:pos.usar')->name('store');

    Route::get('/ticket/{venta}', [PosController::class, 'ticket'])
        ->middleware('permiso:pos.usar')->name('ticket');
});
