<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('roles')->name('roles.')->group(function () {

    Route::get('/', [RoleController::class, 'index'])
        ->middleware('permiso:roles.ver')
        ->name('index');

    Route::get('/{role}/editar', [RoleController::class, 'edit'])
        ->middleware('permiso:roles.editar')
        ->name('edit');

    Route::put('/{role}', [RoleController::class, 'update'])
        ->middleware('permiso:roles.editar')
        ->name('update');
});
