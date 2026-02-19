@extends('layouts.app')

@section('title', 'Logs de API — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Logs de Uso de API</h2>
    <a href="{{ route('admin.api-keys.index') }}" class="btn btn-outline-primary">
        <i class="fas fa-key me-1"></i>API Keys
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary-custom mb-2"><i class="fas fa-exchange-alt fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ number_format($stats['total_today']) }}</h3>
                <small class="text-muted">Peticiones hoy</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-danger mb-2"><i class="fas fa-exclamation-triangle fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ number_format($stats['errors_today']) }}</h3>
                <small class="text-muted">Errores hoy</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2"><i class="fas fa-tachometer-alt fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['avg_response'] }}ms</h3>
                <small class="text-muted">Promedio resp.</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-info mb-2"><i class="fas fa-network-wired fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ number_format($stats['unique_ips']) }}</h3>
                <small class="text-muted">IPs únicas hoy</small>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted">Endpoint</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="/api/v1/..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">API Key</label>
                <select name="api_key_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach($apiKeys as $key)
                    <option value="{{ $key->id }}" {{ request('api_key_id') == $key->id ? 'selected' : '' }}>{{ $key->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small text-muted">Método</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $m)
                    <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select name="status_code" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="success" {{ request('status_code') === 'success' ? 'selected' : '' }}>Exitosos (&lt;400)</option>
                    <option value="error" {{ request('status_code') === 'error' ? 'selected' : '' }}>Errores (&ge;400)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th>Endpoint</th>
                        <th>Status</th>
                        <th>Tiempo</th>
                        <th>API Key</th>
                        <th>Usuario</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="small text-muted text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>
                            <span class="badge bg-{{ $log->method === 'GET' ? 'primary' : ($log->method === 'POST' ? 'success' : ($log->method === 'DELETE' ? 'danger' : ($log->method === 'PUT' || $log->method === 'PATCH' ? 'info' : 'secondary'))) }}">
                                {{ $log->method }}
                            </span>
                        </td>
                        <td class="small text-truncate" style="max-width:280px" title="{{ $log->endpoint }}">{{ $log->endpoint }}</td>
                        <td>
                            @if($log->status_code && $log->status_code < 400)
                                <span class="badge bg-success">{{ $log->status_code }}</span>
                            @elseif($log->status_code && $log->status_code < 500)
                                <span class="badge bg-warning text-dark">{{ $log->status_code }}</span>
                            @elseif($log->status_code)
                                <span class="badge bg-danger">{{ $log->status_code }}</span>
                            @else
                                <span class="badge bg-secondary">—</span>
                            @endif
                        </td>
                        <td class="small">{{ $log->response_time_ms ? $log->response_time_ms . 'ms' : '—' }}</td>
                        <td class="small text-muted">{{ $log->apiKey?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $log->user?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay logs registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-white">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
