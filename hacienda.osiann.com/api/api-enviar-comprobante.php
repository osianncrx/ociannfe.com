<?php
require __DIR__ . '/db.php'; // aquí carga $facturador y $db (mysqli)

if (!($db instanceof mysqli)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'La conexión a la base de datos no es válida']);
    exit;
}

// Solo POST permitido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Solo método POST permitido']);
    exit;
}

// Leer JSON del cuerpo
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!is_array($input)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

// Campos mínimos requeridos (v4.4: MedioPago ya NO va al nivel raíz)
$required = ['id_empresa', 'Receptor', 'CondicionVenta', 'Lineas'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => "Falta campo obligatorio: $field"]);
        exit;
    }
}

$id_empresa = (int)$input['id_empresa'];
$Sucursal   = $input['Sucursal'] ?? "001";
$Terminal   = $input['Terminal'] ?? "00001";
$TipoDoc    = $input['TipoDoc']  ?? "01";

// =====================
// Empresa (Emisor) desde BD - SOLO columnas existentes hoy
// =====================
$stmtEmp = $db->prepare("
    SELECT 
        CodigoActividad,
        Nombre,
        Tipo,
        Numero,
        Provincia,
        Canton,
        Distrito,
        OtrasSenas,
        CorreoElectronico
    FROM fe_empresas
    WHERE id_empresa = ?
    LIMIT 1
");
if (!$stmtEmp) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error preparando consulta fe_empresas: ' . $db->error]);
    exit;
}
$stmtEmp->bind_param("i", $id_empresa);
$stmtEmp->execute();
$resultEmp = $stmtEmp->get_result();
if (!$empresaData = $resultEmp->fetch_assoc()) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Empresa no encontrada para id_empresa: ' . $id_empresa]);
    exit;
}
$stmtEmp->close();

$CodigoActividad = $empresaData['CodigoActividad'] ?? null;

// Normalizar códigos territoriales a dígitos (sin 0 a la izquierda) para evitar cvc-pattern (\d)
$prov = isset($empresaData['Provincia']) ? $empresaData['Provincia'] : '0';
$cant = isset($empresaData['Canton'])    ? $empresaData['Canton']  : '00';
$dist = isset($empresaData['Distrito'])  ? $empresaData['Distrito']  : '00';

// Emisor 100% BD (sin campos que tu tabla no tiene hoy)
$Emisor = [
    'Nombre' => (string)($empresaData['Nombre'] ?? ''),
    'Identificacion' => [
        'Tipo'   => (string)($empresaData['Tipo'] ?? ''),
        'Numero' => (string)($empresaData['Numero'] ?? '')
    ],
    'Ubicacion' => [
        'Provincia'  => $prov,
        'Canton'     => $cant,
        'Distrito'   => $dist,
        'OtrasSenas' => (string)($empresaData['OtrasSenas'] ?? '')
    ]
];
// Correo (opcional)
if (!empty($empresaData['CorreoElectronico'])) {
    $Emisor['CorreoElectronico'] = (string)$empresaData['CorreoElectronico'];
}

// =====================
// Consecutivo (mantener quemado si no hay clave previa)
// =====================

$sqlMaxConsec = "
    SELECT 
        MAX(CAST(RIGHT(NumeroConsecutivo, 10) AS UNSIGNED)) AS max_consecutivo
    FROM fe_emisiones
    WHERE NumeroConsecutivo IS NOT NULL
      AND NumeroConsecutivo <> ''
      AND LENGTH(NumeroConsecutivo) >= 10
      AND NumeroConsecutivo REGEXP '^[0-9]+$'
";



$resultMax = $db->query($sqlMaxConsec);
if (!$resultMax) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Error consultando consecutivo: ' . $db->error]);
    exit;
}



if ($resultMax) {
    // El consecutivo dentro de la clave está entre la posición 31 y 41 (10 dígitos)
    $rowMax = $resultMax->fetch_assoc();
    $ultimoConsecutivoInt = (int)($rowMax['max_consecutivo'] ?? 0);

    $nuevoConsecutivoInt  = $ultimoConsecutivoInt + 1;
    $nuevoConsecutivoStr  = str_pad((string) $nuevoConsecutivoInt, 10, '0', STR_PAD_LEFT);


    $NumeroConsecutivo = $Sucursal . $Terminal . $TipoDoc . $nuevoConsecutivoStr;
} else {
    $NumeroConsecutivo = "00100001010000000001";
}


// =====================
// Fecha Emisión CR
// =====================
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

