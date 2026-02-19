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

    protected $fillable = [
        'tenant_id',
        'clave',
        'id_empresa',
        'estado',
        'mensaje',
        'NumeroConsecutivo',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'integer',
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
}
