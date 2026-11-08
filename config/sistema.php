<?php

/*
 * Primera cuenta de una instalación real. Las lee `php artisan sistema:instalar`
 * (que el contenedor corre al arrancar); en la demo no se usan.
 */
return [
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
