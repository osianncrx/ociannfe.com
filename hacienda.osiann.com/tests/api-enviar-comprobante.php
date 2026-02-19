<?php
require __DIR__ . '/db.php'; // aquí carga $facturador y $db (mysqli)

if (!($db instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'La conexión a la base de datos no es válida']);
    exit;
}

// Solo POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Solo método POST permitido']);
    exit;
}

// Leer JSON del cuerpo
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

// Campos mínimos requeridos (excepto CódigoActividad y Emisor que vienen de BD)
$required = ['id_empresa', 'Receptor', 'CondicionVenta', 'MedioPago', 'Lineas'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Falta campo obligatorio: $field"]);
        exit;
    }
}

$id_empresa = $input['id_empresa'];

// Obtener datos empresa desde fe_empresas
$stmtEmp = $db->prepare("
    SELECT CodigoActividad, Nombre, Tipo, Numero,
           Provincia, Canton, Distrito, OtrasSenas, CorreoElectronico
    FROM fe_empresas
    WHERE id_empresa = ?
    LIMIT 1
");
if (!$stmtEmp) {
    http_response_code(500);
    echo json_encode(['error' => 'Error preparando consulta fe_empresas: ' . $db->error]);
    exit;
}

$stmtEmp->bind_param("i", $id_empresa);
$stmtEmp->execute();
$resultEmp = $stmtEmp->get_result();

if (!$empresaData = $resultEmp->fetch_assoc()) {
    http_response_code(404);
    echo json_encode(['error' => 'Empresa no encontrada para id_empresa: ' . $id_empresa]);
    exit;
}

$stmtEmp->close();

$CodigoActividad = $empresaData['CodigoActividad'] ?? null;

$Emisor = [
    'Nombre' => $empresaData['Nombre'] ?? '',
    'Identificacion' => [
        'Tipo' => $empresaData['Tipo'] ?? '',
        'Numero' => $empresaData['Numero'] ?? ''
    ],
    'Ubicacion' => [
        'Provincia' => $empresaData['Provincia'] ?? '',
        'Canton' => $empresaData['Canton'] ?? '',
        'Distrito' => $empresaData['Distrito'] ?? '',
        'OtrasSenas' => $empresaData['OtrasSenas'] ?? ''
    ],
    'CorreoElectronico' => $empresaData['CorreoElectronico'] ?? ''
];

// Obtener último consecutivo de fe_emisiones
$stmt = $db->prepare("SELECT clave FROM fe_emisiones WHERE id_empresa = ? ORDER BY id_emision DESC LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error preparando consulta: ' . $db->error]);
    exit;
}

$stmt->bind_param("i", $id_empresa);
$stmt->execute();
$result = $stmt->get_result();
$ultimaClave = null;
if ($row = $result->fetch_assoc()) {
    $ultimaClave = $row['clave'];
}

$stmt->close();

if ($ultimaClave) {
    $ultimoConsecutivoStr = substr($ultimaClave, 21, 20);
    $ultimoConsecutivoInt = intval(ltrim($ultimoConsecutivoStr, '0'));

    $nuevoConsecutivoInt = $ultimoConsecutivoInt + 1;
    $NumeroConsecutivo = str_pad($nuevoConsecutivoInt, 20, '0', STR_PAD_LEFT);
} else {
    $NumeroConsecutivo = "00100000010000000001";
}

// Fecha Emisión con zona horaria Costa Rica
if (!empty($input['FechaEmision'])) {
    try {
        $dt = new DateTime($input['FechaEmision']);
        $dt->setTimezone(new DateTimeZone('America/Costa_Rica'));
        $FechaEmision = $dt->format('c');
    } catch (Exception $e) {
        $dt = new DateTime('now', new DateTimeZone('America/Costa_Rica'));
        $FechaEmision = $dt->format('c');
    }
} else {
    $dt = new DateTime('now', new DateTimeZone('America/Costa_Rica'));
    $FechaEmision = $dt->format('c');
}

$Receptor = $input['Receptor'];
$CondicionVenta = $input['CondicionVenta'];
$MedioPago = $input['MedioPago'];
$LineasInput = $input['Lineas'];

if (!is_array($LineasInput) || count($LineasInput) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'El campo Lineas debe ser un array con al menos una línea']);
    exit;
}

$totalServiciosGravados = 0;
$totalMercanciasExentas = 0;
$totalGravado = 0;
$totalExento = 0;
$totalDescuentos = 0;
$totalImpuesto = 0;

$LineasDetalle = [];

