<?php

use App\Http\Controllers\OrdenCompraController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('ordenes-compra')->name('ordenes-compra.')->group(function () {
    Route::get('/', [OrdenCompraController::class, 'index'])->middleware('permiso:ordenes_compra.ver')->name('index');
    Route::get('/crear', [OrdenCompraController::class, 'create'])->middleware('permiso:ordenes_compra.crear')->name('create');
    Route::post('/', [OrdenCompraController::class, 'store'])->middleware('permiso:ordenes_compra.crear')->name('store');
    Route::get('/{ordenCompra}', [OrdenCompraController::class, 'show'])->middleware('permiso:ordenes_compra.ver')->name('show');
    Route::get('/{ordenCompra}/editar', [OrdenCompraController::class, 'edit'])->middleware('permiso:ordenes_compra.editar')->name('edit');
    Route::put('/{ordenCompra}', [OrdenCompraController::class, 'update'])->middleware('permiso:ordenes_compra.editar')->name('update');
    Route::delete('/{ordenCompra}', [OrdenCompraController::class, 'destroy'])->middleware('permiso:ordenes_compra.eliminar')->name('destroy');
    Route::get('/{ordenCompra}/recibir', [OrdenCompraController::class, 'recepcionForm'])->middleware('permiso:ordenes_compra.recibir')->name('recibir.form');
    Route::post('/{ordenCompra}/recibir', [OrdenCompraController::class, 'recibir'])->middleware('permiso:ordenes_compra.recibir')->name('recibir');
    Route::post('/{ordenCompra}/facturar', [OrdenCompraController::class, 'facturar'])->middleware('permiso:ordenes_compra.recibir,compras.crear')->name('facturar');
});
