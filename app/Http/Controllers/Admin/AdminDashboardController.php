<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Emision;
use App\Models\Empresa;
use App\Models\Cola;
use App\Models\Plan;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'tenants' => Tenant::count(),
            'users' => User::count(),
            'empresas' => Empresa::count(),
            'emisiones_total' => Emision::count(),
            'emisiones_hoy' => Emision::whereDate('FechaEmision', today())->count(),
            'emisiones_aceptadas' => Emision::where('estado', 3)->count(),
            'emisiones_rechazadas' => Emision::where('estado', 4)->count(),
            'cola_pendiente' => Cola::where('accion', '<', 3)->count(),
            'plans' => Plan::where('is_active', true)->count(),
        ];
        
        $ultimostenants = Tenant::latest()->limit(5)->get();
        $ultimasEmisiones = Emision::orderByDesc('id_emision')->limit(10)->get();
        
        return view('admin.dashboard', compact('stats', 'ultimostenants', 'ultimasEmisiones'));
    }
}
