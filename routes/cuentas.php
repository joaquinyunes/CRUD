<?php

use App\Http\Controllers\CuentaCorrienteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('cuentas')->name('cuentas.')->group(function () {
    Route::get('/clientes', [CuentaCorrienteController::class, 'clientes'])
        ->middleware('permiso:cuentas.ver')->name('clientes');

    Route::get('/clientes/{cliente}', [CuentaCorrienteController::class, 'cliente'])
        ->middleware('permiso:cuentas.ver')->name('cliente');

    Route::post('/clientes/{cliente}/cobrar', [CuentaCorrienteController::class, 'cobrarCliente'])
        ->middleware('permiso:cuentas.cobrar')->name('cliente.cobrar');

    Route::get('/proveedores', [CuentaCorrienteController::class, 'proveedores'])
        ->middleware('permiso:cuentas.ver')->name('proveedores');

    Route::get('/proveedores/{proveedor}', [CuentaCorrienteController::class, 'proveedor'])
        ->middleware('permiso:cuentas.ver')->name('proveedor');

    Route::post('/proveedores/{proveedor}/pagar', [CuentaCorrienteController::class, 'pagarProveedor'])
        ->middleware('permiso:cuentas.cobrar')->name('proveedor.pagar');
});
