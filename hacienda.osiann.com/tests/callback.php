<?php
require __DIR__ . '/db.php'; // Carga $db, $ajustes y $facturador
$body = file_get_contents('php://input');

$estado = $facturador->procesarCallbackHacienda($body);
print_r($estado);