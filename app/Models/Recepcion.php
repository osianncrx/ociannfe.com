<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recepcion extends Model
{
    protected $table = 'fe_recepciones';
    protected $primaryKey = 'id_recepcion';
    public $timestamps = false;

    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_ACEPTADO = 3;
    const ESTADO_RECHAZADO = 4;
    const ESTADO_ERROR = 5;

    protected $fillable = [
        'tenant_id',
        'clave',
        'id_empresa',
        'estado',
        'mensaje',
        'NumeroConsecutivo',
        'TipoDocumento',
        'FechaEmision',
        'Emisor_Nombre',
        'Emisor_TipoIdentificacion',
        'Emisor_NumeroIdentificacion',
        'Emisor_CorreoElectronico',
        'Receptor_Nombre',
        'Receptor_TipoIdentificacion',
        'Receptor_NumeroIdentificacion',
        'TotalComprobante',
        'TotalImpuesto',
        'CodigoMoneda',
        'xml_original',
        'respuesta_tipo',
        'respuesta_consecutivo',
        'respuesta_estado',
        'respuesta_mensaje',
        'declarado',
        'id_declaracion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'integer',
            'respuesta_estado' => 'integer',
            'FechaEmision' => 'datetime',
            'TotalComprobante' => 'decimal:2',
            'TotalImpuesto' => 'decimal:2',
            'declarado' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            1 => 'Pendiente',
            2 => 'Enviado',
            3 => 'Aceptado',
            4 => 'Rechazado',
            5 => 'Error',
            default => 'Desconocido',
        };
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            1 => 'warning',
            2 => 'info',
            3 => 'success',
            4 => 'danger',
            5 => 'danger',
            default => 'secondary',
        };
    }

    public function getTipoDocumentoTextoAttribute(): string
    {
        return match ($this->TipoDocumento) {
            '01' => 'Factura Electrónica',
            '02' => 'Nota de Débito',
            '03' => 'Nota de Crédito',
            '04' => 'Tiquete Electrónico',
            '08' => 'Factura Compra',
            '09' => 'Factura Exportación',
            '10' => 'Recibo Electrónico de Pago',
            default => $this->TipoDocumento ?? 'Desconocido',
        };
    }

    public function getRespuestaTipoTextoAttribute(): string
    {
        return match ($this->respuesta_tipo) {
            '05' => 'Aceptación Total',
            '06' => 'Aceptación Parcial',
            '07' => 'Rechazo',
            default => 'Sin respuesta',
        };
    }
}
