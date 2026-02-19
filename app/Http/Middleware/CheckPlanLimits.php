<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Emision;
use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    public function handle(Request $request, Closure $next, string $resource = ''): Response
    {
        $user = $request->user();

        if (!$user || $user->hasRole('admin')) {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            abort(403, 'No tienes un tenant asignado.');
        }

        $subscription = $tenant->activeSubscription;
        if (!$subscription) {
            return $this->limitExceeded($request, 'No tienes una suscripción activa.');
        }

        $plan = $subscription->plan;
        if (!$plan) {
            return $next($request);
        }

        if ($resource === 'empresas' && !$plan->isUnlimited('max_empresas')) {
            $currentCount = Empresa::where('tenant_id', $tenant->id)->count();
            if ($currentCount >= $plan->max_empresas) {
                return $this->limitExceeded(
                    $request,
                    "Has alcanzado el límite de {$plan->max_empresas} empresas de tu plan {$plan->name}."
                );
            }
        }

        if ($resource === 'comprobantes' && !$plan->isUnlimited('max_comprobantes_mes')) {
            $currentMonth = Emision::where('tenant_id', $tenant->id)
                ->whereMonth('FechaEmision', now()->month)
                ->whereYear('FechaEmision', now()->year)
                ->count();

            if ($currentMonth >= $plan->max_comprobantes_mes) {
                return $this->limitExceeded(
                    $request,
                    "Has alcanzado el límite de {$plan->max_comprobantes_mes} comprobantes/mes de tu plan {$plan->name}."
                );
            }
        }

        return $next($request);
    }

    private function limitExceeded(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message, 'upgrade_required' => true], 429);
        }

        return redirect()->back()->with('error', $message);
    }
}
