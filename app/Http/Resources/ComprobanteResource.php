<?php
declare(strict_types=1);
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComprobanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_emision,
            'clave' => $this->clave,
            'consecutivo' => $this->NumeroConsecutivo,
            'fecha_emision' => $this->FechaEmision,
            'estado' => $this->estado_texto,
            'estado_codigo' => $this->estado,
            'emisor' => [
                'nombre' => $this->Emisor_Nombre,
                'identificacion' => $this->Emisor_NumeroIdentificacion,
            ],
            'receptor' => [
                'nombre' => $this->Receptor_Nombre,
                'identificacion' => $this->Receptor_NumeroIdentificacion,
            ],
            'totales' => [
                'gravado' => $this->TotalGravado,
                'exento' => $this->TotalExento,
                'venta' => $this->TotalVenta,
                'descuentos' => $this->TotalDescuentos,
                'venta_neta' => $this->TotalVentaNeta,
                'impuesto' => $this->TotalImpuesto,
                'comprobante' => $this->TotalComprobante,
            ],
            'mensaje_hacienda' => $this->mensaje,
            'lineas' => $this->whenLoaded('lineas'),
            'empresa_id' => $this->id_empresa,
        ];
    }
}
