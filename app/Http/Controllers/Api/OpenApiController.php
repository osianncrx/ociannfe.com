<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function json(): JsonResponse
    {
        $spec = $this->buildSpec();
        return response()->json($spec);
    }

    public function docs()
    {
        return view('api.docs');
    }

    private function buildSpec(): array
    {
        $baseUrl = rtrim(config('app.url'), '/');

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Ociann Facturación Electrónica C.R.',
                'description' => $this->getDescription(),
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Ociann',
                    'url' => $baseUrl,
                ],
            ],
            'servers' => [
                ['url' => $baseUrl, 'description' => 'Servidor principal'],
            ],
            'tags' => $this->getTags(),
            'paths' => $this->getPaths(),
            'components' => $this->getComponents(),
        ];
    }

    private function getDescription(): string
    {
        return <<<'MD'
**Ociann Facturación Electrónica C.R.** — API REST para facturación electrónica ante el Ministerio de Hacienda de Costa Rica.

---

## Secciones

### Autenticación
Login con email/password para obtener Bearer Token (Sanctum), o usar API Key vía header `X-API-Key`.

### Empresas
CRUD de empresas registradas con sus credenciales del Ministerio de Hacienda.

### Comprobantes
Emisión de comprobantes electrónicos (facturas, notas de crédito/débito, tiquetes), consulta de estado y descarga de XML.

### Recepciones
Consulta de comprobantes electrónicos recibidos.

### Cola de Procesamiento
Gestión de la cola de envío asincrónico de comprobantes al Ministerio de Hacienda.

### Webhooks
Endpoint público para recibir callbacks del Ministerio de Hacienda.

### API Keys (vía X-API-Key)
Los endpoints bajo `/api/v1/key/*` autentican mediante el header `X-API-Key` en lugar de Bearer Token.

---

## Autenticación

- **Bearer Token**: Obtener vía `POST /api/v1/auth/login` → usar en header `Authorization: Bearer <token>`
- **API Key**: Enviar header `X-API-Key: <tu_api_key>`
MD;
    }

    private function getTags(): array
    {
        return [
            ['name' => 'Autenticación', 'description' => 'Login, logout y datos del usuario autenticado'],
            ['name' => 'Empresas', 'description' => 'CRUD de empresas registradas ante el Ministerio de Hacienda'],
            ['name' => 'Comprobantes', 'description' => 'Emisión y consulta de comprobantes electrónicos'],
            ['name' => 'Recepciones', 'description' => 'Consulta de comprobantes electrónicos recibidos'],
            ['name' => 'Cola', 'description' => 'Cola de procesamiento asincrónico de comprobantes'],
            ['name' => 'Webhooks', 'description' => 'Callbacks del Ministerio de Hacienda'],
            ['name' => 'API Key', 'description' => 'Endpoints autenticados vía X-API-Key'],
        ];
    }

    private function getPaths(): array
    {
        return array_merge(
            $this->getAuthPaths(),
            $this->getEmpresaPaths(),
            $this->getComprobantePaths(),
            $this->getRecepcionPaths(),
            $this->getColaPaths(),
            $this->getWebhookPaths(),
            $this->getApiKeyPaths(),
        );
    }

    // ─── Auth ──────────────────────────────────────────

    private function getAuthPaths(): array
    {
        return [
            '/api/v1/auth/login' => [
                'post' => [
                    'tags' => ['Autenticación'],
                    'summary' => 'Iniciar sesión',
                    'description' => 'Autenticarse con email y password para obtener un Bearer Token (Sanctum).',
                    'operationId' => 'authLogin',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/LoginRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Login exitoso',
                            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/LoginResponse']]],
                        ],
                        '401' => ['description' => 'Credenciales inválidas', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ErrorResponse']]]],
                    ],
                ],
            ],
            '/api/v1/auth/logout' => [
                'post' => [
                    'tags' => ['Autenticación'],
                    'summary' => 'Cerrar sesión',
                    'description' => 'Revocar el token actual.',
                    'operationId' => 'authLogout',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => ['description' => 'Sesión cerrada', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageResponse']]]],
                        '401' => ['description' => 'No autenticado'],
                    ],
                ],
            ],
            '/api/v1/auth/me' => [
                'get' => [
                    'tags' => ['Autenticación'],
                    'summary' => 'Datos del usuario actual',
                    'description' => 'Obtener información del usuario autenticado.',
                    'operationId' => 'authMe',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Datos del usuario',
                            'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/UserResponse']]],
                        ],
                        '401' => ['description' => 'No autenticado'],
                    ],
                ],
            ],
        ];
    }

    // ─── Empresas ──────────────────────────────────────

    private function getEmpresaPaths(): array
    {
        return [
            '/api/v1/empresas' => [
                'get' => [
                    'tags' => ['Empresas'],
                    'summary' => 'Listar empresas',
                    'description' => 'Obtener la lista paginada de empresas del tenant.',
                    'operationId' => 'empresasIndex',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1], 'description' => 'Número de página'],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20], 'description' => 'Resultados por página'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Lista de empresas', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EmpresaPaginatedResponse']]]],
                    ],
                ],
                'post' => [
                    'tags' => ['Empresas'],
                    'summary' => 'Crear empresa',
                    'description' => 'Registrar una nueva empresa con sus credenciales del Ministerio de Hacienda.',
                    'operationId' => 'empresasStore',
                    'security' => [['bearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'multipart/form-data' => [
                                'schema' => ['$ref' => '#/components/schemas/EmpresaCreateRequest'],
                            ],
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/EmpresaCreateRequest'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Empresa creada', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EmpresaResponse']]]],
                        '422' => ['description' => 'Error de validación', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ValidationErrorResponse']]]],
                    ],
                ],
            ],
            '/api/v1/empresas/{id}' => [
                'get' => [
                    'tags' => ['Empresas'],
                    'summary' => 'Ver empresa',
                    'description' => 'Obtener los datos de una empresa específica.',
                    'operationId' => 'empresasShow',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer'], 'description' => 'ID de la empresa'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Datos de la empresa', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EmpresaResponse']]]],
                        '404' => ['description' => 'Empresa no encontrada'],
                    ],
                ],
                'put' => [
                    'tags' => ['Empresas'],
                    'summary' => 'Actualizar empresa',
                    'description' => 'Actualizar datos de una empresa existente.',
                    'operationId' => 'empresasUpdate',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer'], 'description' => 'ID de la empresa'],
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => ['schema' => ['$ref' => '#/components/schemas/EmpresaUpdateRequest']],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Empresa actualizada', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EmpresaResponse']]]],
                        '404' => ['description' => 'Empresa no encontrada'],
                        '422' => ['description' => 'Error de validación'],
                    ],
                ],
                'delete' => [
                    'tags' => ['Empresas'],
                    'summary' => 'Eliminar empresa',
                    'description' => 'Eliminar una empresa del tenant.',
                    'operationId' => 'empresasDestroy',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer'], 'description' => 'ID de la empresa'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Empresa eliminada', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/MessageResponse']]]],
                        '404' => ['description' => 'Empresa no encontrada'],
                    ],
                ],
            ],
        ];
    }

    // ─── Comprobantes ──────────────────────────────────

    private function getComprobantePaths(): array
    {
        return [
            '/api/v1/comprobantes' => [
                'get' => [
                    'tags' => ['Comprobantes'],
                    'summary' => 'Listar comprobantes',
                    'description' => 'Obtener lista paginada de comprobantes electrónicos emitidos.',
                    'operationId' => 'comprobantesIndex',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'estado', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Filtrar por estado'],
                        ['name' => 'empresa_id', 'in' => 'query', 'schema' => ['type' => 'integer'], 'description' => 'Filtrar por empresa'],
                        ['name' => 'fecha_desde', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'Fecha desde (YYYY-MM-DD)'],
                        ['name' => 'fecha_hasta', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'Fecha hasta (YYYY-MM-DD)'],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20], 'description' => 'Resultados por página'],
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1], 'description' => 'Número de página'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Lista de comprobantes', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ComprobantePaginatedResponse']]]],
                    ],
                ],
            ],
            '/api/v1/comprobantes/emitir' => [
                'post' => [
                    'tags' => ['Comprobantes'],
                    'summary' => 'Emitir comprobante',
                    'description' => "Emitir un nuevo comprobante electrónico al Ministerio de Hacienda.\n\nTipos de documento:\n- `01` Factura Electrónica\n- `02` Nota de Débito\n- `03` Nota de Crédito\n- `04` Tiquete Electrónico\n- `08` Factura de Compra\n- `09` Factura de Exportación",
                    'operationId' => 'comprobantesEmitir',
                    'security' => [['bearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => ['schema' => ['$ref' => '#/components/schemas/EmitirComprobanteRequest']],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Comprobante emitido exitosamente', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EmitirComprobanteResponse']]]],
                        '422' => ['description' => 'Error de validación', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ValidationErrorResponse']]]],
                    ],
                ],
            ],
            '/api/v1/comprobantes/{clave}' => [
                'get' => [
                    'tags' => ['Comprobantes'],
                    'summary' => 'Ver comprobante',
                    'description' => 'Obtener detalles de un comprobante por su clave numérica de 50 dígitos.',
                    'operationId' => 'comprobantesShow',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'clave', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Clave numérica del comprobante (50 dígitos)'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Datos del comprobante', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ComprobanteResponse']]]],
                        '404' => ['description' => 'Comprobante no encontrado'],
                    ],
                ],
            ],
            '/api/v1/comprobantes/{clave}/estado' => [
                'get' => [
                    'tags' => ['Comprobantes'],
                    'summary' => 'Consultar estado en Hacienda',
                    'description' => 'Consultar el estado actual de un comprobante directamente al Ministerio de Hacienda.',
                    'operationId' => 'comprobantesEstado',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'clave', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Clave numérica del comprobante'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Estado del comprobante en Hacienda', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EstadoComprobanteResponse']]]],
                        '404' => ['description' => 'Comprobante no encontrado'],
                    ],
                ],
            ],
            '/api/v1/comprobantes/{clave}/xml' => [
                'get' => [
                    'tags' => ['Comprobantes'],
                    'summary' => 'Descargar XML',
                    'description' => 'Descargar el XML firmado del comprobante electrónico.',
                    'operationId' => 'comprobantesXml',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'clave', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Clave numérica del comprobante'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'XML del comprobante', 'content' => ['application/xml' => ['schema' => ['type' => 'string']]]],
                        '404' => ['description' => 'XML no disponible'],
                    ],
                ],
            ],
        ];
    }

    // ─── Recepciones ───────────────────────────────────

    private function getRecepcionPaths(): array
    {
        return [
            '/api/v1/recepciones' => [
                'get' => [
                    'tags' => ['Recepciones'],
                    'summary' => 'Listar recepciones',
                    'description' => 'Obtener lista paginada de comprobantes electrónicos recibidos.',
                    'operationId' => 'recepcionesIndex',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'estado', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Filtrar por estado'],
                        ['name' => 'empresa_id', 'in' => 'query', 'schema' => ['type' => 'integer'], 'description' => 'Filtrar por empresa'],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20], 'description' => 'Resultados por página'],
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1], 'description' => 'Número de página'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Lista de recepciones'],
                    ],
                ],
            ],
            '/api/v1/recepciones/{clave}' => [
                'get' => [
                    'tags' => ['Recepciones'],
                    'summary' => 'Ver recepción',
                    'description' => 'Obtener detalles de una recepción por su clave.',
                    'operationId' => 'recepcionesShow',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'clave', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Clave de la recepción'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Datos de la recepción'],
                        '404' => ['description' => 'Recepción no encontrada'],
                    ],
                ],
            ],
        ];
    }

    // ─── Cola ──────────────────────────────────────────

    private function getColaPaths(): array
    {
        return [
            '/api/v1/cola' => [
                'get' => [
                    'tags' => ['Cola'],
                    'summary' => 'Estado de la cola',
                    'description' => 'Obtener el estado actual de la cola de procesamiento.',
                    'operationId' => 'colaIndex',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => ['description' => 'Estado de la cola', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ColaStatusResponse']]]],
                    ],
                ],
            ],
            '/api/v1/cola/procesar' => [
                'post' => [
                    'tags' => ['Cola'],
                    'summary' => 'Procesar cola',
                    'description' => 'Forzar el procesamiento de la cola de envío de comprobantes pendientes.',
                    'operationId' => 'colaProcesar',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => ['description' => 'Cola procesada', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ColaProcesarResponse']]]],
                    ],
                ],
            ],
        ];
    }

    // ─── Webhooks ──────────────────────────────────────

    private function getWebhookPaths(): array
    {
        return [
            '/api/v1/webhook/hacienda' => [
                'post' => [
                    'tags' => ['Webhooks'],
                    'summary' => 'Callback Hacienda',
                    'description' => 'Endpoint público para recibir notificaciones del Ministerio de Hacienda sobre el estado de comprobantes.',
                    'operationId' => 'webhookHacienda',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/xml' => ['schema' => ['type' => 'string']],
                            'application/json' => ['schema' => ['type' => 'object']],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Procesado correctamente'],
                        '500' => ['description' => 'Error de procesamiento'],
                    ],
                ],
            ],
        ];
    }

    // ─── API Key endpoints ─────────────────────────────

    private function getApiKeyPaths(): array
    {
        return [
            '/api/v1/key/empresas' => [
                'get' => [
                    'tags' => ['API Key'],
                    'summary' => 'Listar empresas (API Key)',
                    'description' => 'Listar empresas del tenant usando autenticación por API Key.',
                    'operationId' => 'keyEmpresasIndex',
                    'security' => [['apiKeyAuth' => []]],
                    'responses' => [
                        '200' => ['description' => 'Lista de empresas', 'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/EmpresaPaginatedResponse']]]],
                        '401' => ['description' => 'API Key inválida'],
                    ],
                ],
            ],
            '/api/v1/key/comprobantes/emitir' => [
                'post' => [
                    'tags' => ['API Key'],
                    'summary' => 'Emitir comprobante (API Key)',
                    'description' => 'Emitir comprobante electrónico usando autenticación por API Key.',
                    'operationId' => 'keyComprobantesEmitir',
                    'security' => [['apiKeyAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => ['schema' => ['$ref' => '#/components/schemas/EmitirComprobanteRequest']],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Comprobante emitido'],
                        '401' => ['description' => 'API Key inválida'],
                        '422' => ['description' => 'Error de validación'],
                    ],
                ],
            ],
            '/api/v1/key/comprobantes/{clave}' => [
                'get' => [
                    'tags' => ['API Key'],
                    'summary' => 'Ver comprobante (API Key)',
                    'operationId' => 'keyComprobantesShow',
                    'security' => [['apiKeyAuth' => []]],
                    'parameters' => [
                        ['name' => 'clave', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Clave del comprobante'],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Datos del comprobante'],
                        '401' => ['description' => 'API Key inválida'],
                        '404' => ['description' => 'No encontrado'],
                    ],
                ],
            ],
            '/api/v1/key/comprobantes/{clave}/estado' => [
                'get' => [
                    'tags' => ['API Key'],
                    'summary' => 'Estado en Hacienda (API Key)',
                    'operationId' => 'keyComprobantesEstado',
                    'security' => [['apiKeyAuth' => []]],
                    'parameters' => [
                        ['name' => 'clave', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Estado del comprobante'],
                        '401' => ['description' => 'API Key inválida'],
                    ],
                ],
            ],
            '/api/v1/key/comprobantes/{clave}/xml' => [
                'get' => [
                    'tags' => ['API Key'],
                    'summary' => 'Descargar XML (API Key)',
                    'operationId' => 'keyComprobantesXml',
                    'security' => [['apiKeyAuth' => []]],
                    'parameters' => [
                        ['name' => 'clave', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'XML del comprobante', 'content' => ['application/xml' => ['schema' => ['type' => 'string']]]],
                        '404' => ['description' => 'No disponible'],
                    ],
                ],
            ],
        ];
    }

    // ─── Components ────────────────────────────────────

    private function getComponents(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Sanctum Token',
                    'description' => 'Obtener token vía POST /api/v1/auth/login',
                ],
                'apiKeyAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key',
                    'description' => 'API Key generada desde el panel (formato: fecr_...)',
                ],
            ],
            'schemas' => $this->getSchemas(),
        ];
    }

    private function getSchemas(): array
    {
        return [
            'LoginRequest' => [
                'type' => 'object',
                'required' => ['email', 'password'],
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email', 'description' => 'Email del usuario'],
                    'password' => ['type' => 'string', 'description' => 'Contraseña'],
                ],
            ],
            'LoginResponse' => [
                'type' => 'object',
                'properties' => [
                    'token' => ['type' => 'string', 'description' => 'Bearer token para autenticación'],
                    'user' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'email' => ['type' => 'string'],
                            'tenant_id' => ['type' => 'integer', 'nullable' => true],
                        ],
                    ],
                ],
            ],
            'UserResponse' => [
                'type' => 'object',
                'properties' => [
                    'user' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'email' => ['type' => 'string'],
                            'tenant_id' => ['type' => 'integer', 'nullable' => true],
                            'tenant' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                ],
            ],
            'MessageResponse' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                ],
            ],
            'ErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'error' => ['type' => 'string'],
                ],
            ],
            'ValidationErrorResponse' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                ],
            ],
            'EmpresaResponse' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'cedula' => ['type' => 'string'],
                    'sucursal' => ['type' => 'string', 'nullable' => true],
                    'nombre' => ['type' => 'string'],
                    'nombre_comercial' => ['type' => 'string', 'nullable' => true],
                    'tipo_identificacion' => ['type' => 'string'],
                    'numero_identificacion' => ['type' => 'string'],
                    'ambiente' => ['type' => 'string', 'enum' => ['Staging', 'Producción']],
                    'ambiente_id' => ['type' => 'integer', 'enum' => [1, 2]],
                    'ubicacion' => [
                        'type' => 'object',
                        'properties' => [
                            'provincia' => ['type' => 'string', 'nullable' => true],
                            'canton' => ['type' => 'string', 'nullable' => true],
                            'distrito' => ['type' => 'string', 'nullable' => true],
                            'otras_senas' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'correo' => ['type' => 'string', 'nullable' => true],
                    'telefono' => [
                        'type' => 'object',
                        'properties' => [
                            'codigo_pais' => ['type' => 'string', 'nullable' => true],
                            'numero' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                    'codigo_actividad' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'EmpresaPaginatedResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/EmpresaResponse']],
                    'links' => ['type' => 'object'],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
            'EmpresaCreateRequest' => [
                'type' => 'object',
                'required' => ['cedula', 'Nombre', 'Tipo', 'Numero', 'id_ambiente', 'usuario_mh', 'contra_mh', 'pin_llave', 'llave_criptografica'],
                'properties' => [
                    'cedula' => ['type' => 'string', 'maxLength' => 12, 'description' => 'Cédula jurídica o física'],
                    'Nombre' => ['type' => 'string', 'maxLength' => 255, 'description' => 'Nombre de la empresa'],
                    'Tipo' => ['type' => 'string', 'maxLength' => 2, 'description' => 'Tipo de identificación (01=Física, 02=Jurídica, 03=DIMEX, 04=NITE)'],
                    'Numero' => ['type' => 'string', 'maxLength' => 12, 'description' => 'Número de identificación'],
                    'id_ambiente' => ['type' => 'integer', 'enum' => [1, 2], 'description' => '1=Staging, 2=Producción'],
                    'usuario_mh' => ['type' => 'string', 'description' => 'Usuario del Ministerio de Hacienda (ATV)'],
                    'contra_mh' => ['type' => 'string', 'description' => 'Contraseña del Ministerio de Hacienda (ATV)'],
                    'pin_llave' => ['type' => 'string', 'description' => 'PIN de la llave criptográfica (.p12)'],
                    'llave_criptografica' => ['type' => 'string', 'format' => 'binary', 'description' => 'Archivo .p12 (o base64 del archivo)'],
                    'sucursal' => ['type' => 'string', 'description' => 'Código de sucursal (3 dígitos). Se auto-asigna si se omite.'],
                    'NombreComercial' => ['type' => 'string', 'nullable' => true],
                    'CorreoElectronico' => ['type' => 'string', 'format' => 'email', 'nullable' => true],
                    'CodigoActividad' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'EmpresaUpdateRequest' => [
                'type' => 'object',
                'properties' => [
                    'Nombre' => ['type' => 'string', 'nullable' => true],
                    'NombreComercial' => ['type' => 'string', 'nullable' => true],
                    'CorreoElectronico' => ['type' => 'string', 'format' => 'email', 'nullable' => true],
                    'Provincia' => ['type' => 'string', 'nullable' => true],
                    'Canton' => ['type' => 'string', 'nullable' => true],
                    'Distrito' => ['type' => 'string', 'nullable' => true],
                    'OtrasSenas' => ['type' => 'string', 'nullable' => true],
                    'CodigoActividad' => ['type' => 'string', 'nullable' => true],
                    'usuario_mh' => ['type' => 'string', 'nullable' => true, 'description' => 'Nuevo usuario MH'],
                    'contra_mh' => ['type' => 'string', 'nullable' => true, 'description' => 'Nueva contraseña MH'],
                    'pin_llave' => ['type' => 'string', 'nullable' => true, 'description' => 'Nuevo PIN de llave'],
                ],
            ],
            'ComprobanteResponse' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'clave' => ['type' => 'string', 'description' => 'Clave numérica de 50 dígitos'],
                    'consecutivo' => ['type' => 'string'],
                    'fecha_emision' => ['type' => 'string', 'format' => 'date-time'],
                    'estado' => ['type' => 'string'],
                    'estado_codigo' => ['type' => 'integer'],
                    'emisor' => [
                        'type' => 'object',
                        'properties' => [
                            'nombre' => ['type' => 'string'],
                            'identificacion' => ['type' => 'string'],
                        ],
                    ],
                    'receptor' => [
                        'type' => 'object',
                        'properties' => [
                            'nombre' => ['type' => 'string'],
                            'identificacion' => ['type' => 'string'],
                        ],
                    ],
                    'totales' => [
                        'type' => 'object',
                        'properties' => [
                            'gravado' => ['type' => 'number'],
                            'exento' => ['type' => 'number'],
                            'venta' => ['type' => 'number'],
                            'descuentos' => ['type' => 'number'],
                            'venta_neta' => ['type' => 'number'],
                            'impuesto' => ['type' => 'number'],
                            'comprobante' => ['type' => 'number'],
                        ],
                    ],
                    'mensaje_hacienda' => ['type' => 'string', 'nullable' => true],
                    'empresa_id' => ['type' => 'integer'],
                ],
            ],
            'ComprobantePaginatedResponse' => [
                'type' => 'object',
                'properties' => [
                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ComprobanteResponse']],
                    'links' => ['type' => 'object'],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
            'EmitirComprobanteRequest' => [
                'type' => 'object',
                'required' => ['id_empresa', 'Receptor', 'CondicionVenta', 'Lineas'],
                'properties' => [
                    'id_empresa' => ['type' => 'integer', 'description' => 'ID de la empresa emisora'],
                    'TipoDoc' => ['type' => 'string', 'enum' => ['01', '02', '03', '04', '08', '09'], 'description' => 'Tipo de documento', 'default' => '01'],
                    'Receptor' => [
                        'type' => 'object',
                        'required' => ['Nombre'],
                        'properties' => [
                            'Nombre' => ['type' => 'string', 'description' => 'Nombre del receptor'],
                            'Identificacion' => [
                                'type' => 'object',
                                'properties' => [
                                    'Tipo' => ['type' => 'string', 'enum' => ['01', '02', '03', '04'], 'description' => '01=Física, 02=Jurídica, 03=DIMEX, 04=NITE'],
                                    'Numero' => ['type' => 'string', 'maxLength' => 12],
                                ],
                            ],
                            'CorreoElectronico' => ['type' => 'string', 'format' => 'email'],
                        ],
                    ],
                    'CondicionVenta' => ['type' => 'string', 'enum' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '99'], 'description' => '01=Contado, 02=Crédito, etc.'],
                    'MediosPago' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['01', '02', '03', '04', '05', '99']], 'description' => '01=Efectivo, 02=Tarjeta, 03=Cheque, etc.'],
                    'Lineas' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'items' => [
                            'type' => 'object',
                            'required' => ['NumeroLinea', 'Codigo', 'Cantidad', 'UnidadMedida', 'Detalle', 'PrecioUnitario'],
                            'properties' => [
                                'NumeroLinea' => ['type' => 'integer', 'minimum' => 1],
                                'Codigo' => ['type' => 'string'],
                                'Cantidad' => ['type' => 'number', 'minimum' => 0.01],
                                'UnidadMedida' => ['type' => 'string', 'description' => 'Ej: Unid, Sp, m, kg, etc.'],
                                'Detalle' => ['type' => 'string', 'maxLength' => 255],
                                'PrecioUnitario' => ['type' => 'number', 'minimum' => 0],
                                'Impuesto' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'Codigo' => ['type' => 'string', 'description' => 'Código de impuesto (01=IVA, etc.)'],
                                        'Tarifa' => ['type' => 'number', 'minimum' => 0, 'maximum' => 100, 'description' => 'Porcentaje de impuesto'],
                                    ],
                                ],
                                'Descuento' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'MontoDescuento' => ['type' => 'number', 'minimum' => 0],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'FechaEmision' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'Sucursal' => ['type' => 'string', 'nullable' => true, 'description' => 'Código de sucursal (3 dígitos)'],
                    'Terminal' => ['type' => 'string', 'nullable' => true, 'description' => 'Código de terminal (5 dígitos)'],
                    'CodigoTipoMoneda' => [
                        'type' => 'object',
                        'nullable' => true,
                        'properties' => [
                            'CodigoMoneda' => ['type' => 'string', 'description' => 'Código ISO 4217 (CRC, USD, EUR)'],
                            'TipoCambio' => ['type' => 'number', 'description' => 'Tipo de cambio respecto al colón'],
                        ],
                    ],
                ],
            ],
            'EmitirComprobanteResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'clave' => ['type' => 'string', 'description' => 'Clave numérica asignada'],
                    'consecutivo' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'estado' => ['type' => 'string'],
                ],
            ],
            'EstadoComprobanteResponse' => [
                'type' => 'object',
                'properties' => [
                    'clave' => ['type' => 'string'],
                    'estado' => ['type' => 'string'],
                    'mensaje' => ['type' => 'string', 'nullable' => true],
                    'detalle' => ['type' => 'string', 'nullable' => true],
                ],
            ],
            'ColaStatusResponse' => [
                'type' => 'object',
                'properties' => [
                    'pendientes' => ['type' => 'integer'],
                    'en_proceso' => ['type' => 'integer'],
                    'completados_hoy' => ['type' => 'integer'],
                    'errores_hoy' => ['type' => 'integer'],
                ],
            ],
            'ColaProcesarResponse' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'enviados' => ['type' => 'integer'],
                    'detalles' => ['type' => 'array', 'items' => ['type' => 'object']],
                ],
            ],
        ];
    }
}
