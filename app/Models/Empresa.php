<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Empresa extends Model
{
    protected $table = 'fe_empresas';
    protected $primaryKey = 'id_empresa';
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'id_cliente',
        'id_ambiente',
        'cedula',
        'sucursal',
        'usuario_mh',
        'contra_mh',
        'pin_llave',
        'llave_criptografica',
        'Nombre',
        'Tipo',
        'Numero',
        'NombreComercial',
        'Provincia',
        'Canton',
        'Distrito',
        'Barrio',
        'OtrasSenas',
        'CorreoElectronico',
        'CodigoPais',
        'NumTelefono',
        'CodigoActividad',
        'pdf_logo',
        'pdf_encabezado',
        'pdf_pie_pagina',
        'pdf_color_primario',
        'pdf_mostrar_comentarios',
    ];

    protected function casts(): array
    {
        return [
            'pdf_mostrar_comentarios' => 'boolean',
        ];
    }

    protected $hidden = [
        'usuario_mh',
        'contra_mh',
        'pin_llave',
        'llave_criptografica',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'id_ambiente', 'id_ambiente');
    }

    public function emisiones(): HasMany
    {
        return $this->hasMany(Emision::class, 'id_empresa', 'id_empresa');
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(Recepcion::class, 'id_empresa', 'id_empresa');
    }

    public function cola(): HasMany
    {
        return $this->hasMany(Cola::class, 'id_empresa', 'id_empresa');
    }

    /**
     * Auto-asigna el siguiente número de sucursal disponible para la combinación
     * tenant + cédula + ambiente. Si no existe ninguna, devuelve '001'.
     */
    public static function autoAssignSucursal(int $tenantId, string $cedula, int $idAmbiente): string
    {
        $maxSucursal = static::where('tenant_id', $tenantId)
            ->where('cedula', $cedula)
            ->where('id_ambiente', $idAmbiente)
            ->max(DB::raw('CAST(sucursal AS UNSIGNED)'));

        $siguiente = ($maxSucursal ?? 0) + 1;

        return str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT);
    }
}
