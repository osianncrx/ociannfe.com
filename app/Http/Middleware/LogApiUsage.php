<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $elapsed = (int) round((microtime(true) - $start) * 1000);

        try {
            $apiKey = app()->bound('current_api_key') ? app('current_api_key') : null;
            $user = $request->user();
            $tenant = app()->bound('current_tenant')
                ? app('current_tenant')
                : ($user?->tenant ?? null);

            ApiUsageLog::create([
                'api_key_id' => $apiKey?->id,
                'user_id' => $user?->id,
                'tenant_id' => $tenant?->id,
                'method' => $request->method(),
                'endpoint' => '/' . ltrim($request->path(), '/'),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'response_time_ms' => $elapsed,
            ]);
        } catch (\Throwable) {
            // Silently fail — logging should never break the API
        }

        return $response;
    }
}
