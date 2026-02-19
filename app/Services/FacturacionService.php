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
        $comprobanteData['NumeroConsecutivo'] = $consecutivo;

        if (empty($comprobanteData['FechaEmision'])) {
            $comprobanteData['FechaEmision'] = now()->setTimezone('America/Costa_Rica')->format('c');
        }

        $comprobanteData['Emisor'] = $this->buildEmisor($empresa);

        try {
            $facturador = $this->getFacturador();
            $clave = $facturador->enviarComprobante($comprobanteData, $idEmpresa);

            if ($clave) {
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

    private function buildEmisor(Empresa $empresa): array
    {
        $emisor = [
            'Nombre' => (string) ($empresa->Nombre ?? ''),
            'Identificacion' => [
                'Tipo' => (string) ($empresa->Tipo ?? ''),
                'Numero' => (string) ($empresa->Numero ?? ''),
            ],
            'Ubicacion' => [
                'Provincia' => (string) ($empresa->Provincia ?? '0'),
                'Canton' => (string) ($empresa->Canton ?? '00'),
                'Distrito' => (string) ($empresa->Distrito ?? '00'),
                'OtrasSenas' => (string) ($empresa->OtrasSenas ?? ''),
            ],
        ];

        if (!empty($empresa->CorreoElectronico)) {
            $emisor['CorreoElectronico'] = (string) $empresa->CorreoElectronico;
        }

        return $emisor;
    }

    private function guardarEmisionLocal(array $data, string $clave, int $idEmpresa, int $tenantId): void
    {
        $emision = Emision::where('clave', $clave)->first();

        if ($emision) {
            $emision->update(['tenant_id' => $tenantId]);
        }

        if (isset($data['DetalleServicio']['LineaDetalle'])) {
            foreach ($data['DetalleServicio']['LineaDetalle'] as $linea) {
                if ($emision) {
                    EmisionLinea::updateOrCreate(
                        [
                            'id_emision' => $emision->id_emision,
                            'NumeroLinea' => $linea['NumeroLinea'] ?? 0,
                        ],
                        [
                            'Codigo' => $linea['CodigoCABYS'] ?? $linea['Codigo'] ?? '',
                            'Cantidad' => $linea['Cantidad'] ?? 0,
                            'UnidadMedida' => $linea['UnidadMedida'] ?? 'Unid',
                            'Detalle' => $linea['Detalle'] ?? '',
                            'PrecioUnitario' => $linea['PrecioUnitario'] ?? 0,
                            'MontoTotal' => $linea['MontoTotal'] ?? 0,
                            'SubTotal' => $linea['SubTotal'] ?? 0,
                            'MontoTotalLinea' => $linea['MontoTotalLinea'] ?? 0,
                            'Impuesto_Codigo' => $linea['Impuesto']['Codigo'] ?? null,
                            'Impuesto_CodigoTarifa' => $linea['Impuesto']['CodigoTarifaIVA'] ?? $linea['Impuesto']['CodigoTarifa'] ?? null,
                            'Impuesto_Tarifa' => $linea['Impuesto']['Tarifa'] ?? 0,
                            'Impuesto_Monto' => $linea['Impuesto']['Monto'] ?? 0,
                        ]
                    );
                }
            }
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
