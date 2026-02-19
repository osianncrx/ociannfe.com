<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Emision;
use App\Models\Empresa;
use App\Services\FacturacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConsultarEstados extends Command
{
    protected $signature = 'fe:consultar-estados {--limit=50 : Máximo de comprobantes a consultar}';
    protected $description = 'Consulta en Hacienda el estado de comprobantes enviados que aún no tienen respuesta';

    public function handle(FacturacionService $facturacionService): int
    {
        $limit = (int) $this->option('limit');

        $emisiones = Emision::where('estado', Emision::ESTADO_ENVIADO)
            ->orderBy('id_emision')
            ->limit($limit)
            ->get();

        if ($emisiones->isEmpty()) {
            $this->info('No hay comprobantes en estado "Enviado" para consultar.');
            return self::SUCCESS;
        }

        $this->info("Consultando {$emisiones->count()} comprobante(s) en Hacienda...");
        $actualizados = 0;

        foreach ($emisiones as $emision) {
            try {
                $empresa = Empresa::find($emision->id_empresa);
                if (!$empresa) {
                    continue;
                }

                $result = $facturacionService->consultarEstado(
                    (string) $emision->clave,
                    'E',
                    $emision->id_empresa
                );

                $estadoAnterior = $emision->estado;
                $nuevoEstado = match ($result['estado'] ?? '') {
                    'aceptado'  => Emision::ESTADO_ACEPTADO,
                    'rechazado' => Emision::ESTADO_RECHAZADO,
                    'enviado'   => Emision::ESTADO_ENVIADO,
                    'error'     => Emision::ESTADO_ERROR,
                    default     => $emision->estado,
                };

                if ($nuevoEstado !== $estadoAnterior) {
                    $emision->update([
                        'estado'  => $nuevoEstado,
                        'mensaje' => $result['mensaje'] ?? null,
                    ]);
                    $actualizados++;
                    $this->line("  [{$emision->clave}] → {$result['estado']}");
                }
            } catch (\Exception $e) {
                $this->warn("  Error consultando {$emision->clave}: " . $e->getMessage());
            }
        }

        $this->info("Proceso completado. {$actualizados} comprobante(s) actualizado(s).");
        Log::info("Consulta de estados: {$actualizados}/{$emisiones->count()} actualizados.");

        return self::SUCCESS;
    }
}
