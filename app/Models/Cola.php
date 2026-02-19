<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cola extends Model
{
    protected $table = 'fe_cola';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'clave';

    const ACCION_ENVIAR_EMISION = 1;
    const ACCION_ENVIAR_RECEPCION = 2;
    const ACCION_EMISION_DESACTIVADA = 3;
    const ACCION_RECEPCION_DESACTIVADA = 4;

    protected $fillable = [
        'id_empresa',
        'clave',
        'accion',
        'tiempo_creado',
        'tiempo_enviar',
        'intentos_envio',
        'respuesta_envio',
        'mensaje',
    ];

    protected function casts(): array
    {
        return [
            'accion' => 'integer',
            'tiempo_creado' => 'integer',
            'tiempo_enviar' => 'integer',
            'intentos_envio' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function getTipoAttribute(): string
    {
        return $this->accion <= 2
            ? ($this->accion === 1 ? 'E' : 'R')
            : ($this->accion === 3 ? 'E' : 'R');
    }

    public function isActive(): bool
    {
        return $this->accion <= 2;
    }
}
