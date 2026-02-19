<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/db.php';

// OJO: no uses Comprobante::analizarXML si quieres el XML original
$xml = $facturador->cogerXml(
    '50628082500310293428500100001010000000289124615691',
    'E', // E emisión, R recepción
    1,   // tipo 1 = XML del comprobante
    5    // ID de empresa en tu BD
);

// 👉 Esto imprime el XML tal cual, con firma digital incluida
header("Content-Type: application/xml; charset=utf-8");
echo $xml;
