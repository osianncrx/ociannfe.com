<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';
function actualizarEmpresaCampos(mysqli $db, int $id_empresa, array $campos): bool
{
    if (empty($campos)) {
        return true; // Nada que actualizar
    }

    $tabla = 'fe_empresas';
    $pk    = 'id_empresa';

    $permitidos = [
        'Nombre','Tipo','Numero','NombreComercial',
        'Provincia','Canton','Distrito','Barrio','OtrasSenas',
        'CorreoElectronico','CodigoPais','NumTelefono',
        'CodigoActividad'
    ];

    $set   = [];
    $vals  = [];
    $types = '';

    foreach ($campos as $col => $val) {
        if (!in_array($col, $permitidos, true)) {
            continue;
        }
        $set[] = "`$col` = ?";
        $vals[] = $val;
        $types .= 's'; // por ahora todo string
    }

    if (empty($set)) {
        return true;
    }

    $vals[] = $id_empresa;
    $types .= 'i';

    $sql = "UPDATE `$tabla` SET ".implode(', ', $set)." WHERE `$pk` = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en prepare(): ".$db->error." | SQL: ".$sql);
    }

    if (!$stmt->bind_param($types, ...$vals)) {
        throw new Exception("Error en bind_param(): ".$stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error en execute(): ".$stmt->error);
    }

    $stmt->close();
    return true;
}


// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usa POST.']);
    exit;
}

// --- Helpers ---
$g = function(string $k, $default = null) {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default;
};
$firstNonEmpty = function(array $keys, $default = null) use ($g) {
    foreach ($keys as $k) {
        $v = $g($k, null);
        if ($v !== null && $v !== '') return $v;
    }
    return $default;
};

// --- Validación mínima (para INSERT con guardarEmpresa) ---
$req_min = [
    ['cedula'],
    ['ambiente'],
    ['usuario', 'usuario_mh'],
    ['contra',  'contra_mh'],
    ['pin',     'pin_llave'],
];
foreach ($req_min as $group) {
    $ok = false;
    foreach ($group as $k) {
        if (!empty($_POST[$k])) { $ok = true; break; }
    }
    if (!$ok) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el campo obligatorio: ' . implode(' / ', $group)]);
        exit;
    }
}

// --- .p12 obligatorio (igual que tu primer script) ---
if (!isset($_FILES['archivo_p12']) || $_FILES['archivo_p12']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Archivo .p12 no recibido o con error']);
    exit;
}
$llave_tmp  = $_FILES['archivo_p12']['tmp_name'];
$llave_name = $_FILES['archivo_p12']['name'] ?? 'llave.p12';
$ext = strtolower(pathinfo($llave_name, PATHINFO_EXTENSION));
if ($ext !== 'p12' || !is_file($llave_tmp)) {
    http_response_code(400);
    echo json_encode(['error' => 'El archivo subido no parece una .p12 válida']);
    exit;
}
$llave = file_get_contents($llave_tmp);
if ($llave === false || $llave === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No se pudo leer el contenido de la .p12']);
    exit;
}

// --- 1) INSERT/UPSERT con los campos mínimos (igual al de arriba) ---
$datos_minimos = [
    'cedula'               => $g('cedula'),
    'ambiente'             => $g('ambiente'),
    'usuario'              => $firstNonEmpty(['usuario','usuario_mh']),
    'contra'               => $firstNonEmpty(['contra','contra_mh']),
    'pin'                  => $firstNonEmpty(['pin','pin_llave']),
    'llave_criptografica'  => $llave,
];

try {
    $id_empresa = $facturador->guardarEmpresa($datos_minimos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Error al guardar (mínimos): '.$e->getMessage()
    ]);
    exit;
}

// --- 2) UPDATE de campos extra, usando el id_empresa retornado ---
$extras = [
    // Identificación / nombre comercial
    'Nombre'            => $firstNonEmpty(['nombre']),
    'Tipo'              => $firstNonEmpty(['tipo']),
    'Numero'            => $g('cedula'),                         // mismo número de cédula
    'NombreComercial'   => $firstNonEmpty(['nombre_comercial']),

    // Ubicación
    'Provincia'         => $firstNonEmpty(['provincia']),
    'Canton'            => $firstNonEmpty(['canton']),
    'Distrito'          => $firstNonEmpty(['distrito']),
    'Barrio'            => $firstNonEmpty(['barrio']),
    'OtrasSenas'        => $firstNonEmpty(['otrassenas','otras_senas']),

    // Contacto
    'CorreoElectronico' => $firstNonEmpty(['correoElectronico','correo']),
    'CodigoPais'        => $firstNonEmpty(['codigoPais','codigo_pais']),
    'NumTelefono'       => $firstNonEmpty(['numTelefono','telefono']),

    // Actividad económica
    'CodigoActividad'   => $firstNonEmpty(['codigoActividad','codigo_actividad']),
];

// Filtrá vacíos/null para no sobreescribir con cadenas vacías
$extras = array_filter(
    $extras,
    static fn($v) => !is_null($v) && $v !== ''
);

try {
    if (!empty($extras)) {
        // Requiere agregar el método actualizarEmpresaCampos en tu db.php (abajo)
        actualizarEmpresaCampos($db,(int)$id_empresa, $extras);
    }

    echo json_encode([
        'success'            => true,
        'id_empresa'         => $id_empresa,
        'mensaje'            => 'Empresa registrada/modificada y datos extra actualizados correctamente.',
        'campos_actualizados'=> array_keys($extras),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Error al actualizar extras: '.$e->getMessage()
    ]);
}
