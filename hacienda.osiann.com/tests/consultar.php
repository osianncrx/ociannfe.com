<?php
require __DIR__ . '/db.php'; // Carga $db, $ajustes y $facturador
$estado = $facturador->consultarEstado(
    '50601102500000000000000100001010000000001182591167',
    'R', // E para emision, R para recepcion
    '5'
);
print_r($estado);