// =====================
// Receptor (POST), Condición de venta, Moneda y Líneas
// =====================
$Receptor       = $input['Receptor'];
$CondicionVenta = (string)$input['CondicionVenta'];
$LineasInput    = $input['Lineas'];

// Medios de pago (nuevo y legacy; van dentro de ResumenFactura)
$MediosPago = [];
if (isset($input['MediosPago']) && is_array($input['MediosPago']) && count($input['MediosPago']) > 0) {
    $MediosPago = array_values(array_filter(array_map('strval', $input['MediosPago'])));
} elseif (!empty($input['MedioPago'])) { // legacy único
    $MediosPago = [ (string)$input['MedioPago'] ];
}
$MontosMediosPago = (isset($input['MontosMediosPago']) && is_array($input['MontosMediosPago'])) ? $input['MontosMediosPago'] : [];

// Moneda y tipo de cambio (bloque requerido)
$CodigoMoneda = 'CRC';
$TipoCambio   = '1';
if (!empty($input['CodigoTipoMoneda']['CodigoMoneda'])) {
    $CodigoMoneda = (string)$input['CodigoTipoMoneda']['CodigoMoneda'];
}
if (!empty($input['CodigoTipoMoneda']['TipoCambio'])) {
    $TipoCambio = (string)$input['CodigoTipoMoneda']['TipoCambio'];
}

if (!is_array($LineasInput) || count($LineasInput) === 0) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'El campo Lineas debe ser un array con al menos una línea']);
    exit;
}

// =====================
// Acumuladores y desglose
// =====================
$totalServiciosGravados   = 0.0;
$totalServExentos         = 0.0;
$totalMercanciasExentas   = 0.0;
$totalMercanciasGravadas  = 0.0;
$totalMercExonerada       = 0.0;
$totalMercNoSujeta        = 0.0;
$totalServNoSujeto        = 0.0;
$totalExonerado           = 0.0;
$totalNoSujeto            = 0.0;
$totalGravado             = 0.0;
$totalExento              = 0.0;
$totalDescuentos          = 0.0;
$totalImpuestoNeto        = 0.0; // neto cobrado
$totalImpAsumEmisorFab    = 0.0;
$totalIVADevuelto         = 0.0;

$LineasDetalle = [];
$desgloseCobrado = []; // (Codigo, CodigoTarifaIVA) => suma ImpuestoNeto

