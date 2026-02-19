<?php

use Contica\eFacturacion\Facturador;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/db.php'; 
$xml = $facturador->cogerXmlComprobante('50625082500310293428500100001010000000288169692861');
$file = fopen(__DIR__ . "/getxml.xml", "w");
fwrite($file, $xml);
fclose($file);
