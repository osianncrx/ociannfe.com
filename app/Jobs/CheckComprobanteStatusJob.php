<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Emision;
use App\Services\FacturacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckComprobanteStatusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [30, 120, 600];

    public function __construct(
        private string $clave,
        private string $tipo,
        private int $idEmpresa
    ) {}

    public function handle(FacturacionService $facturacionService): void
    {
        try {
            $result = $facturacionService->consultarEstado(
                $this->clave,
                $this->tipo,
                $this->idEmpresa
            );

            Log::info("Estado consultado para {$this->clave}: {$result['estado']}");
        } catch (\Exception $e) {
            Log::error("Error consultando estado de {$this->clave}: " . $e->getMessage());
            throw $e;
        }
    }
}
