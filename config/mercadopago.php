<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mercado Pago — cobros con QR / Point
    |--------------------------------------------------------------------------
    |
    | driver = null (default): simula los cobros (se aprueban al instante) para
    | poder probar el flujo del POS sin credenciales.
    | driver = mp: usa la API real. Necesitás el access token de producción y
    | dar de alta la caja (POS) en tu cuenta.
    |
    */

    'driver' => env('MP_DRIVER', 'null'), // null | mp

    'access_token' => env('MP_ACCESS_TOKEN'),

    'user_id' => env('MP_USER_ID'),

    // Identificador externo de la caja/sucursal (QR dinámico "in store").
    'external_pos_id' => env('MP_EXTERNAL_POS_ID', 'CAJA01'),

    'store_id' => env('MP_STORE_ID'),

    'webhook_secret' => env('MP_WEBHOOK_SECRET'),

    'base_url' => 'https://api.mercadopago.com',

    // Segundos que el POS espera la aprobación antes de dar timeout.
    'timeout_cobro' => (int) env('MP_TIMEOUT_COBRO', 120),
];
