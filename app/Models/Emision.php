<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Emision extends Model
{
    protected $table = 'fe_emisiones';
    protected $primaryKey = 'id_emision';
    public $timestamps = false;

    const ESTADO_PENDIENTE = 1;
    const ESTADO_ENVIADO = 2;
    const ESTADO_ACEPTADO = 3;
    const ESTADO_RECHAZADO = 4;
    const ESTADO_ERROR = 5;

    protected $fillable = [
        'tenant_id',
        'clave',
        'CodigoActividad',
        'NumeroConsecutivo',
        'FechaEmision',
        'Emisor_Nombre',
        'Emisor_TipoIdentificacion',
        'Emisor_NumeroIdentificacion',
        'Emisor_Provincia',
        'Emisor_Canton',
        'Emisor_Distrito',
        'Emisor_OtrasSenas',
        'Emisor_CorreoElectronico',
        'Receptor_Nombre',
        'Receptor_TipoIdentificacion',
        'Receptor_NumeroIdentificacion',
        'Receptor_Provincia',
        'Receptor_Canton',
        'Receptor_Distrito',
        'Receptor_OtrasSenas',
        'Receptor_CorreoElectronico',
        'CondicionVenta',
        'MedioPago',
        'TotalServGravados',
        'TotalMercanciasExentas',
        'TotalGravado',
        'TotalExento',
        'TotalVenta',
        'TotalDescuentos',
        'TotalVentaNeta',
        'TotalImpuesto',
        'TotalComprobante',
        'id_empresa',
        'estado',
        'mensaje',
        'xml_comprobante',
        'declarado',
        'id_declaracion',
    ];

    protected function casts(): array
    {
        return [
            'FechaEmision' => 'datetime',
            'TotalServGravados' => 'decimal:2',
            'TotalMercanciasExentas' => 'decimal:2',
            'TotalGravado' => 'decimal:2',
            'TotalExento' => 'decimal:2',
            'TotalVenta' => 'decimal:2',
            'TotalDescuentos' => 'decimal:2',
            'TotalVentaNeta' => 'decimal:2',
            'TotalImpuesto' => 'decimal:2',
            'TotalComprobante' => 'decimal:2',
            'estado' => 'integer',
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

    public function lineas(): HasMany
    {
        return $this->hasMany(EmisionLinea::class, 'id_emision', 'id_emision');
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

    public function getTipoDocumentoAttribute(): ?string
    {
        $consecutivo = $this->NumeroConsecutivo;
        if ($consecutivo && strlen($consecutivo) >= 10) {
            return substr($consecutivo, 8, 2);
        }
        return null;
    }

    public function getTipoDocumentoTextoAttribute(): string
    {
        return match ($this->tipo_documento) {
            '01' => 'Factura Electrónica',
            '02' => 'Nota de Débito',
            '03' => 'Nota de Crédito',
            '04' => 'Tiquete Electrónico',
            '08' => 'Factura Compra',
            '09' => 'Factura Exportación',
            '10' => 'Recibo Electrónico de Pago',
            default => 'Documento',
        };
    }

    public function permiteNotaCredito(): bool
    {
        return $this->estado === self::ESTADO_ACEPTADO
            && in_array($this->tipo_documento, ['01', '02', '04', '08', '09']);
    }

    public function permiteNotaDebito(): bool
    {
        return $this->estado === self::ESTADO_ACEPTADO
            && in_array($this->tipo_documento, ['01', '03', '04', '08', '09']);
    }
}
