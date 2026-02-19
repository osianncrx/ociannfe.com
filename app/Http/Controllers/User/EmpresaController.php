<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Ambiente;
use App\Services\FacturacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('id_empresa')
            ->paginate(15);
        return view('user.empresas.index', compact('empresas'));
    }

    public function create()
    {
        $ambientes = Ambiente::all();
        return view('user.empresas.create', compact('ambientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cedula' => 'required|string|max:12',
            'Nombre' => 'required|string|max:255',
            'Tipo' => 'required|string|max:2',
            'Numero' => 'required|string|max:12',
            'id_ambiente' => 'required|integer|in:1,2',
            'usuario_mh' => 'required|string|max:512',
            'contra_mh' => 'required|string|max:512',
            'pin_llave' => 'required|string|max:512',
            'llave_criptografica' => 'required|file|max:10240',
            'CorreoElectronico' => 'nullable|email|max:255',
            'Provincia' => 'nullable|string|max:2',
            'Canton' => 'nullable|string|max:3',
            'Distrito' => 'nullable|string|max:3',
            'OtrasSenas' => 'nullable|string|max:255',
            'CodigoActividad' => 'nullable|string|max:255',
        ]);

        $data = $request->except('llave_criptografica');
        $tenantId = auth()->user()->tenant_id;
        $data['tenant_id'] = $tenantId;
        $data['id_cliente'] = (string) $tenantId;

        $sucursal = Empresa::autoAssignSucursal(
            $tenantId,
            $request->cedula,
            (int) $request->id_ambiente
        );
        $data['sucursal'] = $sucursal;

        if ($request->hasFile('llave_criptografica')) {
            $data['llave_criptografica'] = file_get_contents($request->file('llave_criptografica')->getRealPath());
        }

        Empresa::create($data);

        $mensaje = 'Empresa creada exitosamente.';
        if ($sucursal !== '001') {
            $mensaje .= " Se asignó la sucursal {$sucursal} automáticamente (ya existe otra empresa con esta cédula).";
        }

        return redirect()->route('empresas.index')->with('success', $mensaje);
    }

    public function show(int $id)
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();
        return view('user.empresas.show', compact('empresa'));
    }

    public function edit(int $id)
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();
        $ambientes = Ambiente::all();
        return view('user.empresas.edit', compact('empresa', 'ambientes'));
    }

    public function update(Request $request, int $id)
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();

        $rules = [
            'Nombre' => 'required|string|max:255',
            'CorreoElectronico' => 'nullable|email|max:255',
            'Provincia' => 'nullable|string|max:2',
            'Canton' => 'nullable|string|max:3',
            'Distrito' => 'nullable|string|max:3',
            'OtrasSenas' => 'nullable|string|max:255',
            'CodigoActividad' => 'nullable|string|max:255',
        ];
        if ($request->hasFile('llave_criptografica')) {
            $rules['pin_llave'] = 'required|string|max:512';
        }
        $request->validate($rules);

        $data = $request->only([
            'Nombre', 'CorreoElectronico', 'Provincia', 'Canton',
            'Distrito', 'OtrasSenas', 'CodigoActividad', 'NombreComercial',
        ]);

        if ($request->filled('usuario_mh')) {
            $data['usuario_mh'] = $request->usuario_mh;
        }
        if ($request->filled('contra_mh')) {
            $data['contra_mh'] = $request->contra_mh;
        }
        if ($request->filled('pin_llave')) {
            $data['pin_llave'] = $request->pin_llave;
        }
        if ($request->hasFile('llave_criptografica')) {
            $data['llave_criptografica'] = file_get_contents($request->file('llave_criptografica')->getRealPath());
            $data['pin_llave'] = $request->pin_llave;
        }

        $empresa->update($data);
        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada exitosamente.');
    }

    public function destroy(int $id)
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();
        $empresa->delete();
        return redirect()->route('empresas.index')->with('success', 'Empresa eliminada exitosamente.');
    }

    public function verificarCredenciales(Request $request, FacturacionService $facturacionService, int $id): JsonResponse|RedirectResponse
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();

        $result = $facturacionService->verificarCredencialesHacienda($empresa->id_empresa, (int) auth()->user()->tenant_id);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['valid']) {
            return redirect()->route('empresas.show', $empresa)->with('success', $result['message']);
        }

        return redirect()->route('empresas.show', $empresa)->with('error', $result['message']);
    }

    public function lookupCedula(Request $request): JsonResponse
    {
        $request->validate([
            'cedula' => 'required|string|min:9|max:12',
        ]);

        $cedula = preg_replace('/[^0-9]/', '', $request->cedula);

        try {
            $response = Http::timeout(10)
                ->get("https://api.hacienda.go.cr/fe/ae", [
                    'identificacion' => $cedula,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $actividades = collect($data['actividades'] ?? [])
                    ->where('estado', 'A')
                    ->map(function ($act) {
                        $codigo = $act['codigo'] ?? '';
                        $descripcion = $act['descripcion'] ?? '';
                        $codigoCabys = str_replace('.', '', $codigo);

                        $subActividades = collect($act['ciiu3'] ?? [])->map(function ($sub) {
                            return [
                                'codigo' => $sub['codigo'] ?? '',
                                'descripcion' => $sub['descripcion'] ?? '',
                            ];
                        })->values()->all();

                        return [
                            'codigo' => $codigoCabys,
                            'descripcion' => $descripcion,
                            'sub_actividades' => $subActividades,
                        ];
                    })
                    ->values();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'nombre' => $data['nombre'] ?? '',
                        'tipoIdentificacion' => $data['tipoIdentificacion'] ?? '',
                        'regimen' => $data['regimen'] ?? null,
                        'situacion' => $data['situacion'] ?? null,
                        'actividades' => $actividades,
                    ],
                ]);
            }

            if ($response->status() === 404) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un contribuyente con esa cédula.',
                ], 404);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el API de Hacienda.',
            ], $response->status());
        } catch (\Exception $e) {
            Log::warning('Error consultando API Hacienda: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con el API de Hacienda. Puede ingresar los datos manualmente.',
            ], 503);
        }
    }
}
