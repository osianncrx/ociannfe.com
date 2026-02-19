<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'max_empresas',
        'max_comprobantes_mes',
        'max_api_keys',
        'has_api_access',
        'has_s3_storage',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'max_empresas' => 'integer',
            'max_comprobantes_mes' => 'integer',
            'max_api_keys' => 'integer',
            'has_api_access' => 'boolean',
            'has_s3_storage' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isUnlimited(string $field): bool
    {
        return ($this->{$field} ?? 0) === -1;
    }
}
