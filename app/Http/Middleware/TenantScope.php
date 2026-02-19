<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            if ($user && $user->hasRole('admin')) {
                return $next($request);
            }
            abort(403, 'No tienes un tenant asignado.');
        }

        $tenant = $user->tenant;
        if (!$tenant || !$tenant->is_active) {
            abort(403, 'Tu cuenta de empresa está desactivada.');
        }

        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}
