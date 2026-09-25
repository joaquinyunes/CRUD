<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('usuarios')->name('usuarios.')->group(function () {

    Route::get('/', [UserController::class, 'index'])
        ->middleware('permiso:usuarios.ver')
        ->name('index');

    Route::get('/crear', [UserController::class, 'create'])
        ->middleware('permiso:usuarios.crear')
        ->name('create');

    Route::post('/', [UserController::class, 'store'])
        ->middleware('permiso:usuarios.crear')
        ->name('store');

    Route::get('/{usuario}/editar', [UserController::class, 'edit'])
        ->middleware('permiso:usuarios.editar')
        ->name('edit');

    Route::put('/{usuario}', [UserController::class, 'update'])
        ->middleware('permiso:usuarios.editar')
        ->name('update');

    Route::put('/{usuario}/rol', [UserController::class, 'asignarRol'])
        ->middleware('permiso:usuarios.editar')
        ->name('asignar-rol');

    Route::delete('/{usuario}', [UserController::class, 'destroy'])
        ->middleware('permiso:usuarios.eliminar')
        ->name('destroy');
});
