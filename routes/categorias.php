<?php

use App\Http\Controllers\CategoriaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('categorias')->name('categorias.')->group(function () {
    Route::get('/', [CategoriaController::class, 'index'])
        ->middleware('permiso:categorias.ver')
        ->name('index');

    Route::get('/crear', [CategoriaController::class, 'create'])
        ->middleware('permiso:categorias.crear')
        ->name('create');

    Route::post('/', [CategoriaController::class, 'store'])
        ->middleware('permiso:categorias.crear')
        ->name('store');

    Route::get('/{categoria}/editar', [CategoriaController::class, 'edit'])
        ->middleware('permiso:categorias.editar')
        ->name('edit');

    Route::put('/{categoria}', [CategoriaController::class, 'update'])
        ->middleware('permiso:categorias.editar')
        ->name('update');

    Route::delete('/{categoria}', [CategoriaController::class, 'destroy'])
        ->middleware('permiso:categorias.eliminar')
        ->name('destroy');
});
