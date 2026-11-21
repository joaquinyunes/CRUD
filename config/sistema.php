<?php

/*
 * Primera cuenta de una instalación real. Las lee `php artisan sistema:instalar`
 * (que el contenedor corre al arrancar); en la demo no se usan.
 */
return [
    // Proveedor de facturación: «interno» (comprobante sin CAE, el actual) o «simulado» (ensayo).
    // Un proveedor certificado real se agrega acá cuando el dueño elija uno con su contador.
    'facturacion' => ['proveedor' => env('FACTURACION_PROVEEDOR', 'interno')],

    // Entorno de demostración (V3): muestra un cartel «DEMO» en todas las pantallas.
    'demo' => filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN),

    // Los administradores no operan sin doble factor (S4). Se enciende solo en producción.
    'doble_factor_admin_obligatorio' => filter_var(
        env('DOBLE_FACTOR_ADMIN_OBLIGATORIO', env('APP_ENV') === 'production'),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'admin' => [
        'nombre'   => env('ADMIN_NAME', 'Administrador'),
        'email'    => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