foreach ($LineasInput as $index => $linea) {
    $lineRequired = ['NumeroLinea', 'Codigo', 'CodigoComercial', 'Cantidad', 'UnidadMedida', 'Detalle', 'PrecioUnitario'];
    foreach ($lineRequired as $lr) {
        if (!isset($linea[$lr])) {
            http_response_code(400);
            echo json_encode(['error' => "Falta campo obligatorio en línea $index: $lr"]);
            exit;
        }
    }

    $numLinea = $linea['NumeroLinea'];
    $codigo = $linea['Codigo'];
    $codigoComercial = $linea['CodigoComercial'];
    $cantidad = floatval($linea['Cantidad']);
    $unidadMedida = $linea['UnidadMedida'];
    $detalle = $linea['Detalle'];
    $precioUnitario = floatval($linea['PrecioUnitario']);
    $descuentoMonto = isset($linea['Descuento']['MontoDescuento']) ? floatval($linea['Descuento']['MontoDescuento']) : 0;
    $naturalezaDescuento = $linea['Descuento']['NaturalezaDescuento'] ?? '';

    $impuestoCodigo = $linea['Impuesto']['Codigo'] ?? null;
    $impuestoCodigoTarifa = $linea['Impuesto']['CodigoTarifa'] ?? null;
    $impuestoTarifa = isset($linea['Impuesto']['Tarifa']) ? floatval($linea['Impuesto']['Tarifa']) : 0;

    $montoTotal = round($cantidad * $precioUnitario, 2);
    $subTotal = round($montoTotal - $descuentoMonto, 2);

    $montoImpuesto = 0;
    if ($impuestoCodigo && $impuestoCodigoTarifa && $impuestoTarifa > 0) {
        $montoImpuesto = round($subTotal * ($impuestoTarifa / 100), 2);
    }

    $montoTotalLinea = round($subTotal + $montoImpuesto, 2);

    $totalDescuentos += $descuentoMonto;
    $totalImpuesto += $montoImpuesto;

    if ($montoImpuesto > 0) {
        $totalServiciosGravados += $subTotal;
        $totalGravado += $subTotal;
    } else {
        $totalMercanciasExentas += $subTotal;
        $totalExento += $subTotal;
    }

    $lineaDetalle = [
        'NumeroLinea' => strval($numLinea),
        'Codigo' => strval($codigo),
        'CodigoComercial' => $codigoComercial,
        'Cantidad' => strval($cantidad),
        'UnidadMedida' => $unidadMedida,
        'Detalle' => $detalle,
        'PrecioUnitario' => number_format($precioUnitario, 2, '.', ''),
        'MontoTotal' => number_format($montoTotal, 2, '.', ''),
    ];

    if ($descuentoMonto > 0) {
        $lineaDetalle['Descuento'] = [
            'MontoDescuento' => number_format($descuentoMonto, 2, '.', ''),
            'NaturalezaDescuento' => $naturalezaDescuento
        ];
    }

    $lineaDetalle['SubTotal'] = number_format($subTotal, 2, '.', '');

    if ($montoImpuesto > 0) {
        $lineaDetalle['Impuesto'] = [
            'Codigo' => $impuestoCodigo,
            'CodigoTarifa' => $impuestoCodigoTarifa,
            'Tarifa' => number_format($impuestoTarifa, 2, '.', ''),
            'Monto' => number_format($montoImpuesto, 2, '.', '')
        ];
    }

    $lineaDetalle['MontoTotalLinea'] = number_format($montoTotalLinea, 2, '.', '');

    $LineasDetalle[] = $lineaDetalle;
}

$totalVenta = $totalGravado + $totalExento;
$totalVentaNeta = $totalVenta - $totalDescuentos;
$totalComprobante = $totalVentaNeta + $totalImpuesto;

$ResumenFactura = [
    'TotalServGravados' => number_format($totalServiciosGravados, 2, '.', ''),
    'TotalMercanciasExentas' => number_format($totalMercanciasExentas, 2, '.', ''),
    'TotalGravado' => number_format($totalGravado, 2, '.', ''),
    'TotalExento' => number_format($totalExento, 2, '.', ''),
    'TotalVenta' => number_format($totalVenta, 2, '.', ''),
    'TotalDescuentos' => number_format($totalDescuentos, 2, '.', ''),
    'TotalVentaNeta' => number_format($totalVentaNeta, 2, '.', ''),
    'TotalImpuesto' => number_format($totalImpuesto, 2, '.', ''),
    'TotalComprobante' => number_format($totalComprobante, 2, '.', '')
];

$comprobante = [
    'CodigoActividad' => $CodigoActividad,
    'NumeroConsecutivo' => $NumeroConsecutivo,
    'FechaEmision' => $FechaEmision,
    'Emisor' => $Emisor,
    'Receptor' => $Receptor,
    'CondicionVenta' => $CondicionVenta,
    'MedioPago' => $MedioPago,
    'DetalleServicio' => ['LineaDetalle' => $LineasDetalle],
    'ResumenFactura' => $ResumenFactura
];

$respuesta = $facturador->enviarComprobante($comprobante, $id_empresa);

header('Content-Type: application/json');
echo json_encode($respuesta);
exit;
