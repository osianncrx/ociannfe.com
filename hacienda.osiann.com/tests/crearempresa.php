<?php
require __DIR__ . '/db.php'; // Carga $db, $ajustes y $facturador

// Leer llave p12
$llave = file_get_contents('310293428520p.p12');

// Datos de empresa nueva
$datos_de_empresa = [
    'cedula'   => '3102934285',
    'ambiente' => '2',
    'usuario'  => 'cpj-3-102-934285@prod.comprobanteselectronicos.go.cr',
    'contra'   => '8A2_/M$mNUi$@$-bR+)A',
    'pin'      => '1598',
    'llave_criptografica' => $llave
];

// Crear empresa
$id_empresa = $facturador->guardarEmpresa($datos_de_empresa);

// Si quieres modificar después
//$datos_modificados = [
  //  'contra' => 'nueva_contraseña'
//];
//$facturador->guardarEmpresa($datos_modificados, $id_empresa);

echo "Empresa registrada/modificada con ID: $id_empresa\n";
