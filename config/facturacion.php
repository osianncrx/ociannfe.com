<?php

declare(strict_types=1);

return [
    'storage_path' => env('FE_STORAGE_PATH', storage_path('app/comprobantes')),
    'storage_type' => env('FE_STORAGE_TYPE', 'local'),
    'crypto_key' => env('FE_CRYPTO_KEY', ''),
    'callback_url' => env('FE_CALLBACK_URL', ''),
    'proveedor_sistemas' => env('FE_PROVEEDOR_SISTEMAS', '3102877461'),
];
