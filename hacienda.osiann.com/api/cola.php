<?php
require __DIR__ . '/db.php'; // Carga $db, $ajustes y $facturador
$docs_enviados = $facturador->enviarCola();
print_r($docs_enviados);