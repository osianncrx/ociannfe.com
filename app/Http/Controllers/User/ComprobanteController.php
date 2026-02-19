<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Emision;
use App\Models\Empresa;
use Illuminate\Http\Request;

class ComprobanteController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        $query = Emision::where('tenant_id', $tenantId)
            ->orderByDesc('id_emision');
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('empresa')) {
            $query->where('id_empresa', $request->empresa);
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('clave', 'like', "%{$buscar}%")
                  ->orWhere('Receptor_Nombre', 'like', "%{$buscar}%")
                  ->orWhere('NumeroConsecutivo', 'like', "%{$buscar}%");
            });
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('FechaEmision', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('FechaEmision', '<=', $request->fecha_hasta);
        }
        
        $comprobantes = $query->paginate(20)->withQueryString();
        $empresas = Empresa::where('tenant_id', $tenantId)->get();
        
        return view('user.comprobantes.index', compact('comprobantes', 'empresas'));
    }

    public function create()
    {
        $empresas = Empresa::where('tenant_id', auth()->user()->tenant_id)->get();
        return view('user.comprobantes.create', compact('empresas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_empresa' => 'required|integer',
            'Receptor' => 'required|array',
            'Receptor.Nombre' => 'required|string|max:255',
            'CondicionVenta' => 'required|string|in:01,02,03,04,05,06,07,08,09,10,11,12,13,99',
            'Lineas' => 'required|array|min:1',
            'Lineas.*.Detalle' => 'required|string',
            'Lineas.*.Cantidad' => 'required|numeric|min:0.01',
            'Lineas.*.PrecioUnitario' => 'required|numeric|min:0',
        ]);

        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $request->id_empresa)
            ->firstOrFail();

        // The actual submission is delegated to the FacturacionService
        // For now, redirect with a message
        return redirect()->route('comprobantes.index')
            ->with('info', 'Funcionalidad de emisión se completará con la integración del servicio de facturación.');
    }

    public function show(string $id)
    {
        $comprobante = Emision::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_emision', $id)
            ->with('lineas')
            ->firstOrFail();
        return view('user.comprobantes.show', compact('comprobante'));
    }

    public function xml(string $clave)
    {
        $comprobante = Emision::where('tenant_id', auth()->user()->tenant_id)
            ->where('clave', $clave)
            ->firstOrFail();
        
        if (!$comprobante->xml_comprobante) {
            abort(404, 'XML no disponible');
        }

        return response($comprobante->xml_comprobante, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "inline; filename=\"{$clave}.xml\"",
        ]);
    }
}
