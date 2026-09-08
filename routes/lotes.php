<?php

use App\Http\Controllers\LoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('lotes')->name('lotes.')->group(function () {
    Route::get('/', [LoteController::class, 'index'])
        ->middleware('permiso:stock.ver')->name('index');

    Route::post('/', [LoteController::class, 'store'])
        ->middleware('permiso:stock.ajustar')->name('store');
});
