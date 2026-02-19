<?php
declare(strict_types=1);

require __DIR__ . '/dbr.php'; // Debe definir $db (mysqli) y $facturador
header('Content-Type: application/json; charset=utf-8');

// CORS opcional (comenta si no lo necesitas)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usa POST.']);
    exit;
}

if (!($db instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'La conexión a la base de datos no es válida']);
    exit;
}

/** Utilidades */
function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') return [];
    $asArray = json_decode($raw, true);
    return is_array($asArray) ? $asArray : [];
}

function get_header_content_type(): string {
    $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    return strtolower(trim(explode(';', $ct)[0] ?? ''));
}

/** Detección de modo: multipart o json */
$contentType = get_header_content_type();
$isMultipart = ($contentType === 'multipart/form-data'); // nota: algunos clientes no envían el boundary aquí
$isJson      = ($contentType === 'application/json');

$input = [];
$xmlContent = null;

// ------------
// Leer INPUTS
// ------------
if ($isMultipart || !empty($_FILES)) {
    // multipart/form-data
    // Campos de texto
    $input['id_empresa'] = isset($_POST['id_empresa']) ? (int)$_POST['id_empresa'] : null;
    $input['Sucursal']   = isset($_POST['Sucursal']) ? (string)$_POST['Sucursal'] : null;
    $input['Terminal']   = isset($_POST['Terminal']) ? (string)$_POST['Terminal'] : null;
    $input['TipoDoc']    = isset($_POST['TipoDoc']) ? (string)$_POST['TipoDoc'] : null;

    // 'datos' puede venir como JSON en un campo de texto
    if (!empty($_POST['datos'])) {
        $try = json_decode((string)$_POST['datos'], true);
        $input['datos'] = is_array($try) ? $try : [];
    }

    // Archivo XML
    if (!empty($_FILES['xml_file']) && is_uploaded_file($_FILES['xml_file']['tmp_name'])) {
        $xmlContent = file_get_contents($_FILES['xml_file']['tmp_name']);
        if ($xmlContent === false) {
            http_response_code(400);
            echo json_encode(['error' => 'No se pudo leer el archivo subido (xml_file).']);
            exit;
        }
    } else {
        // Soporte alterno: si te pasan un nombre de archivo local en el servidor (no recomendado)
        if (!empty($_POST['xml_filename'])) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . basename((string)$_POST['xml_filename']);
            if (!is_file($path) || !is_readable($path)) {
                http_response_code(400);
                echo json_encode(['error' => 'xml_filename no existe o no es legible en el servidor.']);
                exit;
            }
            $xmlContent = file_get_contents($path);
            if ($xmlContent === false) {
                http_response_code(400);
                echo json_encode(['error' => 'No se pudo leer xml_filename.']);
                exit;
            }
        }
    }
} else {
    // application/json (o sin cabecera pero enviaron JSON)
    $input = read_json_body();

    // xml en base64
    if (!empty($input['xml_base64'])) {
        $decoded = base64_decode((string)$input['xml_base64'], true);
        if ($decoded === false) {
            http_response_code(400);
            echo json_encode(['error' => 'xml_base64 inválido (no es base64 válido).']);
            exit;
        }
        $xmlContent = $decoded;
    }
}

// Defaults y saneo
$id_empresa = isset($input['id_empresa']) && is_numeric($input['id_empresa']) ? (int)$input['id_empresa'] : 64;
$Sucursal   = !empty($input['Sucursal']) ? (string)$input['Sucursal'] : '001';
$Terminal   = !empty($input['Terminal']) ? (string)$input['Terminal'] : '00001';
$TipoDoc    = !empty($input['TipoDoc'])  ? (string)$input['TipoDoc']  : '01';

// 'datos' opcional; si no viene, usa arreglo vacío y el cliente debe completarlo
$datos = isset($input['datos']) && is_array($input['datos']) ? $input['datos'] : [];

// Validaciones mínimas
if ($xmlContent === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el XML. En multipart envía "xml_file"; en JSON envía "xml_base64".']);
    exit;
}
if (trim($xmlContent) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'El contenido del XML está vacío.']);
    exit;
}

// -----------------------
// (Opcional) Validar Emisor existe
// -----------------------
$stmtEmp = $db->prepare("
    SELECT 1 
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
$existe = $stmtEmp->get_result()->fetch_row();
$stmtEmp->close();
if (!$existe) {
    http_response_code(404);
    echo json_encode(['error' => 'Empresa no encontrada para id_empresa: ' . $id_empresa]);
    exit;
}

// ------------------------------------
// Generar NumeroConsecutivoReceptor si no lo mandan
// ------------------------------------
if (empty($datos['NumeroConsecutivoReceptor'])) {
    $stmt = $db->prepare("SELECT clave FROM fe_recepciones WHERE id_empresa = ? ORDER BY id_recepcion DESC LIMIT 1");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Error preparando consulta consecutivo: ' . $db->error]);
        exit;
    }
    $stmt->bind_param("i", $id_empresa);
    $stmt->execute();
    $result = $stmt->get_result();
    $ultimaClave = $result->fetch_assoc()['clave'] ?? null;
    $stmt->close();

    if ($ultimaClave) {
        // Consecutivo (10 dígitos desde la posición 31)
        $ultimoConsecutivoStr = substr($ultimaClave, 31, 10);
        $ultimoConsecutivoInt = (int) ltrim($ultimoConsecutivoStr, '0');
        $nuevoConsecutivoInt  = $ultimoConsecutivoInt + 1;
        $nuevoConsecutivoStr  = str_pad((string)$nuevoConsecutivoInt, 10, '0', STR_PAD_LEFT);
        $NumeroConsecutivo    = $Sucursal . $Terminal . $TipoDoc . $nuevoConsecutivoStr;
    } else {
        $NumeroConsecutivo = "00100001010000000001";
    }

    $datos['NumeroConsecutivoReceptor'] = $NumeroConsecutivo;
} else {
    $NumeroConsecutivo = (string)$datos['NumeroConsecutivoReceptor'];
}

// -----------------------
// Llamar a recepcionar()
// -----------------------
try {
    // Tu implementación de recepcionar espera el contenido del XML como STRING
    $respuesta = $facturador->recepcionar($xmlContent, $datos, $id_empresa);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Recepción enviada',
        'respuesta' => $respuesta,
        'consecutivo' => $NumeroConsecutivo
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Fallo recepcionar: ' . $e->getMessage()]);
}
