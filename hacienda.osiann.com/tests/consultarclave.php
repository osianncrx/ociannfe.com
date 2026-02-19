<?php
require __DIR__ . '/db.php'; // carga $db, $ajustes y $facturador

header('Content-Type: application/json');

// Permitir solo método POST (puedes cambiar a GET si prefieres)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Solo método POST permitido']);
    exit;
}

// Leer JSON o parámetros POST
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true) ?? $_POST;

$clave = $input['clave'] ?? null;
$tipo = $input['tipo'] ?? null; // 'E' o 'R'
$id_empresa = $input['id_empresa'] ?? null;

if (!$clave || !$tipo || !$id_empresa) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan parámetros obligatorios: clave, tipo, id_empresa']);
    exit;
}

try {
    $estado = $facturador->consultarEstado($clave, $tipo, $id_empresa);
    echo json_encode([
        'success' => true,
        'estado' => $estado
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
