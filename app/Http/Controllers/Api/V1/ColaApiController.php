<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ColaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColaApiController extends Controller
{
    public function __construct(private ColaService $colaService) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);
        $status = $this->colaService->getColaStatus($tenantId);
        return response()->json($status);
    }

    public function procesar(Request $request): JsonResponse
    {
        $result = $this->colaService->procesarCola(30);
        return response()->json([
            'message' => 'Cola procesada.',
            'enviados' => count($result),
            'detalles' => $result,
        ]);
    }

    private function getTenantId(Request $request): int
    {
        if (app()->bound('current_tenant')) return app('current_tenant')->id;
        return $request->user()->tenant_id;
    }
}
