<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    public const SUPER_ADMIN_EMAIL = 'pablo@ociann.com';

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->isSuperAdmin()) {
                throw new \Exception('El super administrador no puede ser eliminado.');
            }
        });

        static::updating(function (User $user) {
            if ($user->isSuperAdmin()) {
                if ($user->isDirty('is_active') && !$user->is_active) {
                    throw new \Exception('El super administrador no puede ser desactivado.');
                }
                if ($user->isDirty('email') && $user->email !== self::SUPER_ADMIN_EMAIL) {
                    throw new \Exception('El email del super administrador no puede ser cambiado.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }
}
