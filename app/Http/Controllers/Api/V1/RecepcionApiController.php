<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Recepcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecepcionApiController extends Controller
{
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

    private function getTenantId(Request $request): int
    {
        if (app()->bound('current_tenant')) return app('current_tenant')->id;
        return $request->user()->tenant_id;
    }
}
