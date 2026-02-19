<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json(['error' => 'API key requerida.'], 401);
        }

        $key = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$key || !$key->isValid()) {
            return response()->json(['error' => 'API key inválida o expirada.'], 401);
        }

        $key->update(['last_used_at' => now()]);

        $tenant = $key->tenant;
        if (!$tenant || !$tenant->is_active) {
            return response()->json(['error' => 'Tenant desactivado.'], 403);
        }

        app()->instance('current_tenant', $tenant);
        app()->instance('current_api_key', $key);

        return $next($request);
    }
}