foreach ($LineasInput as $index => $linea) {
    $lineRequired = ['NumeroLinea', 'Codigo', 'Cantidad', 'UnidadMedida', 'Detalle', 'PrecioUnitario'];
    foreach ($lineRequired as $lr) {
        if (!isset($linea[$lr])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => "Falta campo obligatorio en línea $index: $lr"]);
            exit;
        }
    }

    $numLinea        = $linea['NumeroLinea'];
    $codigoCABYS     = $linea['Codigo'];
    $codigoComercial = $linea['CodigoComercial'] ?? null;
    $cantidad        = (float)$linea['Cantidad'];
    $unidadMedida    = (string)$linea['UnidadMedida'];
    $detalle         = (string)$linea['Detalle'];
    $precioUnitario  = (float)$linea['PrecioUnitario'];

    // Descuento (defaults 0)
    $descuentoMonto         = isset($linea['Descuento']['MontoDescuento']) ? (float)$linea['Descuento']['MontoDescuento'] : 0.0;
    $naturalezaDescuento    = isset($linea['Descuento']['NaturalezaDescuento']) ? (string)$linea['Descuento']['NaturalezaDescuento'] : '';

    $montoTotal    = round($cantidad * $precioUnitario, 2);
    $subTotal      = round($montoTotal - $descuentoMonto, 2);

    // Impuesto (bruto) y neto
    $impuestoCodigo       = $linea['Impuesto']['Codigo']           ?? '01';
    $impuestoCodigoTarifa = $linea['Impuesto']['CodigoTarifaIVA']  ?? null;
    $impuestoTarifa       = isset($linea['Impuesto']['Tarifa']) ? (float)$linea['Impuesto']['Tarifa'] : 0.0;

    // Mapear tarifa a CodigoTarifaIVA si falta (13%->08, 4%->04, 2%->03, 1%->02, 0%->01)
    if (!$impuestoCodigoTarifa) {
        if (abs($impuestoTarifa - 13.0) < 0.001) $impuestoCodigoTarifa = '08';
        elseif (abs($impuestoTarifa - 4.0) < 0.001) $impuestoCodigoTarifa = '04';
        elseif (abs($impuestoTarifa - 2.0) < 0.001) $impuestoCodigoTarifa = '03';
        elseif (abs($impuestoTarifa - 1.0) < 0.001) $impuestoCodigoTarifa = '02';
        else $impuestoCodigoTarifa = '01';
    }

    $montoImpuestoBruto = ($impuestoTarifa > 0) ? round($subTotal * ($impuestoTarifa / 100), 2) : 0.0;

    // Exoneración y asumido
    $montoExonerado = isset($linea['Impuesto']['Exoneracion']['MontoImpuesto'])
        ? (float)$linea['Impuesto']['Exoneracion']['MontoImpuesto'] : 0.0;

    $impuestoAsumidoEmisor = isset($linea['ImpuestoAsumidoEmisorFabrica'])
        ? (float)$linea['ImpuestoAsumidoEmisorFabrica'] : 0.0;

    // Impuesto NETO (cobrado)
    $montoImpuestoNeto = max(0.0, round($montoImpuestoBruto - $montoExonerado - $impuestoAsumidoEmisor, 2));

    // Monto total de la línea
    $montoTotalLinea = round($subTotal + $montoImpuestoNeto, 2);

    $totalDescuentos       += $descuentoMonto;
    $totalImpuestoNeto     += $montoImpuestoNeto;
    $totalImpAsumEmisorFab += $impuestoAsumidoEmisor;

    // Clasificación (según impuesto BRUTO)
    if ($montoImpuestoBruto > 0) {
        if (strtolower($unidadMedida) === 'sp') {
            $totalServiciosGravados += $subTotal;
        } else {
            $totalMercanciasGravadas += $subTotal;
        }
        $totalGravado += $subTotal;
    } else {
        if (strtolower($unidadMedida) === 'sp') {
            $totalServExentos += $subTotal;
        } else {
            $totalMercanciasExentas += $subTotal;
        }
        $totalExento += $subTotal;
    }

    // Desglose por impuesto cobrado
    $claveDesg = $impuestoCodigo . '|' . $impuestoCodigoTarifa;
    if (!isset($desgloseCobrado[$claveDesg])) {
        $desgloseCobrado[$claveDesg] = [
            'Codigo'             => (string)$impuestoCodigo,
            'CodigoTarifaIVA'    => (string)$impuestoCodigoTarifa,
            'TotalMontoImpuesto' => 0.0
        ];
    }
    $desgloseCobrado[$claveDesg]['TotalMontoImpuesto'] += $montoImpuestoNeto;

    // Construcción de la línea
    $lineaDetalle = [
        'NumeroLinea'    => (string)$numLinea,
        'CodigoCABYS'    => (string)$codigoCABYS,
    ];

    if (is_array($codigoComercial)
        && !empty($codigoComercial['Tipo'])
        && !empty($codigoComercial['Codigo'])) {
        $lineaDetalle['CodigoComercial'] = [
            'Tipo'   => (string)$codigoComercial['Tipo'],
            'Codigo' => (string)$codigoComercial['Codigo'],
        ];
    }

    $lineaDetalle['Cantidad']       = number_format($cantidad, 2, '.', '');
    $lineaDetalle['UnidadMedida']   = $unidadMedida;
    $lineaDetalle['Detalle']        = $detalle;
    $lineaDetalle['PrecioUnitario'] = number_format($precioUnitario, 2, '.', '');
    $lineaDetalle['MontoTotal']     = number_format($montoTotal, 2, '.', '');

    if ($descuentoMonto > 0) {
        $lineaDetalle['Descuento'] = [
            'MontoDescuento'      => number_format($descuentoMonto, 2, '.', ''),
            'NaturalezaDescuento' => $naturalezaDescuento
        ];
    }

    $lineaDetalle['SubTotal']      = number_format($subTotal, 2, '.', '');
    $lineaDetalle['BaseImponible'] = number_format($subTotal, 2, '.', '');

    // Impuesto (BRUTO reportado) + exoneración si aplica
    $imp = [
        'Codigo'          => (string)$impuestoCodigo,
        'CodigoTarifaIVA' => (string)$impuestoCodigoTarifa,
        'Tarifa'          => number_format($impuestoTarifa, 2, '.', ''),
        'Monto'           => number_format($montoImpuestoBruto, 2, '.', '')
    ];
    if ($montoExonerado > 0) {
        $imp['Exoneracion'] = [
            'MontoImpuesto' => number_format($montoExonerado, 2, '.', '')
        ];
        $totalExonerado += $montoExonerado;
    }
    $lineaDetalle['Impuesto'] = $imp;

    // Aclaratorios consistentes con Hacienda
    $lineaDetalle['ImpuestoAsumidoEmisorFabrica'] = number_format($impuestoAsumidoEmisor, 2, '.', '');
    $lineaDetalle['ImpuestoNeto']                 = number_format($montoImpuestoNeto, 2, '.', '');
    $lineaDetalle['MontoTotalLinea']              = number_format($montoTotalLinea, 2, '.', '');

    $LineasDetalle[] = $lineaDetalle;
}

