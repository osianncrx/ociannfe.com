<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenHacienda extends Model
{
    protected $table = 'fe_tokens';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'cedula',
        'id_ambiente',
        'access_token',
        'expires_in',
        'refresh_token',
        'refresh_expires_in',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function isValid(): bool
    {
        return $this->expires_in > (time() + 45);
    }

    public function canRefresh(): bool
    {
        return $this->refresh_expires_in > (time() + 45);
    }
}
