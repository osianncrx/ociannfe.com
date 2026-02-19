<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\FacturacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendComprobanteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        private array $comprobanteData,
        private int $idEmpresa,
        private int $tenantId
    ) {}

    public function handle(FacturacionService $facturacionService): void
    {
        try {
            $result = $facturacionService->enviarComprobante(
                $this->comprobanteData,
                $this->idEmpresa,
                $this->tenantId
            );

            if ($result['success']) {
                Log::info("Comprobante enviado exitosamente: {$result['clave']}");
            } else {
                Log::warning("Fallo al enviar comprobante: {$result['message']}");
            }
        } catch (\Exception $e) {
            Log::error('Error en SendComprobanteJob: ' . $e->getMessage());
            throw $e;
        }
    }
}
