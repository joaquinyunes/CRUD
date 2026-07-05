<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

// Auth (público)
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con token
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Productos
    Route::apiResource('productos', ProductoController::class)
        ->except(['edit', 'create'])
        ->names([
            'index'   => 'api.productos.index',
            'store'   => 'api.productos.store',
            'show'    => 'api.productos.show',
            'update'  => 'api.productos.update',
            'destroy' => 'api.productos.destroy',
        ]);

    // Clientes
    Route::apiResource('clientes', ClienteController::class)
        ->except(['edit', 'create'])
        ->names([
            'index'   => 'api.clientes.index',
            'store'   => 'api.clientes.store',
            'show'    => 'api.clientes.show',
            'update'  => 'api.clientes.update',
            'destroy' => 'api.clientes.destroy',
        ]);

    // Categorías
    Route::apiResource('categorias', CategoriaController::class)
        ->except(['edit', 'create'])
        ->names([
            'index'   => 'api.categorias.index',
            'store'   => 'api.categorias.store',
            'show'    => 'api.categorias.show',
            'update'  => 'api.categorias.update',
            'destroy' => 'api.categorias.destroy',
        ]);

    // Proveedores
    Route::apiResource('proveedores', ProveedorController::class)
        ->except(['edit', 'create'])
        ->names([
            'index'   => 'api.proveedores.index',
            'store'   => 'api.proveedores.store',
            'show'    => 'api.proveedores.show',
            'update'  => 'api.proveedores.update',
            'destroy' => 'api.proveedores.destroy',
        ]);

    // Ventas
    Route::apiResource('ventas', VentaController::class)
        ->except(['edit', 'create', 'update'])
        ->names([
            'index'   => 'api.ventas.index',
            'store'   => 'api.ventas.store',
            'show'    => 'api.ventas.show',
            'destroy' => 'api.ventas.destroy',
        ]);

    // Compras
    Route::apiResource('compras', CompraController::class)
        ->except(['edit', 'create', 'update'])
        ->names([
            'index'   => 'api.compras.index',
            'store'   => 'api.compras.store',
            'show'    => 'api.compras.show',
            'destroy' => 'api.compras.destroy',
        ]);
});
