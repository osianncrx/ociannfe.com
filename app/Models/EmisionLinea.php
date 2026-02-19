<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmisionLinea extends Model
{
    protected $table = 'fe_emision_lineas';
    protected $primaryKey = 'id_linea';
    public $timestamps = false;

    protected $fillable = [
        'id_emision',
        'NumeroLinea',
        'Codigo',
        'CodigoComercial',
        'Cantidad',
        'UnidadMedida',
        'Detalle',
        'PrecioUnitario',
        'MontoTotal',
        'Descuento_MontoDescuento',
        'Descuento_NaturalezaDescuento',
        'SubTotal',
        'Impuesto_Codigo',
        'Impuesto_CodigoTarifa',
        'Impuesto_Tarifa',
        'Impuesto_Monto',
        'MontoTotalLinea',
    ];

    protected function casts(): array
    {
        return [
            'CodigoComercial' => 'array',
            'Cantidad' => 'decimal:4',
            'PrecioUnitario' => 'decimal:4',
            'MontoTotal' => 'decimal:2',
            'Descuento_MontoDescuento' => 'decimal:2',
            'SubTotal' => 'decimal:2',
            'Impuesto_Tarifa' => 'decimal:2',
            'Impuesto_Monto' => 'decimal:2',
            'MontoTotalLinea' => 'decimal:2',
        ];
    }

    public function emision(): BelongsTo
    {
        return $this->belongsTo(Emision::class, 'id_emision', 'id_emision');
    }
}
