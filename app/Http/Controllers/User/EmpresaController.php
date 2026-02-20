<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Emision;
use App\Models\EmisionLinea;
use App\Models\Empresa;
use App\Models\Ambiente;
use App\Services\FacturacionService;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function editPlantillaPdf(int $id)
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();
        return view('user.empresas.plantilla-pdf', compact('empresa'));
    }

    public function updatePlantillaPdf(Request $request, int $id)
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();

        $request->validate([
            'pdf_logo'                 => 'nullable|image|max:2048',
            'pdf_encabezado'           => 'nullable|string|max:1000',
            'pdf_pie_pagina'           => 'nullable|string|max:2000',
            'pdf_color_primario'       => 'nullable|string|max:7',
            'pdf_mostrar_comentarios'  => 'nullable',
        ]);

        $data = $request->only(['pdf_encabezado', 'pdf_pie_pagina', 'pdf_color_primario']);
        $data['pdf_mostrar_comentarios'] = $request->has('pdf_mostrar_comentarios');

        if ($request->hasFile('pdf_logo')) {
            $file = $request->file('pdf_logo');
            $mime = $file->getMimeType();
            $content = file_get_contents($file->getRealPath());
            $data['pdf_logo'] = 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        if ($request->has('eliminar_logo') && $request->eliminar_logo) {
            $data['pdf_logo'] = null;
        }

        $empresa->update($data);

        return redirect()->route('empresas.plantilla-pdf', $empresa->id_empresa)
            ->with('success', 'Plantilla PDF actualizada exitosamente.');
    }

    public function previewPlantillaPdf(int $id)
    {
        $empresa = Empresa::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_empresa', $id)
            ->firstOrFail();

        $comprobante = new Emision([
            'NumeroConsecutivo' => '00100001010000000001',
            'clave' => '50617022600310293428500100001010000000001199999999',
            'FechaEmision' => now(),
            'Emisor_Nombre' => $empresa->Nombre,
            'Emisor_TipoIdentificacion' => $empresa->Tipo,
            'Emisor_NumeroIdentificacion' => $empresa->cedula,
            'Emisor_CorreoElectronico' => $empresa->CorreoElectronico,
            'Receptor_Nombre' => 'Cliente de Ejemplo S.A.',
            'Receptor_TipoIdentificacion' => '02',
            'Receptor_NumeroIdentificacion' => '3101234567',
            'Receptor_CorreoElectronico' => 'cliente@ejemplo.com',
            'Receptor_OtrasSenas' => 'San José, Costa Rica',
            'CondicionVenta' => '01',
            'MedioPago' => '02',
            'TotalGravado' => 100000,
            'TotalExento' => 0,
            'TotalVenta' => 100000,
            'TotalDescuentos' => 0,
            'TotalVentaNeta' => 100000,
            'TotalImpuesto' => 13000,
            'TotalComprobante' => 113000,
        ]);

        $linea1 = new EmisionLinea([
            'NumeroLinea' => 1,
            'Codigo' => 'PROD001',
            'Detalle' => 'Producto de ejemplo A',
            'Cantidad' => 2,
            'UnidadMedida' => 'Unidad',
            'PrecioUnitario' => 25000,
            'MontoTotal' => 50000,
            'SubTotal' => 50000,
            'Impuesto_Codigo' => '01',
            'Impuesto_CodigoTarifa' => '08',
            'Impuesto_Tarifa' => 13,
            'Impuesto_Monto' => 6500,
            'MontoTotalLinea' => 56500,
        ]);

        $linea2 = new EmisionLinea([
            'NumeroLinea' => 2,
            'Codigo' => 'SERV002',
            'Detalle' => 'Servicio de ejemplo B',
            'Cantidad' => 1,
            'UnidadMedida' => 'Sp',
            'PrecioUnitario' => 50000,
            'MontoTotal' => 50000,
            'SubTotal' => 50000,
            'Impuesto_Codigo' => '01',
            'Impuesto_CodigoTarifa' => '08',
            'Impuesto_Tarifa' => 13,
            'Impuesto_Monto' => 6500,
            'MontoTotalLinea' => 56500,
        ]);

        $comprobante->setRelation('lineas', collect([$linea1, $linea2]));
        $comprobante->setRelation('empresa', $empresa);

        $pdf = Pdf::loadView('pdf.comprobante', compact('comprobante', 'empresa'))
            ->setPaper('letter');

        return $pdf->stream('Vista_Previa_' . $empresa->Nombre . '.pdf');
    }
}
