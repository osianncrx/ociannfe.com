<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cola;
use App\Models\Emision;
use App\Models\Recepcion;
use Illuminate\Support\Facades\Log;

class ColaService
{
    private FacturacionService $facturacionService;

    public function __construct(FacturacionService $facturacionService)
    {
        $this->facturacionService = $facturacionService;
    }

    public function procesarCola(int $timeout = 60): array
    {
        return $this->facturacionService->procesarCola($timeout);
    }

    public function getColaStatus(?int $tenantId = null): array
    {
        $query = Cola::where('accion', '<', 3);

        if ($tenantId) {
            $query->whereIn('id_empresa', function ($q) use ($tenantId) {
                $q->select('id_empresa')
                    ->from('fe_empresas')
                    ->where('tenant_id', $tenantId);
            });
        }

        $pendientes = (clone $query)->count();

        $conError = Cola::where('accion', '>=', 3);
        if ($tenantId) {
            $conError->whereIn('id_empresa', function ($q) use ($tenantId) {
                $q->select('id_empresa')
                    ->from('fe_empresas')
                    ->where('tenant_id', $tenantId);
            });
        }

        return [
            'pendientes' => $pendientes,
            'con_error' => $conError->count(),
            'items' => $query->orderBy('tiempo_enviar')->limit(50)->get(),
        ];
    }

    public function reintentarDocumento(string $clave): bool
    {
        $cola = Cola::where('clave', $clave)->first();

        if (!$cola) {
            return false;
        }

        $nuevaAccion = $cola->accion > 2 ? $cola->accion - 2 : $cola->accion;

        $cola->update([
            'accion' => $nuevaAccion,
            'intentos_envio' => 0,
            'tiempo_enviar' => time(),
        ]);

        return true;
    }
}
