<?php

/**
 * Índices de MongoDB: reemplazan a las migraciones SQL (claves únicas y de consulta).
 *
 * Un solo archivo para todo el sistema. Formato: colección => lista de
 * [claves, opciones]. `App\Support\Mongo\Indices::crear()` lo aplica y se puede
 * correr las veces que haga falta (`php artisan mongo:indices`).
 *
 * Los índices únicos son la red de seguridad de verdad: el contador y la
 * transacción evitan los repetidos, y si algo falla igual, la base lo rechaza.
 * Cada fase que porte modelos agrega acá sus colecciones.
 */

return [

    // ---- Fase 0/1: catálogo, ventas, stock y caja ----

    'productos' => [
        [['codigo' => 1], ['unique' => true, 'name' => 'codigo_unico']],
        [['codigo_barra' => 1], ['name' => 'codigo_barra']],
        [['categoria_id' => 1], ['name' => 'categoria_id']],
        [['estado' => 1], ['name'       => 'estado']],
    ],

    'clientes' => [
        [['estado' => 1], ['name' => 'estado']],
    ],

    'metodos_pago' => [
        [['codigo' => 1], ['name' => 'codigo']],
    ],

    'ventas' => [
        [['numero' => 1], ['unique' => true, 'name' => 'numero_unico']],
        [['estado' => 1, 'fecha' => 1], ['name' => 'estado_fecha']],
        [['fecha' => 1], ['name'                => 'fecha']],
        [['cliente_id' => 1], ['name'           => 'cliente_id']],
        [['user_id' => 1], ['name'              => 'user_id']],
        [['deposito_id' => 1], ['name'          => 'deposito_id']],
    ],

    'ventas_detalle' => [
        [['venta_id' => 1], ['name' => 'venta_id']],
        [['producto_id' => 1], ['name' => 'producto_id']],
    ],

    'ventas_pago' => [
        [['venta_id' => 1], ['name' => 'venta_id']],
        [['metodo_pago_id' => 1], ['name' => 'metodo_pago_id']],
    ],

    'movimientos_stock' => [
        [['producto_id' => 1, 'created_at' => -1], ['name' => 'producto_fecha']],
        [['referencia_tipo' => 1, 'referencia_id' => 1], ['name' => 'referencia']],
        [['deposito_id' => 1], ['name'                           => 'deposito_id']],
    ],

    // El `_id` ("producto:deposito") ya es único; esto sirve para "todo el stock de un depósito".
    'stock_deposito' => [
        [['deposito_id' => 1], ['name' => 'deposito_id']],
        [['producto_id' => 1], ['name' => 'producto_id']],
    ],

    'caja_sesiones' => [
        [['user_id' => 1, 'estado' => 1], ['name' => 'usuario_estado']],
    ],

    'caja_movimientos' => [
        [['caja_sesion_id' => 1, 'tipo' => 1], ['name' => 'sesion_tipo']],
    ],

    // ---- Fase 2: catálogo, personas y seguridad ----

    'users' => [
        [['email' => 1], ['unique' => true, 'name' => 'email_unico']],
        [['role_id' => 1], ['name' => 'role_id']],
    ],

    'roles' => [
        [['nombre' => 1], ['name' => 'nombre']],
    ],

    'permissions' => [
        [['clave' => 1], ['unique' => true, 'name' => 'clave_unica']],
    ],

    'personal_access_tokens' => [
        [['token' => 1], ['unique' => true, 'name' => 'token_unico']],
        [['tokenable_type' => 1, 'tokenable_id' => 1], ['name' => 'tokenable']],
    ],

    'categorias' => [
        [['nombre' => 1], ['name' => 'nombre']],
    ],

    'unidades_medida' => [
        [['nombre' => 1], ['name' => 'nombre']],
    ],

    'proveedores' => [
        [['nombre' => 1], ['name' => 'nombre']],
        [['cuit' => 1], ['name' => 'cuit']],
    ],

    // `contadores` usa `_id` ("id:ventas", "num:ventas:VTA"): único por construcción.
];
