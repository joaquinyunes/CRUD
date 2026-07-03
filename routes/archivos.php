<?php

use App\Http\Controllers\ArchivoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('archivos')->name('archivos.')->group(function () {
    Route::get('/', [ArchivoController::class, 'index'])
        ->name('index');

    Route::post('/', [ArchivoController::class, 'store'])
        ->name('store');

    Route::get('/{archivo}/descargar', [ArchivoController::class, 'download'])
        ->name('download');

    Route::delete('/{archivo}', [ArchivoController::class, 'destroy'])
        ->name('destroy');
});
