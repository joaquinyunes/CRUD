<?php

use App\Http\Controllers\DevolucionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/devoluciones', [DevolucionController::class, 'index'])
        ->middleware('permiso:devoluciones.ver')->name('devoluciones.index');

    Route::get('/devoluciones/{devolucion}', [DevolucionController::class, 'show'])
        ->middleware('permiso:devoluciones.ver')->name('devoluciones.show');

    Route::get('/ventas/{venta}/devolucion', [DevolucionController::class, 'createVenta'])
        ->middleware('permiso:devoluciones.crear')->name('devoluciones.venta.create');

    Route::post('/ventas/{venta}/devolucion', [DevolucionController::class, 'storeVenta'])
        ->middleware('permiso:devoluciones.crear')->name('devoluciones.venta.store');

    Route::get('/compras/{compra}/devolucion', [DevolucionController::class, 'createCompra'])
        ->middleware('permiso:devoluciones.crear')->name('devoluciones.compra.create');

    Route::post('/compras/{compra}/devolucion', [DevolucionController::class, 'storeCompra'])
        ->middleware('permiso:devoluciones.crear')->name('devoluciones.compra.store');
});
