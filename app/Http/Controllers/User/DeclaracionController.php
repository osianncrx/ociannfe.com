<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Declaracion;
use App\Models\Emision;
use App\Models\EmisionLinea;
use App\Models\Empresa;
use App\Models\Recepcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeclaracionController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Declaracion::where('tenant_id', $tenantId)
            ->orderByDesc('periodo_anio')
            ->orderByDesc('periodo_mes');

        if ($request->filled('cedula')) {
            $query->where('cedula', $request->cedula);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo_declaracion', $request->tipo);
        }

        $declaraciones = $query->paginate(20)->withQueryString();

        $cedulas = Empresa::where('tenant_id', $tenantId)
            ->select('cedula')
            ->distinct()
            ->pluck('cedula');

        return view('user.declaraciones.index', compact('declaraciones', 'cedulas'));
    }

    public function create()
    {
        $tenantId = auth()->user()->tenant_id;
        $mesAnterior = now()->subMonth();
        $periodoAnio = $mesAnterior->year;
        $periodoMes = $mesAnterior->month;
        $diaActual = (int) date('d');
        $entreUnoYQuince = $diaActual >= 1 && $diaActual <= 15;

        $cedulas = Empresa::where('tenant_id', $tenantId)
            ->select('cedula', DB::raw('GROUP_CONCAT(Nombre SEPARATOR ", ") as nombres'))
            ->groupBy('cedula')
            ->get();

        $cedulasPendientes = [];

        if ($entreUnoYQuince) {
            foreach ($cedulas as $item) {
                $empresaIds = Empresa::where('tenant_id', $tenantId)
                    ->where('cedula', $item->cedula)
                    ->pluck('id_empresa');

                $pendientesEmisiones = Emision::whereIn('id_empresa', $empresaIds)
                    ->where('tenant_id', $tenantId)
                    ->where('estado', Emision::ESTADO_ACEPTADO)
                    ->where('declarado', 0)
                    ->whereYear('FechaEmision', $periodoAnio)
                    ->whereMonth('FechaEmision', $periodoMes)
                    ->count();

                $pendientesRecepciones = Recepcion::whereIn('id_empresa', $empresaIds)
                    ->where('tenant_id', $tenantId)
                    ->where('estado', Recepcion::ESTADO_ACEPTADO)
                    ->whereIn('respuesta_tipo', ['05', '06'])
                    ->where('declarado', 0)
                    ->whereYear('FechaEmision', $periodoAnio)
                    ->whereMonth('FechaEmision', $periodoMes)
                    ->count();

                $total = $pendientesEmisiones + $pendientesRecepciones;
                if ($total > 0) {
                    $cedulasPendientes[$item->cedula] = $total;
                }
            }
        }

        return view('user.declaraciones.create', compact(
            'cedulas',
            'periodoAnio',
            'periodoMes',
            'entreUnoYQuince',
            'cedulasPendientes'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cedula'       => 'required|string|max:50',
            'periodo_anio' => 'required|integer|min:2018|max:2099',
            'periodo_mes'  => 'required|integer|min:1|max:12',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $cedula = $request->cedula;
        $anio = (int) $request->periodo_anio;
        $mes = (int) $request->periodo_mes;

        $empresaIds = Empresa::where('tenant_id', $tenantId)
            ->where('cedula', $cedula)
            ->pluck('id_empresa');

        if ($empresaIds->isEmpty()) {
            return back()->withErrors(['cedula' => 'No se encontraron empresas con esa cédula.'])->withInput();
        }

        $existing = Declaracion::where('tenant_id', $tenantId)
            ->where('cedula', $cedula)
            ->where('tipo_declaracion', 'D-104')
            ->where('periodo_anio', $anio)
            ->where('periodo_mes', $mes)
            ->first();

        if ($existing) {
            return redirect()->route('declaraciones.show', $existing->id_declaracion)
                ->with('info', 'Ya existe una declaración para este período y cédula. Se muestra la existente.');
        }

        $datos = $this->calcularD104PorCedula($empresaIds->toArray(), $tenantId, $anio, $mes);

        $declaracion = Declaracion::create([
            'tenant_id'             => $tenantId,
            'cedula'                => $cedula,
            'tipo_declaracion'      => 'D-104',
            'periodo_anio'          => $anio,
            'periodo_mes'           => $mes,
            'estado'                => 'generada',
            'total_ventas_gravadas' => $datos['total_ventas_gravadas'],
            'total_ventas_exentas'  => $datos['total_ventas_exentas'],
            'total_compras_gravadas'=> $datos['total_compras_gravadas'],
            'total_compras_exentas' => $datos['total_compras_exentas'],
            'total_iva_trasladado'  => $datos['total_iva_trasladado'],
            'total_iva_acreditable' => $datos['total_iva_acreditable'],
            'impuesto_neto'         => $datos['impuesto_neto'],
            'detalle_actividades'   => $datos['detalle_actividades'],
            'detalle_tarifas'       => $datos['detalle_tarifas'],
            'datos_calculados'      => $datos['datos_calculados'],
        ]);

        $this->marcarDeclarados($declaracion, $empresaIds->toArray(), $tenantId, $anio, $mes);

        return redirect()->route('declaraciones.show', $declaracion->id_declaracion)
            ->with('success', 'Declaración D-104 generada exitosamente.');
    }

    public function show(int $id)
    {
        $declaracion = Declaracion::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_declaracion', $id)
            ->firstOrFail();

        return view('user.declaraciones.show', compact('declaracion'));
    }

    public function update(Request $request, int $id)
    {
        $tenantId = auth()->user()->tenant_id;
        $declaracion = Declaracion::where('tenant_id', $tenantId)
            ->where('id_declaracion', $id)
            ->firstOrFail();

        if ($request->has('recalcular')) {
            $empresaIds = Empresa::where('tenant_id', $tenantId)
                ->where('cedula', $declaracion->cedula)
                ->pluck('id_empresa')
                ->toArray();

            $this->desmarcarDeclarados($declaracion);

            $datos = $this->calcularD104PorCedula(
                $empresaIds,
                $tenantId,
                $declaracion->periodo_anio,
                $declaracion->periodo_mes
            );

            $declaracion->update([
                'total_ventas_gravadas' => $datos['total_ventas_gravadas'],
                'total_ventas_exentas'  => $datos['total_ventas_exentas'],
                'total_compras_gravadas'=> $datos['total_compras_gravadas'],
                'total_compras_exentas' => $datos['total_compras_exentas'],
                'total_iva_trasladado'  => $datos['total_iva_trasladado'],
                'total_iva_acreditable' => $datos['total_iva_acreditable'],
                'impuesto_neto'         => $datos['impuesto_neto'],
                'detalle_actividades'   => $datos['detalle_actividades'],
                'detalle_tarifas'       => $datos['detalle_tarifas'],
                'datos_calculados'      => $datos['datos_calculados'],
                'estado'                => 'generada',
            ]);

            $this->marcarDeclarados($declaracion, $empresaIds, $tenantId, $declaracion->periodo_anio, $declaracion->periodo_mes);

            return redirect()->route('declaraciones.show', $id)
                ->with('success', 'Declaración recalculada exitosamente.');
        }

        if ($request->has('marcar_presentada')) {
            $declaracion->update(['estado' => 'presentada']);
            return redirect()->route('declaraciones.show', $id)
                ->with('success', 'Declaración marcada como presentada.');
        }

        return redirect()->route('declaraciones.show', $id);
    }

    public function destroy(int $id)
    {
        $declaracion = Declaracion::where('tenant_id', auth()->user()->tenant_id)
            ->where('id_declaracion', $id)
            ->firstOrFail();

        if ($declaracion->estado === 'presentada') {
            return redirect()->route('declaraciones.index')
                ->with('error', 'No se puede eliminar una declaración presentada.');
        }

        $this->desmarcarDeclarados($declaracion);
        $declaracion->delete();

        return redirect()->route('declaraciones.index')
            ->with('success', 'Declaración eliminada.');
    }

    private function calcularD104PorCedula(array $empresaIds, int $tenantId, int $anio, int $mes): array
    {
        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fechaFin = date('Y-m-t', strtotime($fechaInicio));

        // --- Emisiones ---
        $emisiones = Emision::whereIn('id_empresa', $empresaIds)
            ->where('tenant_id', $tenantId)
            ->where('estado', Emision::ESTADO_ACEPTADO)
            ->whereDate('FechaEmision', '>=', $fechaInicio)
            ->whereDate('FechaEmision', '<=', $fechaFin)
            ->get();

        $totalVentasGravadas = 0;
        $totalVentasExentas = 0;
        $totalIvaTrasladado = 0;

        $tarifasEmitidos = ['0' => 0, '1' => 0, '2' => 0, '4' => 0, '8' => 0, '13' => 0];
        $ivaEmitidosPorTarifa = ['0' => 0, '1' => 0, '2' => 0, '4' => 0, '8' => 0, '13' => 0];
        $actividadesVentas = [];

        foreach ($emisiones as $emision) {
            $tipoDoc = substr($emision->NumeroConsecutivo ?? '', 8, 2);
            $signo = in_array($tipoDoc, ['03']) ? -1 : 1;

            $totalVentasGravadas += $signo * (float) $emision->TotalGravado;
            $totalVentasExentas += $signo * (float) $emision->TotalExento;
            $totalIvaTrasladado += $signo * (float) $emision->TotalImpuesto;

            $actividad = $emision->CodigoActividad ?? 'Sin actividad';
            if (!isset($actividadesVentas[$actividad])) {
                $actividadesVentas[$actividad] = ['gravado' => 0, 'exento' => 0, 'iva' => 0, 'total' => 0];
            }
            $actividadesVentas[$actividad]['gravado'] += $signo * (float) $emision->TotalGravado;
            $actividadesVentas[$actividad]['exento'] += $signo * (float) $emision->TotalExento;
            $actividadesVentas[$actividad]['iva'] += $signo * (float) $emision->TotalImpuesto;
            $actividadesVentas[$actividad]['total'] += $signo * (float) $emision->TotalComprobante;

            $lineas = EmisionLinea::where('id_emision', $emision->id_emision)->get();
            foreach ($lineas as $linea) {
                $tarifa = (string) (int) ($linea->Impuesto_Tarifa ?? 0);
                if (!isset($tarifasEmitidos[$tarifa])) $tarifa = '13';
                $montoLinea = (float) ($linea->SubTotal ?? $linea->MontoTotal ?? 0);
                $ivaLinea = (float) ($linea->Impuesto_Monto ?? 0);
                $tarifasEmitidos[$tarifa] += $signo * $montoLinea;
                $ivaEmitidosPorTarifa[$tarifa] += $signo * $ivaLinea;
            }
        }

        // --- Recepciones ---
        $recepciones = Recepcion::whereIn('id_empresa', $empresaIds)
            ->where('tenant_id', $tenantId)
            ->where('estado', Recepcion::ESTADO_ACEPTADO)
            ->whereIn('respuesta_tipo', ['05', '06'])
            ->whereDate('FechaEmision', '>=', $fechaInicio)
            ->whereDate('FechaEmision', '<=', $fechaFin)
            ->get();

        $totalComprasGravadas = 0;
        $totalComprasExentas = 0;
        $totalIvaAcreditable = 0;

        $tarifasRecibidos = ['0' => 0, '1' => 0, '2' => 0, '4' => 0, '8' => 0, '13' => 0];
        $ivaRecibidosPorTarifa = ['0' => 0, '1' => 0, '2' => 0, '4' => 0, '8' => 0, '13' => 0];

        foreach ($recepciones as $rec) {
            $lineasXml = $this->parsearLineasXmlRecepcion($rec->xml_original);

            if (!empty($lineasXml)) {
                foreach ($lineasXml as $linea) {
                    $tarifa = (string) (int) ($linea['tarifa'] ?? 0);
                    if (!isset($tarifasRecibidos[$tarifa])) $tarifa = '13';
                    $tarifasRecibidos[$tarifa] += (float) ($linea['subtotal'] ?? 0);
                    $ivaRecibidosPorTarifa[$tarifa] += (float) ($linea['iva_monto'] ?? 0);
                    $totalComprasGravadas += (float) ($linea['subtotal'] ?? 0);
                    $totalIvaAcreditable += (float) ($linea['iva_monto'] ?? 0);
                }
            } else {
                $base = max(0, (float) $rec->TotalComprobante - (float) $rec->TotalImpuesto);
                $iva = (float) $rec->TotalImpuesto;
                $tarifa = $iva > 0 ? '13' : '0';
                $tarifasRecibidos[$tarifa] += $base;
                $ivaRecibidosPorTarifa[$tarifa] += $iva;
                $totalComprasGravadas += $base;
                $totalIvaAcreditable += $iva;
            }
        }

        $impuestoNeto = $totalIvaTrasladado - $totalIvaAcreditable;

        return [
            'total_ventas_gravadas' => round($totalVentasGravadas, 2),
            'total_ventas_exentas'  => round($totalVentasExentas, 2),
            'total_compras_gravadas'=> round($totalComprasGravadas, 2),
            'total_compras_exentas' => round($totalComprasExentas, 2),
            'total_iva_trasladado'  => round($totalIvaTrasladado, 2),
            'total_iva_acreditable' => round($totalIvaAcreditable, 2),
            'impuesto_neto'         => round($impuestoNeto, 2),
            'detalle_actividades'   => $actividadesVentas,
            'detalle_tarifas'       => [
                'emitidos'      => $tarifasEmitidos,
                'recibidos'     => $tarifasRecibidos,
                'emitidos_iva'  => $ivaEmitidosPorTarifa,
                'recibidos_iva' => $ivaRecibidosPorTarifa,
            ],
            'datos_calculados'      => [
                'total_emisiones'       => $emisiones->count(),
                'total_recepciones'     => $recepciones->count(),
                'fecha_calculo'         => now()->setTimezone('America/Costa_Rica')->format('Y-m-d H:i:s'),
                'periodo_inicio'        => $fechaInicio,
                'periodo_fin'           => $fechaFin,
            ],
        ];
    }

    private function parsearLineasXmlRecepcion(?string $xmlString): array
    {
        if (empty($xmlString)) {
            return [];
        }

        try {
            $xmlString = trim($xmlString);
            if (str_starts_with($xmlString, "\xEF\xBB\xBF")) {
                $xmlString = substr($xmlString, 3);
            }

            $xml = new \SimpleXMLElement($xmlString);
            $ns = $xml->getNamespaces(true);
            $prefix = !empty($ns) ? array_key_first($ns) : null;

            $lineas = [];

            $detalleServicio = $prefix
                ? $xml->children($ns[$prefix])->DetalleServicio
                : $xml->DetalleServicio;

            if (!$detalleServicio) {
                return [];
            }

            $lineasDetalle = $prefix
                ? $detalleServicio->children($ns[$prefix])->LineaDetalle
                : $detalleServicio->LineaDetalle;

            foreach ($lineasDetalle as $lineaXml) {
                $nodo = $prefix ? $lineaXml->children($ns[$prefix]) : $lineaXml;

                $subtotal = (float) ($nodo->SubTotal ?? $nodo->MontoTotal ?? 0);
                $tarifa = 0;
                $ivaMonto = 0;

                $impuesto = $nodo->Impuesto ?? null;
                if ($impuesto) {
                    $impNodo = $prefix ? $impuesto->children($ns[$prefix]) : $impuesto;
                    $tarifa = (float) ($impNodo->Tarifa ?? 0);
                    $ivaMonto = (float) ($impNodo->Monto ?? 0);
                }

                $lineas[] = [
                    'subtotal'  => $subtotal,
                    'tarifa'    => $tarifa,
                    'iva_monto' => $ivaMonto,
                ];
            }

            return $lineas;
        } catch (\Throwable $e) {
            Log::warning('Error parseando XML de recepción', [
                'error' => $e->getMessage(),
                'xml_preview' => substr($xmlString, 0, 200),
            ]);
            return [];
        }
    }

    private function marcarDeclarados(Declaracion $declaracion, array $empresaIds, int $tenantId, int $anio, int $mes): void
    {
        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fechaFin = date('Y-m-t', strtotime($fechaInicio));

        Emision::whereIn('id_empresa', $empresaIds)
            ->where('tenant_id', $tenantId)
            ->where('estado', Emision::ESTADO_ACEPTADO)
            ->whereDate('FechaEmision', '>=', $fechaInicio)
            ->whereDate('FechaEmision', '<=', $fechaFin)
            ->update([
                'declarado'      => 1,
                'id_declaracion' => $declaracion->id_declaracion,
            ]);

        Recepcion::whereIn('id_empresa', $empresaIds)
            ->where('tenant_id', $tenantId)
            ->where('estado', Recepcion::ESTADO_ACEPTADO)
            ->whereIn('respuesta_tipo', ['05', '06'])
            ->whereDate('FechaEmision', '>=', $fechaInicio)
            ->whereDate('FechaEmision', '<=', $fechaFin)
            ->update([
                'declarado'      => 1,
                'id_declaracion' => $declaracion->id_declaracion,
            ]);
    }

    private function desmarcarDeclarados(Declaracion $declaracion): void
    {
        Emision::where('id_declaracion', $declaracion->id_declaracion)
            ->update(['declarado' => 0, 'id_declaracion' => null]);

        Recepcion::where('id_declaracion', $declaracion->id_declaracion)
            ->update(['declarado' => 0, 'id_declaracion' => null]);
    }
}
