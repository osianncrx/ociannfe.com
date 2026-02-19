<?php

use Contica\Facturacion\Comprobante;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Crear la conexión a la base de datos
 */
$db = new mysqli(
    'localhost',
    'hacienda',
    'kjmc15981598',
    'hacienda'
);

/**
 * Inicializar la base de datos
 * Tambien para ejecutar migraciones después de una actualización
 */
//Storage::runMigrations($db);
$ajustes = [
    'storage_path' => '/www/wwwroot/hacienda.osiann.com/files', // ruta completa en donde guardar los comprobantes
    'crypto_key' => 'def0000062feacc74abef4a7714ac48a5e1e1ec2f01c8be6b1a005d84d53911f0d13aa069b30cf408419eeedd2de8abf3610e92341fb548319df21f6d1e521bf8936b65f',   // (opcional) Llave para encriptar datos de conexion
    'callback_url' => 'https://hacienda.osiann.com/tests/callback.php',  // (opcional) URL donde se recibe el callback
    'storage_type' => 'local', // 'local' o 's3' para el tipo de almacenaje
    's3_client_options' => [ // ajustes opcionales si se usa almacenaje s3
        'credentials' => [
            'key'    => 'llave',
            'secret' => 'secreto'
        ],
        'endpoint' => 'https://us-east-1.linodeobjects.com', // (opcional)
        'region' => 'region', // por ej, us-east-1
        'version' => 'latest', // version
    ],
    's3_bucket_name' => 'nombre_de_bucket' // (opcional) nombre de balde en s3
];

/**
 * Esto crea el objeto que se usa para ejecturar todos los métodos disponibles
 */
$datos = []; // Datos del comprobante si los tienes
$id_empresa = 5; // ID de empresa emisora

$facturador = new Comprobante($ajustes, $datos, $id_empresa);