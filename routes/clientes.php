<?php

use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('clientes')
    ->name('clientes.')
    ->group(function () {
        Route::get('/', [ClienteController::class, 'index'])
            ->middleware('permiso:clientes.ver')
            ->name('index');

        Route::get('/crear', [ClienteController::class, 'create'])
            ->middleware('permiso:clientes.crear')
            ->name('create');

        Route::post('/', [ClienteController::class, 'store'])
            ->middleware('permiso:clientes.crear')
            ->name('store');

        Route::get('/{cliente}/editar', [ClienteController::class, 'edit'])
            ->middleware('permiso:clientes.editar')
            ->name('edit');

        Route::put('/{cliente}', [ClienteController::class, 'update'])
            ->middleware('permiso:clientes.editar')
            ->name('update');

        Route::delete('/{cliente}', [ClienteController::class, 'destroy'])
            ->middleware('permiso:clientes.eliminar')
            ->name('destroy');
    });
