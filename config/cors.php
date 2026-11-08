<?php

/*
 * CORS cerrado: el sistema se usa desde su propio dominio, no desde páginas ajenas.
 * Si hay un front separado (app móvil web, panel aparte), listar su origen exacto en
 * CORS_ALLOWED_ORIGINS separado por comas. Nunca "*".
 */
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    // La API usa tokens Bearer, no cookies entre orígenes.
    'supports_credentials' => false,
];
