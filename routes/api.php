<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CompraController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\VentaController;
use Illuminate\Support\Facades\Route;

/*
 * La API expone los mismos casos de uso que la interfaz web y, por lo tanto,
 * exige los mismos permisos: un token pertenece a un usuario, y ese usuario
 * tiene un rol. Un token de un usuario de solo lectura no puede escribir.
 */

/**
 * Declara un recurso de API con el permiso que corresponde a cada verbo.
 *
 * Los nombres de ruta llevan el prefijo "api." para no chocar con los de la
 * interfaz web, que ya ocupan "productos.index" y compañía.
 */
$recurso = function (string $uri, string $controller, string $modulo, bool $sinActualizar = false): void {
    $excepto = $sinActualizar ? ['edit', 'create', 'update'] : ['edit', 'create'];

    $nombres = [];
    foreach (['index', 'store', 'show', 'update', 'destroy'] as $accion) {
        $nombres[$accion] = "api.{$uri}.{$accion}";
    }

    Route::apiResource($uri, $controller)
        ->except($excepto)
        ->names($nombres)
        ->middlewareFor('index', "permiso:{$modulo}.ver")
        ->middlewareFor('show', "permiso:{$modulo}.ver")
        ->middlewareFor('store', "permiso:{$modulo}.crear")
        ->middlewareFor('update', "permiso:{$modulo}.editar")
        ->middlewareFor('destroy', "permiso:{$modulo}.eliminar");
};

// Auth (público). Con límite propio: es la única puerta que no pide token.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () use ($recurso) {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    $recurso('productos', ProductoController::class, 'productos');
    $recurso('clientes', ClienteController::class, 'clientes');
    $recurso('categorias', CategoriaController::class, 'categorias');
    $recurso('proveedores', ProveedorController::class, 'proveedores');
    $recurso('ventas', VentaController::class, 'ventas', sinActualizar: true);
    $recurso('compras', CompraController::class, 'compras', sinActualizar: true);
});
