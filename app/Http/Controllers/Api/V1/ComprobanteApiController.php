<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmitirComprobanteRequest;
use App\Http\Resources\ComprobanteResource;
use App\Models\Emision;
use App\Models\Empresa;
use App\Services\FacturacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ComprobanteApiController extends Controller
{
    public function __construct(private FacturacionService $facturacionService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $this->getTenantId($request);
        $query = Emision::where('tenant_id', $tenantId)->orderByDesc('id_emision');
        
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        if ($request->filled('empresa_id')) $query->where('id_empresa', $request->empresa_id);
        if ($request->filled('fecha_desde')) $query->whereDate('FechaEmision', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->whereDate('FechaEmision', '<=', $request->fecha_hasta);
        
        return ComprobanteResource::collection($query->paginate($request->get('per_page', 20)));
    }

    public function emitir(EmitirComprobanteRequest $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $idEmpresa = (int) $request->id_empresa;

        Empresa::where('tenant_id', $tenantId)->where('id_empresa', $idEmpresa)->firstOrFail();

        $result = $this->facturacionService->enviarComprobante(
            $request->validated(),
            $idEmpresa,
            $tenantId
        );

        $status = $result['success'] ? 201 : 422;
        return response()->json($result, $status);
    }

    public function show(Request $request, string $clave): ComprobanteResource
    {
        $comprobante = Emision::where('tenant_id', $this->getTenantId($request))
            ->where('clave', $clave)->with('lineas')->firstOrFail();
        return new ComprobanteResource($comprobante);
    }

    public function estado(Request $request, string $clave): JsonResponse
    {
        $comprobante = Emision::where('tenant_id', $this->getTenantId($request))
            ->where('clave', $clave)->firstOrFail();

        $result = $this->facturacionService->consultarEstado(
            $clave, 'E', $comprobante->id_empresa
        );

        return response()->json($result);
    }

    public function xml(Request $request, string $clave): \Illuminate\Http\Response|JsonResponse
    {
        $comprobante = Emision::where('tenant_id', $this->getTenantId($request))
            ->where('clave', $clave)->firstOrFail();

        $xml = $this->facturacionService->cogerXml($clave, 'E', 1, $comprobante->id_empresa);

        if (!$xml) {
            return response()->json(['error' => 'XML no disponible.'], 404);
        }

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function getTenantId(Request $request): int
    {
        if (app()->bound('current_tenant')) return app('current_tenant')->id;
        return $request->user()->tenant_id;
    }
}
