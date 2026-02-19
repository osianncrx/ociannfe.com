<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FacturacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcesarCola extends Command
{
    protected $signature = 'fe:procesar-cola {--timeout=60 : Tiempo máximo en segundos}';
    protected $description = 'Procesa la cola de comprobantes electrónicos pendientes de enviar a Hacienda';

    public function handle(FacturacionService $facturacionService): int
    {
        $timeout = (int) $this->option('timeout');
        $this->info('Procesando cola de comprobantes...');

        try {
            $enviados = $facturacionService->procesarCola($timeout);
            $count = count($enviados);

            if ($count > 0) {
                $this->info("Se procesaron {$count} comprobante(s):");
                foreach ($enviados as $doc) {
                    $estado = $doc['estado'] ?? '?';
                    $tipo = $doc['tipo'] ?? '?';
                    $clave = $doc['clave'] ?? '?';
                    $this->line("  [{$tipo}] {$clave} → estado: {$estado}");
                }
                Log::info("Cola procesada: {$count} comprobante(s) enviados.");
            } else {
                $this->info('No hay comprobantes pendientes en la cola.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error al procesar cola: ' . $e->getMessage());
            Log::error('Error procesando cola: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
