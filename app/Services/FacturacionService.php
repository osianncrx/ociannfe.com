<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Empresa;
use App\Models\Emision;
use App\Models\EmisionLinea;
use App\Models\Recepcion;
use App\Models\Cola;
use App\Models\Ambiente;
use Contica\Facturacion\FacturadorElectronico;
use Contica\Facturacion\Comprobante;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacturacionService
{
    private ?FacturadorElectronico $facturador = null;

    public function getFacturador(): FacturadorElectronico
    {
        if ($this->facturador === null) {
            $db = new \mysqli(
                config('database.connections.mysql.host'),
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.database')
            );

            if ($db->connect_error) {
                throw new Exception('Error de conexión a la base de datos: ' . $db->connect_error);
            }

            $db->set_charset('utf8mb4');

            $ajustes = [
                'storage_path' => config('facturacion.storage_path', storage_path('app/comprobantes')),
                'crypto_key' => config('facturacion.crypto_key', ''),
                'callback_url' => config('facturacion.callback_url', ''),
                'storage_type' => config('facturacion.storage_type', 'local'),
            ];

            $this->facturador = new FacturadorElectronico($db, $ajustes);
        }

        return $this->facturador;
    }

    public function enviarComprobante(array $comprobanteData, int $idEmpresa, int $tenantId): array
    {
        $empresa = Empresa::where('id_empresa', $idEmpresa)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $consecutivo = $this->generarConsecutivo($idEmpresa, $comprobanteData);
        $fechaEmision = $comprobanteData['FechaEmision']
            ?? now()->setTimezone('America/Costa_Rica')->format('c');

        $lineas = $comprobanteData['Lineas'] ?? [];
        $xmlLineas = [];
        $totalVenta = 0.0;
        $totalGravado = 0.0;
        $totalExento = 0.0;
        $totalServGravados = 0.0;
        $totalServExentos = 0.0;
        $totalMercanciasGravadas = 0.0;
        $totalMercanciasExentas = 0.0;
        $totalDescuentos = 0.0;
        $totalVentaNeta = 0.0;
        $totalImpuesto = 0.0;
        $desgloseImpuestos = [];

        foreach ($lineas as $linea) {
            $cantidad = $this->truncateDecimal((float) ($linea['Cantidad'] ?? 0), 3);
            $precioUnitario = $this->truncateMoney((float) ($linea['PrecioUnitario'] ?? 0));
            $montoTotal = $this->truncateMoney((float) ($linea['MontoTotal'] ?? 0));
            if ($montoTotal <= 0) {
                $montoTotal = $this->truncateMoney($cantidad * $precioUnitario);
            }

            $descuentoMonto = $this->truncateMoney((float) ($linea['Descuento']['MontoDescuento'] ?? 0));
            $subTotal = $this->truncateMoney((float) ($linea['SubTotal'] ?? 0));
            if ($subTotal <= 0) {
                $subTotal = $this->truncateMoney(max(0, $montoTotal - $descuentoMonto));
            }

            $tarifa = $this->truncateDecimal((float) ($linea['Impuesto']['Tarifa'] ?? 0), 2);
            $impMonto = $this->truncateMoney((float) ($linea['Impuesto']['Monto'] ?? 0));
            if ($impMonto <= 0 && $tarifa > 0) {
                $impMonto = $this->truncateMoney($subTotal * ($tarifa / 100));
            }

            $montoTotalLinea = $this->truncateMoney((float) ($linea['MontoTotalLinea'] ?? 0));
            if ($montoTotalLinea <= 0) {
                $montoTotalLinea = $this->truncateMoney($subTotal + $impMonto);
            }

            $totalVenta = $this->truncateMoney($totalVenta + $montoTotal);
            $totalDescuentos = $this->truncateMoney($totalDescuentos + $descuentoMonto);
            $totalVentaNeta = $this->truncateMoney($totalVentaNeta + $subTotal);

            if ($impMonto > 0) {
                $totalGravado = $this->truncateMoney($totalGravado + $montoTotal);
            } else {
                $totalExento = $this->truncateMoney($totalExento + $montoTotal);
            }
            $totalImpuesto = $this->truncateMoney($totalImpuesto + $impMonto);

            $codigoCabys = (string) ($linea['CodigoCABYS'] ?? $linea['Codigo'] ?? '');
            if (str_contains(strtolower((string) ($linea['Detalle'] ?? '')), 'ociann class')) {
                $codigoCabys = '8315100000000';
            }

            $xmlLinea = [
                'NumeroLinea'    => $linea['NumeroLinea'],
                'CodigoCABYS'   => $codigoCabys,
                'Cantidad'       => $cantidad,
                'UnidadMedida'   => $linea['UnidadMedida'] ?? 'Unid',
                'Detalle'        => mb_substr($linea['Detalle'] ?? '', 0, 200),
                'PrecioUnitario' => $precioUnitario,
                'MontoTotal'     => $montoTotal,
            ];

            if ($descuentoMonto > 0) {
                $codigoDescuento = $this->normalizeCodigoDescuento(
                    (string) ($linea['Descuento']['CodigoDescuento'] ?? ''),
                    $descuentoMonto,
                    $montoTotal
                );

                $xmlLinea['Descuento'] = [
                    'MontoDescuento' => $descuentoMonto,
                    'CodigoDescuento' => $codigoDescuento,
                    'NaturalezaDescuento' => $linea['Descuento']['NaturalezaDescuento'] ?? null,
                ];
            }

            // v4.4 expects SubTotal after optional Descuento.
            $xmlLinea['SubTotal'] = $subTotal;

            // v4.4 sequence expects BaseImponible (or IVACobradoFabrica) before Impuesto.
            $xmlLinea['BaseImponible'] = !empty($linea['BaseImponible'])
                ? (float) $linea['BaseImponible']
                : $subTotal;

            if ($impMonto > 0) {
                $impuestoAsumidoEmisor = $this->truncateMoney((float) ($linea['ImpuestoAsumidoEmisorFabrica'] ?? 0));
                $impuestoNeto = $this->truncateMoney(max(0, $impMonto - $impuestoAsumidoEmisor));
                $codigoImpuesto = (string) ($linea['Impuesto']['Codigo'] ?? '01');
                $codigoTarifaIva = (string) ($linea['Impuesto']['CodigoTarifaIVA'] ?? $linea['Impuesto']['CodigoTarifa'] ?? '08');

                $impuesto = [
                    'Codigo'       => $codigoImpuesto,
                    'CodigoTarifaIVA' => $codigoTarifaIva,
                    'Tarifa'       => $tarifa > 0 ? $tarifa : 13,
                    'Monto'        => $impMonto,
                ];
                if (!empty($linea['Impuesto']['FactorIVA'])) {
                    $impuesto['FactorIVA'] = (float) $linea['Impuesto']['FactorIVA'];
                }
                if (!empty($linea['Impuesto']['Exoneracion'])) {
                    $impuesto['Exoneracion'] = $linea['Impuesto']['Exoneracion'];
                }
                $xmlLinea['Impuesto'] = $impuesto;
                $xmlLinea['ImpuestoAsumidoEmisorFabrica'] = $impuestoAsumidoEmisor;
                $xmlLinea['ImpuestoNeto'] = $impuestoNeto;

                $key = $codigoImpuesto . '|' . $codigoTarifaIva;
                $desgloseImpuestos[$key] = [
                    'Codigo' => $codigoImpuesto,
                    'CodigoTarifaIVA' => $codigoTarifaIva,
                    'TotalMontoImpuesto' => $this->truncateMoney(
                        ($desgloseImpuestos[$key]['TotalMontoImpuesto'] ?? 0) + $impuestoNeto
                    ),
                ];
            }

            if (!empty($linea['PartidaArancelaria'])) {
                $xmlLinea['PartidaArancelaria'] = $linea['PartidaArancelaria'];
            }

            $xmlLinea['MontoTotalLinea'] = $montoTotalLinea;
            $xmlLineas[] = $xmlLinea;

            $esServicio = strtolower((string) ($linea['UnidadMedida'] ?? '')) === 'sp';
            if ($impMonto > 0) {
                if ($esServicio) {
                    $totalServGravados = $this->truncateMoney($totalServGravados + $montoTotal);
                } else {
                    $totalMercanciasGravadas = $this->truncateMoney($totalMercanciasGravadas + $montoTotal);
                }
            } else {
                if ($esServicio) {
                    $totalServExentos = $this->truncateMoney($totalServExentos + $montoTotal);
                } else {
                    $totalMercanciasExentas = $this->truncateMoney($totalMercanciasExentas + $montoTotal);
                }
            }
        }

        $totalVentaNeta = $this->truncateMoney(max(0, $totalVenta - $totalDescuentos));

        $totalOtrosCargos = 0;
        $otrosCargosData = [];
        if (!empty($comprobanteData['OtrosCargos'])) {
            foreach ($comprobanteData['OtrosCargos'] as $cargo) {
                $totalOtrosCargos += (float) ($cargo['MontoCargo'] ?? 0);
                $otrosCargosData[] = $cargo;
            }
        }

        $haciendaData = [];

        // v4.4 requires ProveedorSistemas first. CodigoActividadEmisor applies to
        // FE/TE/NC/ND/FEC/FEX but not to REP (TipoDoc 10).
        $proveedor = config('facturacion.proveedor_sistemas', '');
        if ($proveedor) {
            $haciendaData['ProveedorSistemas'] = $proveedor;
        }

        $tipoDoc = (string) ($comprobanteData['TipoDoc'] ?? '01');
        $docsConCodigoActividadEmisor = ['01', '02', '03', '04', '08', '09'];
        if (in_array($tipoDoc, $docsConCodigoActividadEmisor, true)) {
            $codigoActividad = '';

            try {
                $codigoActividad = $this->fetchCodigoActividadByIdentificacion((string) $empresa->Numero) ?? '';
            } catch (Exception $e) {
                Log::warning('No se pudo consultar CodigoActividad en Hacienda: ' . $e->getMessage());
            }

            if ($codigoActividad === '' && !empty($empresa->CodigoActividad)) {
                $codigoActividad = (string) $empresa->CodigoActividad;
            }

            if ($codigoActividad !== '') {
                $codigoActividad = $this->normalizeCodigoActividadEmisor($codigoActividad);
                $haciendaData['CodigoActividadEmisor'] = $codigoActividad;

                if ($empresa->CodigoActividad !== $codigoActividad) {
                    $empresa->CodigoActividad = $codigoActividad;
                    $empresa->save();
                }
            }
        }

        $resumenFactura = [
            'CodigoTipoMoneda' => [
                'CodigoMoneda' => $comprobanteData['CodigoTipoMoneda']['CodigoMoneda'] ?? 'CRC',
                'TipoCambio'   => $comprobanteData['CodigoTipoMoneda']['TipoCambio'] ?? '1',
            ],
            'TotalServGravados'      => $totalServGravados,
            'TotalServExentos'       => $totalServExentos,
            'TotalMercanciasGravadas'=> $totalMercanciasGravadas,
            'TotalMercanciasExentas' => $totalMercanciasExentas,
            'TotalGravado'           => $totalGravado,
            'TotalExento'            => $totalExento,
            'TotalVenta'             => $totalVenta,
            'TotalDescuentos'        => $totalDescuentos,
            'TotalVentaNeta'         => $totalVentaNeta,
        ];

        if (!empty($desgloseImpuestos)) {
            // v4.4: TotalDesgloseImpuesto must be emitted before MedioPago/TotalComprobante.
            $resumenFactura['TotalDesgloseImpuesto'] = array_values($desgloseImpuestos);
        }

        $resumenFactura += [
            'TotalImpuesto'          => $totalImpuesto,
            'TotalOtrosCargos'       => $totalOtrosCargos,
        ];

        $haciendaData += [
            'NumeroConsecutivo' => $consecutivo,
            'FechaEmision'      => $fechaEmision,
            'Emisor'            => $this->buildEmisor($empresa),
            'Receptor'          => $comprobanteData['Receptor'] ?? [],
            'CondicionVenta'    => $comprobanteData['CondicionVenta'] ?? '01',
            'DetalleServicio'   => [
                'LineaDetalle' => $xmlLineas,
            ],
            'ResumenFactura'    => $resumenFactura,
        ];

        $mediosPago = $comprobanteData['MediosPago'] ?? ['01'];
        $montoTotalComprobante = $this->truncateMoney($totalVentaNeta + $totalImpuesto + $totalOtrosCargos);
        $haciendaData['ResumenFactura']['MedioPago'] = $this->buildResumenMediosPago(
            $mediosPago,
            $montoTotalComprobante
        );
        $haciendaData['ResumenFactura']['TotalComprobante'] = $montoTotalComprobante;

        if (!empty($comprobanteData['PlazoCredito'])) {
            $haciendaData['PlazoCredito'] = (int) $comprobanteData['PlazoCredito'];
        }

        if (!empty($otrosCargosData)) {
            $haciendaData['OtrosCargos'] = $otrosCargosData;
        }

        if (!empty($comprobanteData['InformacionReferencia'])) {
            $haciendaData['InformacionReferencia'] = $comprobanteData['InformacionReferencia'];
        }

        try {
            $facturador = $this->getFacturador();
            $facturador->setClientId($empresa->id_cliente);
            $clave = $facturador->enviarComprobante($haciendaData, $idEmpresa);

            if ($clave) {
                $comprobanteData['Emisor'] = $haciendaData['Emisor'];
                $comprobanteData['FechaEmision'] = $fechaEmision;
                $this->guardarEmisionLocal($comprobanteData, $clave, $idEmpresa, $tenantId);

                return [
                    'success' => true,
                    'clave' => $clave,
                    'consecutivo' => $consecutivo,
                    'message' => 'Comprobante enviado a la cola exitosamente.',
                ];
            }

            return [
                'success' => false,
                'message' => 'No se pudo crear el comprobante.',
            ];
        } catch (Exception $e) {
            Log::error('Error al enviar comprobante: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function consultarEstado(string $clave, string $tipo, int $idEmpresa): array
    {
        try {
            $facturador = $this->getFacturador();
            $this->syncClientId($facturador, $idEmpresa);
            return $facturador->consultarEstado($clave, $tipo, $idEmpresa);
        } catch (Exception $e) {
            Log::error("Error al consultar estado de {$clave}: " . $e->getMessage());
            return [
                'clave' => $clave,
                'estado' => 'error',
                'mensaje' => $e->getMessage(),
                'xml' => '',
            ];
        }
    }

    public function cogerXml(string $clave, string $lugar, int $tipo, int $idEmpresa): string|false
    {
        try {
            $facturador = $this->getFacturador();
            $this->syncClientId($facturador, $idEmpresa);
            return $facturador->cogerXml($clave, $lugar, $tipo, $idEmpresa);
        } catch (Exception $e) {
            Log::error("Error al obtener XML de {$clave}: " . $e->getMessage());
            return false;
        }
    }

    public function procesarCola(int $timeout = 0): array
    {
        try {
            $facturador = $this->getFacturador();
            return $facturador->enviarCola($timeout);
        } catch (Exception $e) {
            Log::error('Error al procesar cola: ' . $e->getMessage());
            return [];
        }
    }

    public function procesarCallback(string $body): array
    {
        try {
            $facturador = $this->getFacturador();
            return $facturador->procesarCallbackHacienda($body);
        } catch (Exception $e) {
            Log::error('Error al procesar callback: ' . $e->getMessage());
            throw $e;
        }
    }

    public function recepcionarDocumento(
        string $xmlContent,
        string $clave,
        int $idEmpresa,
        string $tipoRespuesta,
        string $detalleMensaje,
        string $actividadEconomica,
        int $tenantId
    ): array {
        $empresa = Empresa::where('id_empresa', $idEmpresa)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $cedReceptor = $empresa->Numero ?? $empresa->cedula ?? '';
        $tipoIdReceptor = $empresa->Tipo ?? '01';

        $facturador = $this->getFacturador();
        $facturador->setClientId($empresa->id_cliente);

        $consecutivo = $this->generarConsecutivoRecepcion($idEmpresa, $tipoRespuesta);

        $datos = [
            'Clave'                       => $clave,
            'NumeroCedulaReceptor'        => $cedReceptor,
            'NumeroConsecutivoReceptor'   => $consecutivo,
            'FechaEmisionDoc'             => now()->setTimezone('America/Costa_Rica')->format('c'),
            'Mensaje'                     => match ($tipoRespuesta) {
                '05' => 1,
                '06' => 2,
                '07' => 3,
                default => 1,
            },
            'DetalleMensaje'              => $detalleMensaje,
            'MontoTotalImpuesto'          => '0',
            'TotalFactura'                => '0',
        ];

        if ($actividadEconomica) {
            $datos['CodigoActividad'] = $actividadEconomica;
        }

        try {
            $result = $facturador->recepcionar($xmlContent, $datos, $idEmpresa);

            return [
                'success'      => true,
                'consecutivo'  => $consecutivo,
                'message'      => 'Documento recepcionado exitosamente.',
            ];
        } catch (Exception $e) {
            Log::error('Error al recepcionar documento: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function generarConsecutivoRecepcion(int $idEmpresa, string $tipoRespuesta): string
    {
        $empresa = Empresa::find($idEmpresa);
        $sucursal = $empresa->sucursal ?? '001';
        $terminal = '00001';

        $maxConsecutivo = DB::table('fe_recepciones')
            ->where('id_empresa', $idEmpresa)
            ->whereNotNull('respuesta_consecutivo')
            ->where('respuesta_consecutivo', '!=', '')
            ->whereRaw("LENGTH(respuesta_consecutivo) >= 10")
            ->whereRaw("respuesta_consecutivo REGEXP '^[0-9]+$'")
            ->max(DB::raw('CAST(RIGHT(respuesta_consecutivo, 10) AS UNSIGNED)'));

        $nuevoConsecutivo = ($maxConsecutivo ?? 0) + 1;
        $consecutivoStr = str_pad((string) $nuevoConsecutivo, 10, '0', STR_PAD_LEFT);

        return $sucursal . $terminal . $tipoRespuesta . $consecutivoStr;
    }

    public function guardarEmpresa(array $datos, int $idEmpresa = 0): int
    {
        try {
            $facturador = $this->getFacturador();
            return $facturador->guardarEmpresa($datos, $idEmpresa);
        } catch (Exception $e) {
            Log::error('Error al guardar empresa: ' . $e->getMessage());
            throw $e;
        }
    }

    private function syncClientId(FacturadorElectronico $facturador, int $idEmpresa): void
    {
        $empresa = Empresa::find($idEmpresa);
        if ($empresa) {
            $facturador->setClientId($empresa->id_cliente);
        }
    }

    private function generarConsecutivo(int $idEmpresa, array $data): string
    {
        $empresa = Empresa::find($idEmpresa);
        $sucursal = $data['Sucursal'] ?? $empresa->sucursal ?? '001';
        $terminal = $data['Terminal'] ?? '00001';
        $tipoDoc = $data['TipoDoc'] ?? '01';

        $maxConsecutivo = DB::table('fe_emisiones')
            ->where('id_empresa', $idEmpresa)
            ->whereNotNull('NumeroConsecutivo')
            ->where('NumeroConsecutivo', '!=', '')
            ->whereRaw("LENGTH(NumeroConsecutivo) >= 10")
            ->whereRaw("NumeroConsecutivo REGEXP '^[0-9]+$'")
            ->max(DB::raw('CAST(RIGHT(NumeroConsecutivo, 10) AS UNSIGNED)'));

        $nuevoConsecutivo = ($maxConsecutivo ?? 0) + 1;
        $consecutivoStr = str_pad((string) $nuevoConsecutivo, 10, '0', STR_PAD_LEFT);

        return $sucursal . $terminal . $tipoDoc . $consecutivoStr;
    }

    private function truncateDecimal(float $value, int $decimals): float
    {
        $factor = 10 ** $decimals;

        if ($value >= 0) {
            return floor(($value * $factor) + 1e-9) / $factor;
        }

        return ceil(($value * $factor) - 1e-9) / $factor;
    }

    private function truncateMoney(float $value): float
    {
        return $this->truncateDecimal($value, 5);
    }

    private function buildResumenMediosPago(array|string|null $mediosPago, float $total): array
    {
        $entries = is_array($mediosPago) ? $mediosPago : [$mediosPago ?: '01'];
        $codes = [];

        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $code = trim((string) ($entry['TipoMedioPago'] ?? $entry['codigo'] ?? ''));
            } else {
                $code = trim((string) $entry);
            }

            if ($code !== '') {
                $codes[] = $code;
            }
        }

        if ($codes === []) {
            $codes = ['01'];
        }

        $result = [];
        $count = count($codes);
        $baseAmount = $count > 0 ? $this->truncateMoney($total / $count) : $total;
        $assigned = 0.0;

        foreach ($codes as $index => $code) {
            $amount = $index === $count - 1
                ? $this->truncateMoney($total - $assigned)
                : $baseAmount;

            $assigned = $this->truncateMoney($assigned + $amount);

            $result[] = [
                'TipoMedioPago' => $code,
                'TotalMedioPago' => $amount,
            ];
        }

        return $result;
    }

    private function normalizeCodigoActividadEmisor(string $codigo): string
    {
        $digits = preg_replace('/\D+/', '', trim($codigo)) ?? '';

        // v4.4 XSD requires exactly 6 digits for CodigoActividadEmisor.
        // The Hacienda AE API commonly returns values like "6201.0" -> "62010",
        // which should be right-padded to "620100" (not left-padded).
        if ($digits !== '' && strlen($digits) < 6) {
            $digits = str_pad($digits, 6, '0', STR_PAD_RIGHT);
        } elseif (strlen($digits) > 6) {
            $digits = substr($digits, 0, 6);
        }

        return $digits;
    }

    private function fetchCodigoActividadByIdentificacion(string $identificacion): ?string
    {
        $identificacion = preg_replace('/\D+/', '', trim($identificacion)) ?? '';
        if ($identificacion === '') {
            return null;
        }

        $response = Http::timeout(10)->get('https://api.hacienda.go.cr/fe/ae', [
            'identificacion' => $identificacion,
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $actividades = collect($data['actividades'] ?? [])->where('estado', 'A');
        if ($actividades->isEmpty()) {
            return null;
        }

        // Prefer CIIU3 6-digit code when available.
        foreach ($actividades as $actividad) {
            foreach (($actividad['ciiu3'] ?? []) as $ciiu) {
                $ciiuCode = preg_replace('/\D+/', '', (string) ($ciiu['codigo'] ?? '')) ?? '';
                if (strlen($ciiuCode) === 6) {
                    return $ciiuCode;
                }
            }
        }

        $codigo = (string) ($actividades->first()['codigo'] ?? '');
        $codigo = preg_replace('/\D+/', '', $codigo) ?? '';
        return $codigo !== '' ? $codigo : null;
    }

    private function normalizeCodigoDescuento(string $codigo, float $montoDescuento, float $montoTotal): string
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return '02';
        }

        // Codes 01/03 represent regalías/bonificaciones and require 100% discount.
        if (in_array($codigo, ['01', '03'], true) && $this->truncateMoney($montoDescuento) < $this->truncateMoney($montoTotal)) {
            return '02';
        }

        return $codigo;
    }

    private function normalizeProvincia(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $aliases = [
            'sj' => '1',
            'al' => '2',
            'ca' => '3',
            'he' => '4',
            'gu' => '5',
            'pu' => '6',
            'li' => '7',
            'sanjose' => '1',
            'alajuela' => '2',
            'cartago' => '3',
            'heredia' => '4',
            'guanacaste' => '5',
            'puntarenas' => '6',
            'limon' => '7',
        ];

        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }

        $digits = preg_replace('/\D+/', '', $normalized) ?? '';
        if (!preg_match('/^[1-7]$/', $digits)) {
            throw new Exception('La provincia del emisor es inválida. Debe ser un dígito del 1 al 7.');
        }

        return $digits;
    }

    private function normalizeCanton(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $value)) ?? '';
        $digits = str_pad($digits, 2, '0', STR_PAD_LEFT);

        if (!preg_match('/^[0-9]{2}$/', $digits) || $digits === '00') {
            throw new Exception('El cantón del emisor es inválido. Debe tener 2 dígitos (01-99).');
        }

        return $digits;
    }

    private function normalizeDistrito(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $value)) ?? '';
        $digits = str_pad($digits, 2, '0', STR_PAD_LEFT);

        if (!preg_match('/^[0-9]{2}$/', $digits) || $digits === '00') {
            throw new Exception('El distrito del emisor es inválido. Debe tener 2 dígitos (01-99).');
        }

        return $digits;
    }

    private function buildEmisor(Empresa $empresa): array
    {
        $emisor = [
            'Nombre' => (string) ($empresa->Nombre ?? ''),
            'Identificacion' => [
                'Tipo' => (string) ($empresa->Tipo ?? ''),
                'Numero' => (string) ($empresa->Numero ?? ''),
            ],
        ];

        if (!empty($empresa->NombreComercial)) {
            $emisor['NombreComercial'] = (string) $empresa->NombreComercial;
        }

        $emisor['Ubicacion'] = [
            'Provincia' => $this->normalizeProvincia((string) $empresa->Provincia),
            'Canton' => $this->normalizeCanton((string) $empresa->Canton),
            'Distrito' => $this->normalizeDistrito((string) $empresa->Distrito),
            'OtrasSenas' => (string) ($empresa->OtrasSenas ?? ''),
        ];

        if (!empty($empresa->Telefono)) {
            $emisor['Telefono'] = [
                'CodigoPais' => '506',
                'NumTelefono' => (string) $empresa->Telefono,
            ];
        }

        if (!empty($empresa->CorreoElectronico)) {
            $emisor['CorreoElectronico'] = (string) $empresa->CorreoElectronico;
        }

        return $emisor;
    }

    private function guardarEmisionLocal(array $data, string $clave, int $idEmpresa, int $tenantId): void
    {
        // Some provider flows insert the emission row without tenant_id first.
        // Bypass tenant global scope to recover that row and attach it to tenant.
        $emision = Emision::withoutGlobalScopes()
            ->where('clave', $clave)
            ->where('id_empresa', $idEmpresa)
            ->first();

        if (!$emision) {
            $emision = new Emision();
            $emision->clave = $clave;
            $emision->id_empresa = $idEmpresa;
            $emision->estado = Emision::ESTADO_PENDIENTE;
        }

        $emisor = $data['Emisor'] ?? [];
        $receptor = $data['Receptor'] ?? [];
        $lineas = $data['Lineas'] ?? [];

        $totalVenta = 0;
        $totalImpuesto = 0;
        $totalGravado = 0;
        $totalExento = 0;
        $totalDescuentos = 0;
        $totalVentaNeta = 0;

        foreach ($lineas as $linea) {
            $cantidad = (float) ($linea['Cantidad'] ?? 0);
            $precioUnitario = (float) ($linea['PrecioUnitario'] ?? 0);
            $montoTotal = (float) ($linea['MontoTotal'] ?? 0);
            if ($montoTotal <= 0) {
                $montoTotal = $cantidad * $precioUnitario;
            }

            $descuento = (float) ($linea['Descuento']['MontoDescuento'] ?? 0);
            $subTotal = (float) ($linea['SubTotal'] ?? 0);
            if ($subTotal <= 0) {
                $subTotal = max(0, $montoTotal - $descuento);
            }

            $tarifa = (float) ($linea['Impuesto']['Tarifa'] ?? 0);
            $impMonto = (float) ($linea['Impuesto']['Monto'] ?? 0);
            if ($impMonto <= 0 && $tarifa > 0) {
                $impMonto = $subTotal * ($tarifa / 100);
            }

            $montoTotalLinea = (float) ($linea['MontoTotalLinea'] ?? 0);
            if ($montoTotalLinea <= 0) {
                $montoTotalLinea = $subTotal + $impMonto;
            }

            $totalVenta += $montoTotal;
            $totalImpuesto += $impMonto;
            if ($impMonto > 0) {
                $totalGravado += $subTotal;
            } else {
                $totalExento += $subTotal;
            }
            $totalDescuentos += $descuento;
            $totalVentaNeta += $subTotal;
        }

        $mediosPago = $data['MediosPago'] ?? null;
        $medioPago1 = is_array($mediosPago) ? ($mediosPago[0] ?? null) : $mediosPago;
        $medioPago2 = is_array($mediosPago) && count($mediosPago) > 1 ? $mediosPago[1] : null;

        $totalOtros = 0;
        if (!empty($data['OtrosCargos'])) {
            foreach ($data['OtrosCargos'] as $cargo) {
                $totalOtros += (float) ($cargo['MontoCargo'] ?? 0);
            }
        }

        $emision->update([
            'tenant_id'                      => $tenantId,
            'FechaEmision'                   => $data['FechaEmision'] ?? now()->setTimezone('America/Costa_Rica'),
            'CodigoActividad'                => $emisor['CodigoActividad'] ?? $data['CodigoActividad'] ?? null,
            'Emisor_Nombre'                  => $emisor['Nombre'] ?? null,
            'Emisor_TipoIdentificacion'      => $emisor['Identificacion']['Tipo'] ?? null,
            'Emisor_NumeroIdentificacion'    => $emisor['Identificacion']['Numero'] ?? null,
            'Emisor_Provincia'               => $emisor['Ubicacion']['Provincia'] ?? null,
            'Emisor_Canton'                  => $emisor['Ubicacion']['Canton'] ?? null,
            'Emisor_Distrito'                => $emisor['Ubicacion']['Distrito'] ?? null,
            'Emisor_OtrasSenas'              => $emisor['Ubicacion']['OtrasSenas'] ?? null,
            'Emisor_CorreoElectronico'       => $emisor['CorreoElectronico'] ?? null,
            'Receptor_Nombre'                => $receptor['Nombre'] ?? null,
            'Receptor_TipoIdentificacion'    => $receptor['Identificacion']['Tipo'] ?? null,
            'Receptor_NumeroIdentificacion'  => $receptor['Identificacion']['Numero'] ?? null,
            'Receptor_CorreoElectronico'     => $receptor['CorreoElectronico'] ?? null,
            'Receptor_CodigoActividad'       => $receptor['CodigoActividad'] ?? null,
            'Receptor_NombreComercial'       => $receptor['NombreComercial'] ?? null,
            'CondicionVenta'                 => $data['CondicionVenta'] ?? null,
            'MedioPago'                      => $medioPago1,
            'MedioPago2'                     => $medioPago2,
            'PlazoCredito'                   => $data['PlazoCredito'] ?? null,
            'TotalGravado'                   => $totalGravado,
            'TotalExento'                    => $totalExento,
            'TotalVenta'                     => $totalVenta,
            'TotalDescuentos'                => $totalDescuentos,
            'TotalVentaNeta'                 => $totalVentaNeta,
            'TotalImpuesto'                  => $totalImpuesto,
            'TotalOtrosCargos'               => $totalOtros,
            'TotalComprobante'               => $totalVentaNeta + $totalImpuesto + $totalOtros,
            'version_fe'                     => '4.4',
        ]);

        foreach ($lineas as $linea) {
            $cantidad = (float) ($linea['Cantidad'] ?? 0);
            $precioUnitario = (float) ($linea['PrecioUnitario'] ?? 0);
            $montoTotal = (float) ($linea['MontoTotal'] ?? 0);
            if ($montoTotal <= 0) {
                $montoTotal = $cantidad * $precioUnitario;
            }

            $descuento = (float) ($linea['Descuento']['MontoDescuento'] ?? 0);
            $subTotal = (float) ($linea['SubTotal'] ?? 0);
            if ($subTotal <= 0) {
                $subTotal = max(0, $montoTotal - $descuento);
            }

            $tarifa = (float) ($linea['Impuesto']['Tarifa'] ?? 0);
            $impMonto = (float) ($linea['Impuesto']['Monto'] ?? 0);
            if ($impMonto <= 0 && $tarifa > 0) {
                $impMonto = $subTotal * ($tarifa / 100);
            }

            $montoTotalLinea = (float) ($linea['MontoTotalLinea'] ?? 0);
            if ($montoTotalLinea <= 0) {
                $montoTotalLinea = $subTotal + $impMonto;
            }

            EmisionLinea::updateOrCreate(
                [
                    'id_emision'  => $emision->id_emision,
                    'NumeroLinea' => $linea['NumeroLinea'] ?? 0,
                ],
                [
                    'Codigo'                        => $linea['CodigoCABYS'] ?? $linea['Codigo'] ?? '',
                    'Cantidad'                      => $cantidad,
                    'UnidadMedida'                  => $linea['UnidadMedida'] ?? 'Unid',
                    'Detalle'                       => $linea['Detalle'] ?? '',
                    'PrecioUnitario'                => $precioUnitario,
                    'MontoTotal'                    => $montoTotal,
                    'Descuento_MontoDescuento'      => $descuento,
                    'Descuento_NaturalezaDescuento' => $linea['Descuento']['NaturalezaDescuento'] ?? null,
                    'SubTotal'                      => $subTotal,
                    'BaseImponible'                 => $linea['BaseImponible'] ?? null,
                    'MontoTotalLinea'               => $montoTotalLinea,
                    'Impuesto_Codigo'               => $linea['Impuesto']['Codigo'] ?? null,
                    'Impuesto_CodigoTarifa'         => $linea['Impuesto']['CodigoTarifaIVA'] ?? $linea['Impuesto']['CodigoTarifa'] ?? null,
                    'Impuesto_Tarifa'               => $tarifa,
                    'Impuesto_Monto'                => $impMonto,
                    'Impuesto_FactorIVA'            => $linea['Impuesto']['FactorIVA'] ?? null,
                    'Impuesto_Exoneracion_Monto'    => $linea['Impuesto']['Exoneracion']['MontoExoneracion'] ?? null,
                    'Impuesto_Exoneracion_Tipo'     => $linea['Impuesto']['Exoneracion']['TipoDocumento'] ?? null,
                    'Impuesto_Exoneracion_Numero'   => $linea['Impuesto']['Exoneracion']['NumeroDocumento'] ?? null,
                    'PartidaArancelaria'            => $linea['PartidaArancelaria'] ?? null,
                ]
            );
        }
    }

    /**
     * Intenta leer un archivo PKCS#12 con la extensión PHP. Si falla por
     * algoritmo no soportado (RC2, OpenSSL 3.x), recurre al binario openssl
     * con el archivo de configuración legacy del proyecto.
     */
    private function verificarP12(string $p12Bin, string $pin, array &$certs): bool
    {
        $ok = @openssl_pkcs12_read($p12Bin, $certs, $pin);
        if ($ok) {
            return true;
        }

        $opensslErr = '';
        while ($msg = openssl_error_string()) {
            $opensslErr .= $msg;
        }

        $isLegacyIssue = str_contains($opensslErr, 'unsupported')
            || str_contains($opensslErr, '0308010C');

        if (! $isLegacyIssue) {
            return false;
        }

        $legacyCnf = base_path('config/openssl-legacy.cnf');
        if (! file_exists($legacyCnf)) {
            return false;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'p12_');
        file_put_contents($tmpFile, $p12Bin);

        try {
            $escapedPin = escapeshellarg($pin);
            $escapedCnf = escapeshellarg($legacyCnf);
            $escapedTmp = escapeshellarg($tmpFile);

            $cmd = "OPENSSL_CONF={$escapedCnf} openssl pkcs12 -in {$escapedTmp} -passin pass:{$escapedPin} -nokeys -noout 2>&1";
            exec($cmd, $output, $exitCode);

            return $exitCode === 0;
        } finally {
            @unlink($tmpFile);
        }
    }

    /**
     * Verifica las credenciales de Hacienda (usuario/contraseña) intentando
     * obtener un token del IDP. Es la forma oficial de validar acceso al API.
     *
     * @return array{valid: bool, message: string}
     */
    public function verificarCredencialesHacienda(int $idEmpresa, int $tenantId): array
    {
        $empresa = Empresa::where('id_empresa', $idEmpresa)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $ambiente = Ambiente::find($empresa->id_ambiente);
        if (! $ambiente) {
            return ['valid' => false, 'message' => 'Ambiente no configurado.'];
        }

        $usuario = $empresa->getRawOriginal('usuario_mh') ?? $empresa->usuario_mh ?? '';
        $contra = $empresa->getRawOriginal('contra_mh') ?? $empresa->contra_mh ?? '';
        $pin = $empresa->getRawOriginal('pin_llave') ?? $empresa->pin_llave ?? '';
        $p12Content = DB::table('fe_empresas')
            ->where('id_empresa', $idEmpresa)
            ->where('tenant_id', $tenantId)
            ->value('llave_criptografica');
        if ($p12Content === null) {
            $p12Content = $empresa->getRawOriginal('llave_criptografica') ?? '';
        }

        if ($usuario === '' || $contra === '') {
            return ['valid' => false, 'message' => 'Usuario o contraseña MH no configurados.'];
        }

        $cryptoKey = config('facturacion.crypto_key', '');
        if ($cryptoKey !== '') {
            try {
                $key = Key::loadFromAsciiSafeString($cryptoKey);
                if (is_string($usuario) && str_starts_with($usuario, 'def50200')) {
                    $usuario = Crypto::decrypt($usuario, $key);
                }
                if (is_string($contra) && str_starts_with($contra, 'def50200')) {
                    $contra = Crypto::decrypt($contra, $key);
                }
                if (is_string($pin) && str_starts_with($pin, 'def50200')) {
                    $pin = Crypto::decrypt($pin, $key);
                }
                if (is_string($p12Content) && str_starts_with($p12Content, 'def50200')) {
                    $p12Content = Crypto::decrypt($p12Content, $key);
                }
            } catch (Exception $e) {
                Log::warning('Error al desencriptar credenciales para verificación: ' . $e->getMessage());
                return ['valid' => false, 'message' => 'No se pudieron leer las credenciales almacenadas.'];
            }
        }

        $errores = [];
        $p12Ok = false;

        if ($p12Content === '' || $p12Content === null) {
            $errores[] = 'No hay certificado .p12 cargado.';
        } elseif ($pin === '' || $pin === null) {
            $errores[] = 'PIN de la llave no configurado.';
        } else {
            $certificates = [];
            $p12Bin = is_resource($p12Content) ? stream_get_contents($p12Content) : (string) $p12Content;
            $pinClean = trim((string) $pin);
            if (strlen($p12Bin) < 4) {
                $errores[] = 'El certificado .p12 está vacío o corrupto. Suba de nuevo el archivo en Editar empresa.';
            } else {
                $p12Ok = $this->verificarP12($p12Bin, $pinClean, $certificates);
                if (! $p12Ok) {
                    $decoded = @base64_decode($p12Bin, true);
                    if ($decoded !== false && strlen($decoded) >= 4) {
                        $p12Ok = $this->verificarP12($decoded, $pinClean, $certificates);
                    }
                }
                if (! $p12Ok) {
                    $errores[] = 'Certificado .p12 o PIN incorrectos. Use el PIN correcto (ej. si lo obtuvo en apis.gometa.org/p12 use ese) y suba de nuevo el mismo archivo .p12 en Editar empresa para que se guarde bien.';
                }
            }
        }

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->post($ambiente->uri_idp, [
                    'grant_type' => 'password',
                    'client_id' => $ambiente->client_id,
                    'username' => $usuario,
                    'password' => $contra,
                ]);

            if (! $response->successful()) {
                if ($response->status() === 401 || $response->status() === 403) {
                    $body = $response->json();
                    $hint = $body['error_description'] ?? $body['error'] ?? '';
                    $hintStr = $hint ? ' (' . trim($hint) . ')' : '';
                    $errores[] = 'Usuario o contraseña MH incorrectos' . $hintStr . ': use los del portal de Comprobantes Electrónicos y el ambiente correcto (Staging/Producción).';
                } else {
                    $errores[] = 'Hacienda respondió con error ' . $response->status() . '. Intente más tarde.';
                }
            }
        } catch (Exception $e) {
            Log::warning('Error al verificar credenciales Hacienda: ' . $e->getMessage());
            $errores[] = 'No se pudo conectar con el servidor de Hacienda. ' . $e->getMessage();
        }

        if (count($errores) === 0) {
            return ['valid' => true, 'message' => 'Credenciales MH, certificado .p12 y PIN correctos. Conexión con Hacienda correcta.'];
        }

        return [
            'valid' => false,
            'message' => implode(' ', $errores),
        ];
    }
}
