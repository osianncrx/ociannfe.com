<?php
declare(strict_types=1);

// 🚫 Asegúrate de que no haya BOM ni espacios antes de <?php
require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/db.php';

// --- CORS básico (opcional, útil para front) ---
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Función para responder error JSON coherente ---
function respond_json_error(int $code, string $msg, array $extra = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Obtención robusta de parámetros ---
// 1) Primero intenta por POST/GET (x-www-form-urlencoded)
$clave     = $_POST['clave']     ?? $_GET['clave']     ?? null;
$tipo      = $_POST['tipo']      ?? $_GET['tipo']      ?? null; // 'E' o 'R'
$xml_tipo  = $_POST['xml_tipo']  ?? $_GET['xml_tipo']  ?? null; // 1 = comprobante
$empresaId = $_POST['empresaId'] ?? $_GET['empresaId'] ?? null;

// 2) Si no llegaron, intenta JSON en el body
if ($clave === null || $tipo === null || $xml_tipo === null || $empresaId === null) {
    $ct = $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $j = json_decode($raw, true);
            if (is_array($j)) {
                $clave     = $clave     ?? ($j['clave']     ?? null);
                $tipo      = $tipo      ?? ($j['tipo']      ?? null);
                $xml_tipo  = $xml_tipo  ?? ($j['xml_tipo']  ?? null);
                $empresaId = $empresaId ?? ($j['empresaId'] ?? null);
            }
        }
    }
}

// --- Validación ---
if (!$clave || !$tipo || !$xml_tipo || !$empresaId) {
    respond_json_error(400, 'Faltan parámetros requeridos: clave, tipo, xml_tipo, empresaId', [
        'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'content_type' => $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '',
        'post_recibido' => array_keys($_POST),
        'get_recibido'  => array_keys($_GET),
    ]);
}

try {
    // --- Llamada al facturador ---
    /** @var \Contica\Facturador $facturador */ // ajusta el namespace si aplica
    $xml = $facturador->cogerXml(
        (string)$clave,
        (string)$tipo,         // 'E' emisión, 'R' recepción
        (int)$xml_tipo,        // 1 = comprobante
        (int)$empresaId
    );

    // --- Verificación de retorno ---
    if (!is_string($xml) || trim($xml) === '') {
        respond_json_error(502, 'El facturador no devolvió XML válido.');
    }

    // --- Entrega de XML tal cual ---
    // Limpia buffers por si algún include imprimió algo
    if (function_exists('fastcgi_finish_request')) { /* no limpiar buffers aquí */ }
    while (ob_get_level() > 0) { ob_end_clean(); }

    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
    exit;

} catch (Throwable $e) {
    respond_json_error(500, 'No se pudo obtener el XML', [
        'detalle' => $e->getMessage(),
        // 'trace' => $e->getTraceAsString(), // descomenta en desarrollo
    ]);
}
