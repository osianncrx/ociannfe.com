<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\FacturacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessColaJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries = 1;

    public function handle(FacturacionService $facturacionService): void
    {
        Log::info('Iniciando procesamiento de cola de comprobantes.');

        try {
            $enviados = $facturacionService->procesarCola(60);
            Log::info('Cola procesada. Documentos enviados: ' . count($enviados));
        } catch (\Exception $e) {
            Log::error('Error al procesar cola: ' . $e->getMessage());
        }
    }
}
