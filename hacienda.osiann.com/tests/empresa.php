<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

// Solo permitimos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usa POST.']);
    exit;
}

// Validar campos de texto en $_POST
$campos_requeridos = ['cedula', 'ambiente', 'usuario', 'contra', 'pin'];
foreach ($campos_requeridos as $campo) {
    if (empty($_POST[$campo])) {
        http_response_code(400);
        echo json_encode(['error' => "Falta el campo obligatorio: $campo"]);
        exit;
    }
}

// Validar archivo .p12 subido
if (!isset($_FILES['archivo_p12']) || $_FILES['archivo_p12']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Archivo .p12 no recibido o con error']);
    exit;
}

// Leer contenido del archivo .p12 subido
$llave = file_get_contents($_FILES['archivo_p12']['tmp_name']);

// Preparar datos para guardar empresa
$datos = [
    'cedula'   => $_POST['cedula'],
    'ambiente' => $_POST['ambiente'],
    'usuario'  => $_POST['usuario'],
    'contra'   => $_POST['contra'],
    'pin'      => $_POST['pin'],
    'llave_criptografica' => $llave
];

// Guardar empresa
try {
    $id_empresa = $facturador->guardarEmpresa($datos);
    echo json_encode([
        'success' => true,
        'id_empresa' => $id_empresa,
        'mensaje' => 'Empresa registrada/modificada correctamente.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
