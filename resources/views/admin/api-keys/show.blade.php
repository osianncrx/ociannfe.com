@extends('layouts.app')

@section('title', 'API Key: ' . $apiKey->name . ' — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('admin.api-keys.index') }}" class="text-muted text-decoration-none small">
            <i class="fas fa-arrow-left me-1"></i>Volver a API Keys
        </a>
        <h2 class="mb-0 mt-1"><i class="fas fa-key me-2"></i>{{ $apiKey->name }}</h2>
    </div>
    <div class="btn-group">
        <form method="POST" action="{{ route('admin.api-keys.toggle', $apiKey->id) }}" class="d-inline">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-{{ $apiKey->is_active ? 'warning' : 'success' }}">
                <i class="fas fa-{{ $apiKey->is_active ? 'pause' : 'play' }} me-1"></i>
                {{ $apiKey->is_active ? 'Desactivar' : 'Activar' }}
            </button>
        </form>
        <form method="POST" action="{{ route('admin.api-keys.destroy', $apiKey->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta API Key?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash me-1"></i>Eliminar
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary-custom mb-2"><i class="fas fa-chart-line fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ number_format($usageStats['total_requests']) }}</h3>
                <small class="text-muted">Total peticiones</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-info mb-2"><i class="fas fa-calendar-day fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ number_format($usageStats['today_requests']) }}</h3>
                <small class="text-muted">Hoy</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2"><i class="fas fa-tachometer-alt fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $usageStats['avg_response_time'] }}ms</h3>
                <small class="text-muted">Tiempo promedio</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-danger mb-2"><i class="fas fa-exclamation-circle fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ number_format($usageStats['error_count']) }}</h3>
                <small class="text-muted">Errores</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="fas fa-info-circle me-2"></i>Detalles
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted fw-semibold" style="width:40%">API Key</td>
                        <td><code class="small user-select-all">{{ $apiKey->key }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Estado</td>
                        <td>
                            @if($apiKey->is_active)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-danger">Inactiva</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Tenant</td>
                        <td>{{ $apiKey->tenant?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Creador</td>
                        <td>{{ $apiKey->user?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Último uso</td>
                        <td>{{ $apiKey->last_used_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Expira</td>
                        <td>{{ $apiKey->expires_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Creada</td>
                        <td>{{ $apiKey->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($apiKey->permissions)
                    <tr>
                        <td class="text-muted fw-semibold">Permisos</td>
                        <td>
                            @foreach($apiKey->permissions as $perm)
                            <span class="badge bg-light text-dark me-1">{{ $perm }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history me-2"></i>Últimas 50 peticiones</span>
                <a href="{{ route('admin.api-logs.index', ['api_key_id' => $apiKey->id]) }}" class="btn btn-sm btn-outline-primary">
                    Ver todos los logs
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Método</th>
                                <th>Endpoint</th>
                                <th>Status</th>
                                <th>Tiempo</th>
                                <th>IP</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($usageLogs as $log)
                            <tr>
                                <td><span class="badge bg-{{ $log->method === 'GET' ? 'primary' : ($log->method === 'POST' ? 'success' : ($log->method === 'DELETE' ? 'danger' : 'secondary')) }}">{{ $log->method }}</span></td>
                                <td class="small text-truncate" style="max-width:250px" title="{{ $log->endpoint }}">{{ $log->endpoint }}</td>
                                <td>
                                    @if($log->status_code < 400)
                                        <span class="badge bg-success">{{ $log->status_code }}</span>
                                    @elseif($log->status_code < 500)
                                        <span class="badge bg-warning text-dark">{{ $log->status_code }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $log->status_code }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $log->response_time_ms }}ms</td>
                                <td class="small text-muted">{{ $log->ip_address }}</td>
                                <td class="small text-muted">{{ $log->created_at->format('d/m H:i:s') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Sin actividad registrada</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