// Totales
$totalVenta       = $totalGravado + $totalExento;
$totalVentaNeta   = $totalVenta - $totalDescuentos;
$totalComprobante = $totalVentaNeta + $totalImpuestoNeto;

// ResumenFactura
$ResumenFactura = [
    'CodigoTipoMoneda' => [
        'CodigoMoneda' => $CodigoMoneda,
        'TipoCambio'   => (string)$TipoCambio
    ],

    'TotalServGravados'       => number_format($totalServiciosGravados, 2, '.', ''),
    'TotalServExentos'        => number_format($totalServExentos, 2, '.', ''),
    'TotalServExonerado'      => number_format(0, 2, '.', ''),
    'TotalServNoSujeto'       => number_format($totalServNoSujeto, 2, '.', ''),

    'TotalMercanciasGravadas' => number_format($totalMercanciasGravadas, 2, '.', ''),
    'TotalMercanciasExentas'  => number_format($totalMercanciasExentas, 2, '.', ''),
    'TotalMercExonerada'      => number_format($totalMercExonerada, 2, '.', ''),
    'TotalMercNoSujeta'       => number_format($totalMercNoSujeta, 2, '.', ''),

    'TotalGravado'            => number_format($totalGravado, 2, '.', ''),
    'TotalExento'             => number_format($totalExento, 2, '.', ''),
    'TotalExonerado'          => number_format($totalExonerado, 2, '.', ''),
    'TotalNoSujeto'           => number_format($totalNoSujeto, 2, '.', ''),

    'TotalVenta'              => number_format($totalVenta, 2, '.', ''),
    'TotalDescuentos'         => number_format($totalDescuentos, 2, '.', ''),
    'TotalVentaNeta'          => number_format($totalVentaNeta, 2, '.', ''),
];

// Desglose por impuesto cobrado (neto)
if (!empty($desgloseCobrado)) {
    foreach ($desgloseCobrado as $d) {
        $ResumenFactura['TotalDesgloseImpuesto'][] = [
            'Codigo'             => $d['Codigo'],
            'CodigoTarifaIVA'    => $d['CodigoTarifaIVA'],
            'TotalMontoImpuesto' => number_format($d['TotalMontoImpuesto'], 2, '.', '')
        ];
    }
} else {
    $ResumenFactura['TotalDesgloseImpuesto'] = [[
        'Codigo'             => '01',
        'CodigoTarifaIVA'    => '01',
        'TotalMontoImpuesto' => number_format(0, 2, '.', '')
    ]];
}

// Resto de totales
$ResumenFactura['TotalImpuesto']             = number_format($totalImpuestoNeto, 2, '.', '');
$ResumenFactura['TotalImpAsumEmisorFabrica'] = number_format($totalImpAsumEmisorFab, 2, '.', '');
$ResumenFactura['TotalIVADevuelto']          = number_format($totalIVADevuelto, 2, '.', '');

// Medios de pago (opcional, repetibles)
if (count($MediosPago) > 0) {
    foreach ($MediosPago as $cod) {
        $ResumenFactura['MedioPago'][] = [
            'TipoMedioPago' => (string)$cod,
            'TotalMedioPago'=> number_format(
                isset($MontosMediosPago[$cod]) ? (float)$MontosMediosPago[$cod] : 0,
                0, '.', ''
            )
        ];
    }
}
$ResumenFactura['TotalComprobante'] = number_format($totalComprobante, 2, '.', '');

// -------- Comprobante final --------
$comprobante = [
    'ProveedorSistemas'       => '3102877461',                 // ← “quemado”
    'CodigoActividadEmisor'   => $CodigoActividad ?: '701001', // fallback si en BD está vacío
    'CodigoActividadReceptor' => $CodigoActividad ?: '721001', // ajusta si usas otro criterio
    'NumeroConsecutivo'       => $NumeroConsecutivo,
    'FechaEmision'            => $FechaEmision,

    'Emisor'                  => $Emisor,      // ← 100% BD
    'Receptor'                => $Receptor,    // ← 100% POST
    'CondicionVenta'          => $CondicionVenta,

    'DetalleServicio'         => ['LineaDetalle' => $LineasDetalle],
    'ResumenFactura'          => $ResumenFactura,
];

// Enviar a conector/firmador
$respuesta = $facturador->enviarComprobante($comprobante, $id_empresa);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($respuesta);
exit;
