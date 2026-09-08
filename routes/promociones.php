<?php

use App\Http\Controllers\ListaPrecioController;
use App\Http\Controllers\PromocionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('promociones')->name('promociones.')->middleware('permiso:promociones.gestionar')->group(function () {
        Route::get('/', [PromocionController::class, 'index'])->name('index');
        Route::get('/crear', [PromocionController::class, 'create'])->name('create');
        Route::post('/', [PromocionController::class, 'store'])->name('store');
        Route::get('/{promocion}/editar', [PromocionController::class, 'edit'])->name('edit');
        Route::put('/{promocion}', [PromocionController::class, 'update'])->name('update');
        Route::delete('/{promocion}', [PromocionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('listas-precio')->name('listas-precio.')->middleware('permiso:promociones.gestionar')->group(function () {
        Route::get('/', [ListaPrecioController::class, 'index'])->name('index');
        Route::post('/', [ListaPrecioController::class, 'store'])->name('store');
        Route::put('/{lista_precio}', [ListaPrecioController::class, 'update'])->name('update');
        Route::delete('/{lista_precio}', [ListaPrecioController::class, 'destroy'])->name('destroy');
    });
});
