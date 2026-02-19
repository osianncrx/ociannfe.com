<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ambiente extends Model
{
    protected $table = 'fe_ambientes';
    protected $primaryKey = 'id_ambiente';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'client_id',
        'uri_idp',
        'uri_api',
    ];

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'id_ambiente', 'id_ambiente');
    }
}
