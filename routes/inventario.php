<?php

use App\Http\Controllers\MermaController;
use App\Http\Controllers\RecuentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permiso:stock.ajustar'])->group(function () {
    Route::prefix('mermas')->name('mermas.')->group(function () {
        Route::get('/', [MermaController::class, 'index'])->name('index');
        Route::post('/', [MermaController::class, 'store'])->name('store');
    });

    Route::prefix('recuentos')->name('recuentos.')->group(function () {
        Route::get('/', [RecuentoController::class, 'index'])->name('index');
        Route::get('/crear', [RecuentoController::class, 'create'])->name('create');
        Route::post('/', [RecuentoController::class, 'store'])->name('store');
        Route::get('/{recuento}', [RecuentoController::class, 'show'])->name('show');
        Route::put('/{recuento}/conteo', [RecuentoController::class, 'guardar'])->name('guardar');
        Route::post('/{recuento}/aplicar', [RecuentoController::class, 'aplicar'])->name('aplicar');
    });
});
