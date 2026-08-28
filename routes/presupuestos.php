<?php

use App\Http\Controllers\PresupuestoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('presupuestos')->name('presupuestos.')->group(function () {
    Route::get('/', [PresupuestoController::class, 'index'])->middleware('permiso:presupuestos.ver')->name('index');
    Route::get('/crear', [PresupuestoController::class, 'create'])->middleware('permiso:presupuestos.crear')->name('create');
    Route::post('/', [PresupuestoController::class, 'store'])->middleware('permiso:presupuestos.crear')->name('store');
    Route::get('/{presupuesto}', [PresupuestoController::class, 'show'])->middleware('permiso:presupuestos.ver')->name('show');
    Route::get('/{presupuesto}/editar', [PresupuestoController::class, 'edit'])->middleware('permiso:presupuestos.editar')->name('edit');
    Route::put('/{presupuesto}', [PresupuestoController::class, 'update'])->middleware('permiso:presupuestos.editar')->name('update');
    Route::delete('/{presupuesto}', [PresupuestoController::class, 'destroy'])->middleware('permiso:presupuestos.eliminar')->name('destroy');
    Route::post('/{presupuesto}/convertir', [PresupuestoController::class, 'convertir'])->middleware('permiso:presupuestos.convertir,ventas.crear')->name('convertir');
});
