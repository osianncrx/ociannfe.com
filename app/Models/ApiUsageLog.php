<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    protected $fillable = [
        'api_key_id',
        'user_id',
        'tenant_id',
        'method',
        'endpoint',
        'status_code',
        'ip_address',
        'user_agent',
        'response_time_ms',
        'request_body',
        'response_summary',
    ];

    protected function casts(): array
    {
        return [
            'request_body' => 'array',
            'response_summary' => 'array',
        ];
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
