<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FacturacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function hacienda(Request $request, FacturacionService $facturacionService): JsonResponse
    {
        $body = $request->getContent();
        Log::info('Webhook Hacienda recibido: ' . substr($body, 0, 200));

        try {
            $result = $facturacionService->procesarCallback($body);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error procesando webhook Hacienda: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
