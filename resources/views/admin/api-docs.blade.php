@extends('layouts.app')

@section('title', 'Documentación API — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-book me-2"></i>Documentación API</h2>
    <div>
        <a href="{{ url('/api/openapi.json') }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="fas fa-download me-1"></i>OpenAPI JSON
        </a>
        <a href="{{ url('/api/docs') }}" class="btn btn-outline-primary btn-sm" target="_blank">
            <i class="fas fa-external-link-alt me-1"></i>Abrir Swagger UI
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="fw-bold">Información General</h5>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted fw-semibold" style="width:35%">Base URL</td>
                        <td><code>{{ config('app.url') }}/api/v1</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Versión</td>
                        <td><span class="badge bg-primary">v1.0.0</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Formato</td>
                        <td>JSON (REST)</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Autenticación</td>
                        <td>Bearer Token (Sanctum) / API Key</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5 class="fw-bold">Métodos de Autenticación</h5>
                <div class="mb-3">
                    <h6><span class="badge bg-success me-1">1</span> Bearer Token</h6>
                    <p class="small text-muted mb-1">Obtener vía <code>POST /api/v1/auth/login</code></p>
                    <code class="small d-block bg-light p-2 rounded">Authorization: Bearer &lt;token&gt;</code>
                </div>
                <div>
                    <h6><span class="badge bg-info me-1">2</span> API Key</h6>
                    <p class="small text-muted mb-1">Usar en endpoints <code>/api/v1/key/*</code></p>
                    <code class="small d-block bg-light p-2 rounded">X-API-Key: fecr_...</code>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="fas fa-route me-2"></i>Endpoints disponibles
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px">Método</th>
                        <th>Endpoint</th>
                        <th>Descripción</th>
                        <th style="width:120px">Auth</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-secondary"><td colspan="4" class="fw-bold small">Autenticación</td></tr>
                    <tr>
                        <td><span class="badge bg-success">POST</span></td>
                        <td><code>/api/v1/auth/login</code></td>
                        <td class="small">Iniciar sesión</td>
                        <td><span class="badge bg-light text-dark">Público</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success">POST</span></td>
                        <td><code>/api/v1/auth/logout</code></td>
                        <td class="small">Cerrar sesión</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/auth/me</code></td>
                        <td class="small">Datos del usuario actual</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>

                    <tr class="table-secondary"><td colspan="4" class="fw-bold small">Empresas</td></tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/empresas</code></td>
                        <td class="small">Listar empresas</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success">POST</span></td>
                        <td><code>/api/v1/empresas</code></td>
                        <td class="small">Crear empresa</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/empresas/{id}</code></td>
                        <td class="small">Ver empresa</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-info">PUT</span></td>
                        <td><code>/api/v1/empresas/{id}</code></td>
                        <td class="small">Actualizar empresa</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">DELETE</span></td>
                        <td><code>/api/v1/empresas/{id}</code></td>
                        <td class="small">Eliminar empresa</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>

                    <tr class="table-secondary"><td colspan="4" class="fw-bold small">Comprobantes</td></tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/comprobantes</code></td>
                        <td class="small">Listar comprobantes</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success">POST</span></td>
                        <td><code>/api/v1/comprobantes/emitir</code></td>
                        <td class="small">Emitir comprobante</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/comprobantes/{clave}</code></td>
                        <td class="small">Ver comprobante</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/comprobantes/{clave}/estado</code></td>
                        <td class="small">Estado en Hacienda</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/comprobantes/{clave}/xml</code></td>
                        <td class="small">Descargar XML</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>

                    <tr class="table-secondary"><td colspan="4" class="fw-bold small">Recepciones</td></tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/recepciones</code></td>
                        <td class="small">Listar recepciones</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/recepciones/{clave}</code></td>
                        <td class="small">Ver recepción</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>

                    <tr class="table-secondary"><td colspan="4" class="fw-bold small">Cola de Procesamiento</td></tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/cola</code></td>
                        <td class="small">Estado de la cola</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success">POST</span></td>
                        <td><code>/api/v1/cola/procesar</code></td>
                        <td class="small">Procesar cola</td>
                        <td><span class="badge bg-primary">Bearer</span></td>
                    </tr>

                    <tr class="table-secondary"><td colspan="4" class="fw-bold small">Endpoints con API Key</td></tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/key/empresas</code></td>
                        <td class="small">Listar empresas</td>
                        <td><span class="badge bg-warning text-dark">API Key</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success">POST</span></td>
                        <td><code>/api/v1/key/comprobantes/emitir</code></td>
                        <td class="small">Emitir comprobante</td>
                        <td><span class="badge bg-warning text-dark">API Key</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/key/comprobantes/{clave}</code></td>
                        <td class="small">Ver comprobante</td>
                        <td><span class="badge bg-warning text-dark">API Key</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/key/comprobantes/{clave}/estado</code></td>
                        <td class="small">Estado en Hacienda</td>
                        <td><span class="badge bg-warning text-dark">API Key</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">GET</span></td>
                        <td><code>/api/v1/key/comprobantes/{clave}/xml</code></td>
                        <td class="small">Descargar XML</td>
                        <td><span class="badge bg-warning text-dark">API Key</span></td>
                    </tr>

                    <tr class="table-secondary"><td colspan="4" class="fw-bold small">Webhooks</td></tr>
                    <tr>
                        <td><span class="badge bg-success">POST</span></td>
                        <td><code>/api/v1/webhook/hacienda</code></td>
                        <td class="small">Callback Ministerio de Hacienda</td>
                        <td><span class="badge bg-light text-dark">Público</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
