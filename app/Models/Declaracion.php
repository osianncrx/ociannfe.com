<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Declaracion extends Model
{
    protected $table = 'fe_declaraciones';
    protected $primaryKey = 'id_declaracion';

    protected $fillable = [
        'tenant_id',
        'id_empresa',
        'cedula',
        'tipo_declaracion',
        'periodo_anio',
        'periodo_mes',
        'estado',
        'total_ventas_gravadas',
        'total_ventas_exentas',
        'total_compras_gravadas',
        'total_compras_exentas',
        'total_iva_trasladado',
        'total_iva_acreditable',
        'impuesto_neto',
        'detalle_actividades',
        'detalle_tarifas',
        'datos_calculados',
    ];

    protected function casts(): array
    {
        return [
            'total_ventas_gravadas' => 'decimal:2',
            'total_ventas_exentas' => 'decimal:2',
            'total_compras_gravadas' => 'decimal:2',
            'total_compras_exentas' => 'decimal:2',
            'total_iva_trasladado' => 'decimal:2',
            'total_iva_acreditable' => 'decimal:2',
            'impuesto_neto' => 'decimal:2',
            'detalle_actividades' => 'array',
            'detalle_tarifas' => 'array',
            'datos_calculados' => 'array',
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

    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'borrador' => 'warning',
            'generada' => 'info',
            'presentada' => 'success',
            default => 'secondary',
        };
    }

    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            'borrador' => 'Borrador',
            'generada' => 'Generada',
            'presentada' => 'Presentada',
            default => ucfirst($this->estado ?? ''),
        };
    }

    public function getPeriodoTextoAttribute(): string
    {
        if ($this->tipo_declaracion === 'D-101') {
            return "Año {$this->periodo_anio}";
        }

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($meses[$this->periodo_mes] ?? $this->periodo_mes) . " {$this->periodo_anio}";
    }
}
