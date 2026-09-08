<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Facturación electrónica AFIP / ARCA (WSFEv1)
    |--------------------------------------------------------------------------
    |
    | Con `driver = null` (default) el sistema genera comprobantes SIMULADOS
    | (CAE ficticio) para poder operar sin certificado. Para facturar de verdad:
    |   1. Generá el certificado en ARCA y descargá el .crt + .key.
    |   2. Poné AFIP_DRIVER=wsfe y completá cuit / cert / key / punto_venta.
    |   3. Elegí ambiente: homologacion (testing) o produccion.
    |
    */

    'driver' => env('AFIP_DRIVER', 'null'), // null | wsfe

    'ambiente' => env('AFIP_AMBIENTE', 'homologacion'), // homologacion | produccion

    'cuit' => env('AFIP_CUIT'),

    'punto_venta' => (int) env('AFIP_PUNTO_VENTA', 1),

    'cert' => env('AFIP_CERT_PATH', storage_path('app/afip/cert.crt')),
    'key' => env('AFIP_KEY_PATH', storage_path('app/afip/private.key')),

    // Carpeta donde se cachea el token WSAA (TA.xml).
    'ta_path' => storage_path('app/afip'),

    'endpoints' => [
        'homologacion' => [
            'wsaa' => 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms',
            'wsfe' => 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL',
        ],
        'produccion' => [
            'wsaa' => 'https://wsaa.afip.gov.ar/ws/services/LoginCms',
            'wsfe' => 'https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL',
        ],
    ],

    // Comprobante por defecto para consumidor final.
    'comprobante_default' => (int) env('AFIP_COMPROBANTE_DEFAULT', 6), // 1=A 6=B 11=C

    // % de IVA que factura el negocio (para el detalle de alícuotas).
    'iva_alicuota_id' => (int) env('AFIP_IVA_ALICUOTA_ID', 5), // 5 = 21%
];
