<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Recepcion;
use App\Models\Empresa;
use Illuminate\Http\Request;

class RecepcionController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $query = Recepcion::where('tenant_id', $tenantId)
            ->orderByDesc('id_recepcion');
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('empresa')) {
            $query->where('id_empresa', $request->empresa);
        }
        
        $recepciones = $query->paginate(20)->withQueryString();
        $empresas = Empresa::where('tenant_id', $tenantId)->get();
        
        return view('user.recepciones.index', compact('recepciones', 'empresas'));
    }

    public function show(int $id)
    {
        $recepcion = Recepcion::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_recepcion', $id)
            ->firstOrFail();
        return view('user.recepciones.show', compact('recepcion'));
    }

    public function create()
    {
        $empresas = Empresa::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('user.recepciones.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        return redirect()->route('recepciones.index')
            ->with('info', 'Funcionalidad de recepción se completará con la integración del servicio.');
    }
}
