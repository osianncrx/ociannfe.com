<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('admin')) {
            return $next($request);
        }

        if (!$user || !$user->tenant_id) {
            abort(403, 'No tienes un tenant asignado.');
        }

        $subscription = $user->tenant->activeSubscription;

        if (!$subscription || !$subscription->isActive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Tu suscripción ha expirado o no está activa.',
                ], 403);
            }
            return redirect()->route('user.dashboard')
                ->with('error', 'Tu suscripción ha expirado. Contacta al administrador.');
        }

        return $next($request);
    }
}
