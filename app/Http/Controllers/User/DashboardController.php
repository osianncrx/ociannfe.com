<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Emision;
use App\Models\Recepcion;
use App\Models\Empresa;
use App\Models\Cola;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $empresasCount = Empresa::where('tenant_id', $tenantId)->count();
        
        $emisionesHoy = Emision::where('tenant_id', $tenantId)
            ->whereDate('FechaEmision', today())
            ->count();
            
        $emisionesAceptadas = Emision::where('tenant_id', $tenantId)
            ->where('estado', Emision::ESTADO_ACEPTADO)
            ->count();
            
        $emisionesRechazadas = Emision::where('tenant_id', $tenantId)
            ->where('estado', Emision::ESTADO_RECHAZADO)
            ->count();
            
        $emisionesPendientes = Emision::where('tenant_id', $tenantId)
            ->whereIn('estado', [Emision::ESTADO_PENDIENTE, Emision::ESTADO_ENVIADO])
            ->count();

        $totalEmisiones = Emision::where('tenant_id', $tenantId)->count();
        $totalRecepciones = Recepcion::where('tenant_id', $tenantId)->count();
        
        $colaCount = Cola::whereIn('id_empresa', function ($q) use ($tenantId) {
            $q->select('id_empresa')->from('fe_empresas')->where('tenant_id', $tenantId);
        })->where('accion', '<', 3)->count();
        
        $ultimasEmisiones = Emision::where('tenant_id', $tenantId)
            ->orderByDesc('id_emision')
            ->limit(10)
            ->get();

        return view('user.dashboard', compact(
            'empresasCount', 'emisionesHoy', 'emisionesAceptadas',
            'emisionesRechazadas', 'emisionesPendientes', 'totalEmisiones',
            'totalRecepciones', 'colaCount', 'ultimasEmisiones'
        ));
    }
}
