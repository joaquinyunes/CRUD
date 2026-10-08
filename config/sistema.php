<?php

/*
 * Primera cuenta de una instalación real. Las lee `php artisan sistema:instalar`
 * (que el contenedor corre al arrancar); en la demo no se usan.
 */
return [
    'admin' => [
        'nombre'   => env('ADMIN_NAME', 'Administrador'),
        'email'    => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
