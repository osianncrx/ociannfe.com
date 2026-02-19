<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Emision;
use App\Models\Empresa;
use App\Services\FacturacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComprobanteController extends Controller
{
    public function __construct(
        private FacturacionService $facturacionService,
    ) {}

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
            'id_empresa'         => 'required|integer',
            'tipo_documento'     => 'required|string|in:01,02,03,04,08,09',
            'condicion_venta'    => 'required|string|in:01,02,03,04,05,06,07,08,09,99',
            'medio_pago'         => 'required|string|in:01,02,03,04,05,99',
            'receptor_nombre'    => 'required|string|max:255',
            'receptor_tipo_id'   => 'nullable|string|in:01,02,03,04',
            'receptor_numero_id' => 'nullable|string|max:12',
            'receptor_email'     => 'nullable|email|max:255',
            'lineas'             => 'required|array|min:1',
            'lineas.*.detalle'          => 'required|string|max:255',
            'lineas.*.cantidad'         => 'required|numeric|min:0.01',
            'lineas.*.precio_unitario'  => 'required|numeric|min:0',
            'lineas.*.codigo_cabys'     => 'nullable|string|max:20',
            'lineas.*.unidad'           => 'required|string|max:20',
            'lineas.*.tarifa_iva'       => 'required|numeric|min:0|max:100',
        ], [
            'id_empresa.required'              => 'Seleccione una empresa emisora.',
            'tipo_documento.required'          => 'Seleccione el tipo de documento.',
            'condicion_venta.required'         => 'Seleccione la condición de venta.',
            'medio_pago.required'              => 'Seleccione el medio de pago.',
            'receptor_nombre.required'         => 'El nombre del receptor es obligatorio.',
            'lineas.required'                  => 'Debe agregar al menos una línea de detalle.',
            'lineas.min'                       => 'Debe agregar al menos una línea de detalle.',
            'lineas.*.detalle.required'        => 'El detalle de cada línea es obligatorio.',
            'lineas.*.cantidad.required'       => 'La cantidad es obligatoria.',
            'lineas.*.precio_unitario.required'=> 'El precio unitario es obligatorio.',
        ]);

        $tenantId = auth()->user()->tenant_id;

        $empresa = Empresa::where('tenant_id', $tenantId)
            ->where('id_empresa', $request->id_empresa)
            ->firstOrFail();

        $receptor = [
            'Nombre' => $request->receptor_nombre,
        ];
        if ($request->filled('receptor_tipo_id') && $request->filled('receptor_numero_id')) {
            $receptor['Identificacion'] = [
                'Tipo'   => $request->receptor_tipo_id,
                'Numero' => $request->receptor_numero_id,
            ];
        }
        if ($request->filled('receptor_email')) {
            $receptor['CorreoElectronico'] = $request->receptor_email;
        }

        $lineas = [];
        foreach ($request->lineas as $i => $linea) {
            $cantidad = (float) $linea['cantidad'];
            $precio   = (float) $linea['precio_unitario'];
            $tarifa   = (float) $linea['tarifa_iva'];
            $montoTotal = $cantidad * $precio;
            $montoImpuesto = $montoTotal * ($tarifa / 100);

            $lineaData = [
                'NumeroLinea'    => $i + 1,
                'Codigo'         => $linea['codigo_cabys'] ?? '',
                'CodigoCABYS'    => $linea['codigo_cabys'] ?? '',
                'Cantidad'       => $cantidad,
                'UnidadMedida'   => $linea['unidad'],
                'Detalle'        => $linea['detalle'],
                'PrecioUnitario' => $precio,
                'MontoTotal'     => $montoTotal,
                'SubTotal'       => $montoTotal,
                'MontoTotalLinea'=> $montoTotal + $montoImpuesto,
            ];

            if ($tarifa > 0) {
                $codigoTarifa = match ((int) $tarifa) {
                    1  => '01',
                    2  => '02',
                    4  => '03',
                    8  => '08',
                    13 => '08',
                    default => '08',
                };
                $lineaData['Impuesto'] = [
                    'Codigo'        => '01',
                    'CodigoTarifa'  => $codigoTarifa,
                    'Tarifa'        => $tarifa,
                    'Monto'         => $montoImpuesto,
                ];
            }

            $lineas[] = $lineaData;
        }

        $comprobanteData = [
            'id_empresa'      => (int) $request->id_empresa,
            'TipoDoc'         => $request->tipo_documento,
            'CondicionVenta'  => $request->condicion_venta,
            'MediosPago'      => [$request->medio_pago],
            'Receptor'        => $receptor,
            'Lineas'          => $lineas,
        ];

        try {
            $result = $this->facturacionService->enviarComprobante(
                $comprobanteData,
                (int) $request->id_empresa,
                $tenantId
            );

            if ($result['success']) {
                return redirect()->route('comprobantes.index')
                    ->with('success', 'Comprobante emitido exitosamente. Clave: ' . ($result['clave'] ?? '') . ' — Consecutivo: ' . ($result['consecutivo'] ?? ''));
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al emitir comprobante: ' . ($result['message'] ?? 'Error desconocido.'));
        } catch (\Exception $e) {
            Log::error('Error emitiendo comprobante desde web: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error inesperado al emitir el comprobante: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {
        $comprobante = Emision::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_emision', $id)
            ->with('lineas')
            ->firstOrFail();
        return view('user.comprobantes.show', compact('comprobante'));
    }

    public function procesarEnvio(string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $comprobante = Emision::where('tenant_id', $tenantId)
            ->where('id_emision', $id)
            ->firstOrFail();

        if ($comprobante->estado >= Emision::ESTADO_ACEPTADO) {
            return redirect()->route('comprobantes.show', $id)
                ->with('info', 'Este comprobante ya fue procesado por Hacienda.');
        }

        try {
            $result = $this->facturacionService->procesarCola(30);
            $count = count($result);

            if ($count > 0) {
                return redirect()->route('comprobantes.show', $id)
                    ->with('success', "Cola procesada: {$count} comprobante(s) enviado(s) a Hacienda.");
            }

            return redirect()->route('comprobantes.show', $id)
                ->with('info', 'No hay comprobantes pendientes en la cola de envío.');
        } catch (\Exception $e) {
            Log::error('Error procesando cola manual: ' . $e->getMessage());
            return redirect()->route('comprobantes.show', $id)
                ->with('error', 'Error al procesar envío: ' . $e->getMessage());
        }
    }

    public function consultarEstado(string $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $comprobante = Emision::where('tenant_id', $tenantId)
            ->where('id_emision', $id)
            ->firstOrFail();

        if ($comprobante->estado >= Emision::ESTADO_ACEPTADO) {
            return redirect()->route('comprobantes.show', $id)
                ->with('info', 'Este comprobante ya tiene respuesta de Hacienda.');
        }

        if ($comprobante->estado < Emision::ESTADO_ENVIADO) {
            return redirect()->route('comprobantes.show', $id)
                ->with('warning', 'Primero debe enviar el comprobante a Hacienda.');
        }

        try {
            $result = $this->facturacionService->consultarEstado(
                (string) $comprobante->clave,
                'E',
                $comprobante->id_empresa
            );

            $nuevoEstado = match ($result['estado'] ?? '') {
                'aceptado'  => Emision::ESTADO_ACEPTADO,
                'rechazado' => Emision::ESTADO_RECHAZADO,
                'enviado'   => Emision::ESTADO_ENVIADO,
                'error'     => Emision::ESTADO_ERROR,
                default     => $comprobante->estado,
            };

            if ($nuevoEstado !== $comprobante->estado) {
                $comprobante->update([
                    'estado'  => $nuevoEstado,
                    'mensaje' => $result['mensaje'] ?? null,
                ]);
            }

            $estadoTexto = $result['estado'] ?? 'desconocido';
            return redirect()->route('comprobantes.show', $id)
                ->with('success', "Estado consultado en Hacienda: {$estadoTexto}.");
        } catch (\Exception $e) {
            Log::error('Error consultando estado: ' . $e->getMessage());
            return redirect()->route('comprobantes.show', $id)
                ->with('error', 'Error al consultar estado: ' . $e->getMessage());
        }
    }

    public function xml(string $clave)
    {
        $comprobante = Emision::where('clave', $clave)->firstOrFail();

        $xml = $comprobante->xml_comprobante;

        if (!$xml) {
            $xml = $this->facturacionService->cogerXml($clave, 'E', 1, $comprobante->id_empresa);
        }

        if (!$xml) {
            abort(404, 'XML no disponible');
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "inline; filename=\"{$clave}.xml\"",
        ]);
    }

    public function buscarCabys(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->q;

        try {
            $params = ['top' => 10];

            if (preg_match('/^\d+$/', $query)) {
                $params['codigo'] = $query;
            } else {
                $params['q'] = $query;
            }

            $response = Http::timeout(8)
                ->get('https://api.hacienda.go.cr/fe/cabys', $params);

            if ($response->successful()) {
                $data = $response->json();
                $results = collect($data['cabys'] ?? [])->map(fn ($item) => [
                    'codigo' => $item['codigo'],
                    'descripcion' => $item['descripcion'],
                    'impuesto' => $item['impuesto'] ?? 13,
                    'categoria' => $item['categorias'][0] ?? '',
                ])->values();

                return response()->json([
                    'success' => true,
                    'total' => $data['total'] ?? 0,
                    'results' => $results,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se encontraron resultados.',
                'results' => [],
            ]);
        } catch (\Exception $e) {
            Log::warning('Error consultando API CABYS: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar con el API de Hacienda.',
                'results' => [],
            ], 503);
        }
    }
}
