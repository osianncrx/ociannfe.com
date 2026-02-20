<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Recepcion;
use App\Models\Empresa;
use App\Services\FacturacionService;
use Contica\Facturacion\Comprobante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecepcionController extends Controller
{
    private const TIPOS_RECEPCIONABLES = ['01', '02', '03', '08', '09'];

    private const TIPOS_NO_RECEPCIONABLES_MSG = [
        '04' => 'Tiquete Electrónico (04) no es recepcionable. El emisor debe emitir una Factura Electrónica (01).',
        '10' => 'Recibo Electrónico (10) no es recepcionable ante Hacienda.',
    ];

    public function __construct(
        private FacturacionService $facturacionService,
    ) {}

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Recepcion::where('tenant_id', $tenantId)
            ->orderByDesc('id_recepcion');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('empresa')) {
            $query->where('id_empresa', $request->empresa);
        }

        $recepciones = $query->paginate(20)->withQueryString();
        $empresas = Empresa::where('tenant_id', $tenantId)->get();

        return view('user.recepciones.index', compact('recepciones', 'empresas'));
    }

    public function show(int $id)
    {
        $recepcion = Recepcion::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_recepcion', $id)
            ->firstOrFail();
        return view('user.recepciones.show', compact('recepcion'));
    }

    public function create()
    {
        $empresas = Empresa::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('user.recepciones.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_empresa'          => 'required|integer',
            'archivo_xml'         => 'required|file|max:2048',
            'respuesta_tipo'      => 'required|string|in:05,06,07',
            'actividad_economica' => 'nullable|string|max:6',
            'detalle_mensaje'     => 'nullable|string|max:160',
        ], [
            'archivo_xml.required' => 'Debe seleccionar un archivo XML.',
            'respuesta_tipo.required' => 'Seleccione el tipo de respuesta.',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $empresa = Empresa::where('tenant_id', $tenantId)
            ->where('id_empresa', $request->id_empresa)
            ->firstOrFail();

        $xmlContent = file_get_contents($request->file('archivo_xml')->getRealPath());
        if (!$xmlContent) {
            return redirect()->back()->withInput()
                ->with('error', 'No se pudo leer el archivo XML.');
        }

        try {
            $datosXml = Comprobante::analizarXML($xmlContent);
        } catch (\Exception $e) {
            Log::error('Error al parsear XML de recepción: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'El archivo XML no es válido: ' . $e->getMessage());
        }

        if (empty($datosXml)) {
            return redirect()->back()->withInput()
                ->with('error', 'No se pudieron extraer datos del XML.');
        }

        $clave = $datosXml['Clave'] ?? '';
        if (strlen($clave) < 50) {
            return redirect()->back()->withInput()
                ->with('error', 'La clave del documento no es válida.');
        }

        $tipoDoc = substr($clave, 29, 2);

        if (!in_array($tipoDoc, self::TIPOS_RECEPCIONABLES)) {
            $msg = self::TIPOS_NO_RECEPCIONABLES_MSG[$tipoDoc]
                ?? "El tipo de documento ({$tipoDoc}) no es recepcionable ante Hacienda. Solo se pueden recepcionar: Facturas (01), Notas de Débito (02), Notas de Crédito (03), Facturas de Compra (08) y Facturas de Exportación (09).";
            return redirect()->back()->withInput()->with('error', $msg);
        }

        $recepcion = Recepcion::updateOrCreate(
            ['clave' => $clave, 'id_empresa' => $empresa->id_empresa],
            [
                'tenant_id'                    => $tenantId,
                'NumeroConsecutivo'            => $datosXml['NumeroConsecutivo'] ?? null,
                'TipoDocumento'               => $tipoDoc,
                'FechaEmision'                => $datosXml['FechaEmision'] ?? null,
                'Emisor_Nombre'               => $datosXml['Emisor']['Nombre'] ?? null,
                'Emisor_TipoIdentificacion'   => $datosXml['Emisor']['Identificacion']['Tipo'] ?? null,
                'Emisor_NumeroIdentificacion' => $datosXml['Emisor']['Identificacion']['Numero'] ?? null,
                'Emisor_CorreoElectronico'    => $datosXml['Emisor']['CorreoElectronico'] ?? null,
                'Receptor_Nombre'             => $datosXml['Receptor']['Nombre'] ?? null,
                'Receptor_TipoIdentificacion' => $datosXml['Receptor']['Identificacion']['Tipo'] ?? null,
                'Receptor_NumeroIdentificacion' => $datosXml['Receptor']['Identificacion']['Numero'] ?? null,
                'TotalComprobante'            => $datosXml['ResumenFactura']['TotalComprobante'] ?? 0,
                'TotalImpuesto'               => $datosXml['ResumenFactura']['TotalImpuesto'] ?? 0,
                'CodigoMoneda'                => $datosXml['ResumenFactura']['CodigoTipoMoneda']['CodigoMoneda'] ?? 'CRC',
                'xml_original'                => $xmlContent,
                'estado'                      => Recepcion::ESTADO_PENDIENTE,
                'respuesta_tipo'              => $request->respuesta_tipo,
            ]
        );

        try {
            $result = $this->facturacionService->recepcionarDocumento(
                $xmlContent,
                $clave,
                $empresa->id_empresa,
                $request->respuesta_tipo,
                $request->detalle_mensaje ?? 'Aceptado',
                $request->actividad_economica ?? $empresa->CodigoActividad ?? '',
                $tenantId
            );

            if ($result['success']) {
                $recepcion->update([
                    'estado'                => Recepcion::ESTADO_ENVIADO,
                    'respuesta_consecutivo' => $result['consecutivo'] ?? null,
                    'respuesta_mensaje'     => $request->detalle_mensaje ?? 'Aceptado',
                ]);

                return redirect()->route('recepciones.index')
                    ->with('success', 'Documento recepcionado y respuesta enviada a Hacienda. Clave: ' . $clave);
            }

            $recepcion->update([
                'estado'  => Recepcion::ESTADO_ERROR,
                'mensaje' => $result['message'] ?? 'Error desconocido',
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'Error al enviar respuesta a Hacienda: ' . ($result['message'] ?? 'Error desconocido'));
        } catch (\Exception $e) {
            Log::error('Error al recepcionar documento: ' . $e->getMessage(), [
                'clave' => $clave,
                'trace' => $e->getTraceAsString(),
            ]);

            $recepcion->update([
                'estado'  => Recepcion::ESTADO_ERROR,
                'mensaje' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'Error al procesar recepción: ' . $e->getMessage());
        }
    }

    public function parsearXml(Request $request): JsonResponse
    {
        $request->validate([
            'archivo_xml' => 'required|file|max:2048',
        ]);

        $xmlContent = file_get_contents($request->file('archivo_xml')->getRealPath());
        if (!$xmlContent) {
            return response()->json(['success' => false, 'message' => 'No se pudo leer el archivo.']);
        }

        try {
            $datos = Comprobante::analizarXML($xmlContent);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'XML inválido: ' . $e->getMessage()]);
        }

        if (empty($datos)) {
            return response()->json(['success' => false, 'message' => 'No se pudieron extraer datos del XML.']);
        }

        $clave = $datos['Clave'] ?? '';
        $tipoDoc = strlen($clave) >= 31 ? substr($clave, 29, 2) : '';
        $moneda = $datos['ResumenFactura']['CodigoTipoMoneda']['CodigoMoneda'] ?? 'CRC';
        $simbolo = $moneda === 'USD' ? '$' : '₡';

        $lineas = [];
        $detalleServicio = $datos['DetalleServicio'] ?? [];
        if (is_array($detalleServicio)) {
            foreach ($detalleServicio as $l) {
                if (!is_array($l)) continue;
                $lineas[] = [
                    'numero'           => $l['NumeroLinea'] ?? '',
                    'detalle'          => $l['Detalle'] ?? '',
                    'cantidad'         => $l['Cantidad'] ?? 0,
                    'precio_unitario'  => $l['PrecioUnitario'] ?? 0,
                    'impuesto_monto'   => $l['Impuesto']['Monto'] ?? 0,
                    'monto_total_linea'=> $l['MontoTotalLinea'] ?? 0,
                ];
            }
        }

        $actividadEconomica = '';
        if (isset($datos['Receptor']['Identificacion']['Numero'])) {
            $actividadEconomica = $datos['CodigoActividad'] ?? '';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'clave'               => $clave,
                'tipo_documento'      => $tipoDoc,
                'fecha_emision'       => $datos['FechaEmision'] ?? '',
                'moneda'              => $moneda,
                'moneda_simbolo'      => $simbolo,
                'emisor_nombre'       => $datos['Emisor']['Nombre'] ?? '',
                'emisor_tipo_id'      => $datos['Emisor']['Identificacion']['Tipo'] ?? '',
                'emisor_numero_id'    => $datos['Emisor']['Identificacion']['Numero'] ?? '',
                'emisor_email'        => $datos['Emisor']['CorreoElectronico'] ?? '',
                'receptor_nombre'     => $datos['Receptor']['Nombre'] ?? '',
                'receptor_tipo_id'    => $datos['Receptor']['Identificacion']['Tipo'] ?? '',
                'receptor_numero_id'  => $datos['Receptor']['Identificacion']['Numero'] ?? '',
                'lineas'              => $lineas,
                'total_venta'         => $datos['ResumenFactura']['TotalVenta'] ?? 0,
                'total_impuesto'      => $datos['ResumenFactura']['TotalImpuesto'] ?? 0,
                'total_comprobante'   => $datos['ResumenFactura']['TotalComprobante'] ?? 0,
                'actividad_economica' => $actividadEconomica,
            ],
        ]);
    }

    public function parsearXmlMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'archivos_xml'   => 'required|array|min:1|max:100',
            'archivos_xml.*' => 'file|max:2048',
        ]);

        $resultados = [];
        $tipoDocMap = [
            '01' => 'Factura Electrónica', '02' => 'Nota de Débito', '03' => 'Nota de Crédito',
            '04' => 'Tiquete Electrónico', '08' => 'Factura Compra', '09' => 'Factura Exportación',
            '10' => 'Recibo Electrónico',
        ];

        foreach ($request->file('archivos_xml') as $index => $archivo) {
            $nombre = $archivo->getClientOriginalName();
            try {
                $xmlContent = file_get_contents($archivo->getRealPath());
                if (!$xmlContent) {
                    $resultados[] = ['index' => $index, 'nombre' => $nombre, 'success' => false, 'message' => 'No se pudo leer el archivo.'];
                    continue;
                }

                $datos = Comprobante::analizarXML($xmlContent);
                if (empty($datos)) {
                    $resultados[] = ['index' => $index, 'nombre' => $nombre, 'success' => false, 'message' => 'No se pudieron extraer datos del XML.'];
                    continue;
                }

                $clave = $datos['Clave'] ?? '';
                $tipoDoc = strlen($clave) >= 31 ? substr($clave, 29, 2) : '';
                $moneda = $datos['ResumenFactura']['CodigoTipoMoneda']['CodigoMoneda'] ?? 'CRC';
                $simbolo = $moneda === 'USD' ? '$' : '₡';

                $recepcionable = in_array($tipoDoc, self::TIPOS_RECEPCIONABLES);
                $advertencia = null;
                if (!$recepcionable) {
                    $advertencia = self::TIPOS_NO_RECEPCIONABLES_MSG[$tipoDoc]
                        ?? "Tipo de documento ({$tipoDoc}) no es recepcionable ante Hacienda.";
                }

                $resultados[] = [
                    'index'             => $index,
                    'nombre'            => $nombre,
                    'success'           => true,
                    'recepcionable'     => $recepcionable,
                    'advertencia'       => $advertencia,
                    'clave'             => $clave,
                    'tipo_documento'    => $tipoDoc,
                    'tipo_documento_texto' => $tipoDocMap[$tipoDoc] ?? $tipoDoc,
                    'fecha_emision'     => $datos['FechaEmision'] ?? '',
                    'moneda'            => $moneda,
                    'moneda_simbolo'    => $simbolo,
                    'emisor_nombre'     => $datos['Emisor']['Nombre'] ?? '',
                    'emisor_id'         => $datos['Emisor']['Identificacion']['Numero'] ?? '',
                    'total_comprobante' => $datos['ResumenFactura']['TotalComprobante'] ?? 0,
                    'total_impuesto'    => $datos['ResumenFactura']['TotalImpuesto'] ?? 0,
                ];
            } catch (\Exception $e) {
                $resultados[] = ['index' => $index, 'nombre' => $nombre, 'success' => false, 'message' => 'XML inválido: ' . $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'resultados' => $resultados]);
    }

    public function storeMasivo(Request $request): JsonResponse
    {
        $request->validate([
            'id_empresa'          => 'required|integer',
            'archivos_xml'        => 'required|array|min:1|max:10',
            'archivos_xml.*'      => 'file|max:2048',
            'respuesta_tipo'      => 'required|string|in:05,06,07',
            'actividad_economica' => 'nullable|string|max:6',
            'detalle_mensaje'     => 'nullable|string|max:160',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $empresa = Empresa::where('tenant_id', $tenantId)
            ->where('id_empresa', $request->id_empresa)
            ->firstOrFail();

        $archivos = $request->file('archivos_xml');
        $resultados = [];

        foreach ($archivos as $archivo) {
            $nombre = $archivo->getClientOriginalName();
            try {
                $xmlContent = file_get_contents($archivo->getRealPath());
                if (!$xmlContent) {
                    $resultados[] = ['nombre' => $nombre, 'success' => false, 'message' => 'No se pudo leer el archivo.'];
                    continue;
                }

                $datosXml = Comprobante::analizarXML($xmlContent);
                if (empty($datosXml)) {
                    $resultados[] = ['nombre' => $nombre, 'success' => false, 'message' => 'No se pudieron extraer datos del XML.'];
                    continue;
                }

                $clave = $datosXml['Clave'] ?? '';
                if (strlen($clave) < 50) {
                    $resultados[] = ['nombre' => $nombre, 'success' => false, 'message' => 'La clave del documento no es válida.'];
                    continue;
                }

                $tipoDoc = substr($clave, 29, 2);

                if (!in_array($tipoDoc, self::TIPOS_RECEPCIONABLES)) {
                    $msg = self::TIPOS_NO_RECEPCIONABLES_MSG[$tipoDoc]
                        ?? "Tipo de documento ({$tipoDoc}) no es recepcionable ante Hacienda.";
                    $resultados[] = ['nombre' => $nombre, 'success' => false, 'message' => $msg];
                    continue;
                }

                $recepcion = Recepcion::updateOrCreate(
                    ['clave' => $clave, 'id_empresa' => $empresa->id_empresa],
                    [
                        'tenant_id'                    => $tenantId,
                        'NumeroConsecutivo'            => $datosXml['NumeroConsecutivo'] ?? null,
                        'TipoDocumento'               => $tipoDoc,
                        'FechaEmision'                => $datosXml['FechaEmision'] ?? null,
                        'Emisor_Nombre'               => $datosXml['Emisor']['Nombre'] ?? null,
                        'Emisor_TipoIdentificacion'   => $datosXml['Emisor']['Identificacion']['Tipo'] ?? null,
                        'Emisor_NumeroIdentificacion' => $datosXml['Emisor']['Identificacion']['Numero'] ?? null,
                        'Emisor_CorreoElectronico'    => $datosXml['Emisor']['CorreoElectronico'] ?? null,
                        'Receptor_Nombre'             => $datosXml['Receptor']['Nombre'] ?? null,
                        'Receptor_TipoIdentificacion' => $datosXml['Receptor']['Identificacion']['Tipo'] ?? null,
                        'Receptor_NumeroIdentificacion' => $datosXml['Receptor']['Identificacion']['Numero'] ?? null,
                        'TotalComprobante'            => $datosXml['ResumenFactura']['TotalComprobante'] ?? 0,
                        'TotalImpuesto'               => $datosXml['ResumenFactura']['TotalImpuesto'] ?? 0,
                        'CodigoMoneda'                => $datosXml['ResumenFactura']['CodigoTipoMoneda']['CodigoMoneda'] ?? 'CRC',
                        'xml_original'                => $xmlContent,
                        'estado'                      => Recepcion::ESTADO_PENDIENTE,
                        'respuesta_tipo'              => $request->respuesta_tipo,
                    ]
                );

                $result = $this->facturacionService->recepcionarDocumento(
                    $xmlContent,
                    $clave,
                    $empresa->id_empresa,
                    $request->respuesta_tipo,
                    $request->detalle_mensaje ?? 'Aceptado',
                    $request->actividad_economica ?? $empresa->CodigoActividad ?? '',
                    $tenantId
                );

                if ($result['success']) {
                    $recepcion->update([
                        'estado'                => Recepcion::ESTADO_ENVIADO,
                        'respuesta_consecutivo' => $result['consecutivo'] ?? null,
                        'respuesta_mensaje'     => $request->detalle_mensaje ?? 'Aceptado',
                    ]);
                    $resultados[] = ['nombre' => $nombre, 'success' => true, 'clave' => $clave];
                } else {
                    $recepcion->update([
                        'estado'  => Recepcion::ESTADO_ERROR,
                        'mensaje' => $result['message'] ?? 'Error desconocido',
                    ]);
                    $resultados[] = ['nombre' => $nombre, 'success' => false, 'message' => $result['message'] ?? 'Error al enviar a Hacienda.'];
                }
            } catch (\Exception $e) {
                Log::error("Error procesando XML masivo [{$nombre}]: " . $e->getMessage());
                $resultados[] = ['nombre' => $nombre, 'success' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'resultados' => $resultados]);
    }
}
