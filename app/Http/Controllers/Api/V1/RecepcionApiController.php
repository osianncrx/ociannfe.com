<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Recepcion;
use App\Services\FacturacionService;
use Contica\Facturacion\Comprobante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecepcionApiController extends Controller
{
    public function __construct(
        private FacturacionService $facturacionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $query = Recepcion::where('tenant_id', $tenantId)->orderByDesc('id_recepcion');

        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('empresa_id')) $query->where('id_empresa', $request->empresa_id);

        $recepciones = $query->paginate($request->get('per_page', 20));
        return response()->json($recepciones);
    }

    public function show(Request $request, string $clave): JsonResponse
    {
        $recepcion = Recepcion::where('tenant_id', $this->getTenantId($request))
            ->where('clave', $clave)->firstOrFail();
        return response()->json($recepcion);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_empresa'          => 'required|integer',
            'xml'                 => 'required|string',
            'respuesta_tipo'      => 'required|string|in:05,06,07',
            'detalle_mensaje'     => 'nullable|string|max:160',
            'actividad_economica' => 'nullable|string|max:6',
        ]);

        $tenantId = $this->getTenantId($request);

        $empresa = Empresa::where('tenant_id', $tenantId)
            ->where('id_empresa', $request->id_empresa)
            ->firstOrFail();

        $xmlContent = base64_decode($request->xml);
        if ($xmlContent === false) {
            $xmlContent = $request->xml;
        }

        try {
            $datosXml = Comprobante::analizarXML($xmlContent);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'XML inválido: ' . $e->getMessage()], 422);
        }

        if (empty($datosXml)) {
            return response()->json(['success' => false, 'message' => 'No se pudieron extraer datos del XML.'], 422);
        }

        $clave = $datosXml['Clave'] ?? '';
        if (strlen($clave) < 50) {
            return response()->json(['success' => false, 'message' => 'Clave del documento inválida.'], 422);
        }

        $tipoDoc = substr($clave, 29, 2);

        $recepcion = Recepcion::updateOrCreate(
            ['clave' => $clave, 'id_empresa' => $empresa->id_empresa],
            [
                'tenant_id'                     => $tenantId,
                'NumeroConsecutivo'             => $datosXml['NumeroConsecutivo'] ?? null,
                'TipoDocumento'                => $tipoDoc,
                'FechaEmision'                 => $datosXml['FechaEmision'] ?? null,
                'Emisor_Nombre'                => $datosXml['Emisor']['Nombre'] ?? null,
                'Emisor_TipoIdentificacion'    => $datosXml['Emisor']['Identificacion']['Tipo'] ?? null,
                'Emisor_NumeroIdentificacion'  => $datosXml['Emisor']['Identificacion']['Numero'] ?? null,
                'Emisor_CorreoElectronico'     => $datosXml['Emisor']['CorreoElectronico'] ?? null,
                'Receptor_Nombre'              => $datosXml['Receptor']['Nombre'] ?? null,
                'Receptor_TipoIdentificacion'  => $datosXml['Receptor']['Identificacion']['Tipo'] ?? null,
                'Receptor_NumeroIdentificacion'=> $datosXml['Receptor']['Identificacion']['Numero'] ?? null,
                'TotalComprobante'             => $datosXml['ResumenFactura']['TotalComprobante'] ?? 0,
                'TotalImpuesto'                => $datosXml['ResumenFactura']['TotalImpuesto'] ?? 0,
                'CodigoMoneda'                 => $datosXml['ResumenFactura']['CodigoTipoMoneda']['CodigoMoneda'] ?? 'CRC',
                'xml_original'                 => $xmlContent,
                'estado'                       => Recepcion::ESTADO_PENDIENTE,
                'respuesta_tipo'               => $request->respuesta_tipo,
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

                return response()->json([
                    'success'      => true,
                    'clave'        => $clave,
                    'consecutivo'  => $result['consecutivo'] ?? '',
                    'message'      => 'Documento recepcionado exitosamente.',
                ]);
            }

            $recepcion->update([
                'estado'  => Recepcion::ESTADO_ERROR,
                'mensaje' => $result['message'] ?? 'Error desconocido',
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Error al enviar respuesta a Hacienda.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('API: Error al recepcionar documento: ' . $e->getMessage());

            $recepcion->update([
                'estado'  => Recepcion::ESTADO_ERROR,
                'mensaje' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function responder(Request $request, string $clave): JsonResponse
    {
        $request->validate([
            'respuesta_tipo'      => 'required|string|in:05,06,07',
            'detalle_mensaje'     => 'nullable|string|max:160',
            'actividad_economica' => 'nullable|string|max:6',
        ]);

        $tenantId = $this->getTenantId($request);
        $recepcion = Recepcion::where('tenant_id', $tenantId)
            ->where('clave', $clave)
            ->firstOrFail();

        if (empty($recepcion->xml_original)) {
            return response()->json(['success' => false, 'message' => 'No hay XML original almacenado para este documento.'], 422);
        }

        try {
            $result = $this->facturacionService->recepcionarDocumento(
                $recepcion->xml_original,
                $clave,
                $recepcion->id_empresa,
                $request->respuesta_tipo,
                $request->detalle_mensaje ?? 'Aceptado',
                $request->actividad_economica ?? '',
                $tenantId
            );

            if ($result['success']) {
                $recepcion->update([
                    'estado'                => Recepcion::ESTADO_ENVIADO,
                    'respuesta_tipo'        => $request->respuesta_tipo,
                    'respuesta_consecutivo' => $result['consecutivo'] ?? null,
                    'respuesta_mensaje'     => $request->detalle_mensaje ?? 'Aceptado',
                ]);

                return response()->json([
                    'success'     => true,
                    'consecutivo' => $result['consecutivo'] ?? '',
                    'message'     => 'Respuesta enviada a Hacienda.',
                ]);
            }

            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Error.'], 500);
        } catch (\Exception $e) {
            Log::error("API: Error respondiendo a {$clave}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getTenantId(Request $request): int
    {
        if (app()->bound('current_tenant')) return app('current_tenant')->id;
        return $request->user()->tenant_id;
    }
}
