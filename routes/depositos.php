<?php

use App\Http\Controllers\DepositoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('depositos')->name('depositos.')->group(function () {
    Route::get('/', [DepositoController::class, 'index'])->middleware('permiso:depositos.ver,stock.ver')->name('index');
    Route::get('/transferir', [DepositoController::class, 'transferForm'])->middleware('permiso:depositos.gestionar,stock.ajustar')->name('transferir.form');
    Route::post('/transferir', [DepositoController::class, 'transferir'])->middleware('permiso:depositos.gestionar,stock.ajustar')->name('transferir');
    Route::get('/crear', [DepositoController::class, 'create'])->middleware('permiso:depositos.gestionar')->name('create');
    Route::post('/', [DepositoController::class, 'store'])->middleware('permiso:depositos.gestionar')->name('store');
    Route::get('/{deposito}/stock', [DepositoController::class, 'stock'])->middleware('permiso:depositos.ver,stock.ver')->name('stock');
    Route::get('/{deposito}/editar', [DepositoController::class, 'edit'])->middleware('permiso:depositos.gestionar')->name('edit');
    Route::put('/{deposito}', [DepositoController::class, 'update'])->middleware('permiso:depositos.gestionar')->name('update');
    Route::delete('/{deposito}', [DepositoController::class, 'destroy'])->middleware('permiso:depositos.gestionar')->name('destroy');
});
