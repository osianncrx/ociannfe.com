<?php
require __DIR__ . '/db.php'; // Carga $db, $ajustes y $facturador
$estado = $facturador->consultarEstado(
    '50628072500000000000000100000010000000003164375084',
    'E', // E para emision, R para recepcion
    '5'
);
print_r($estado);