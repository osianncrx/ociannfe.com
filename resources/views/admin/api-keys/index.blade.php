@extends('layouts.app')

@section('title', 'API Keys — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-key me-2"></i>API Keys</h2>
    <div>
        <a href="{{ route('admin.api-docs') }}" class="btn btn-outline-primary me-2">
            <i class="fas fa-book me-1"></i>API Docs
        </a>
        <a href="{{ route('admin.api-keys.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Nueva API Key
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    @if(session('new_key'))
    <hr>
    <div class="mb-2">
        <strong>API Key:</strong>
        <code class="user-select-all bg-light px-2 py-1 rounded">{{ session('new_key') }}</code>
    </div>
    <div>
        <strong>Secret:</strong>
        <code class="user-select-all bg-light px-2 py-1 rounded">{{ session('new_secret') }}</code>
    </div>
    <small class="text-danger fw-bold d-block mt-2">
        <i class="fas fa-exclamation-triangle me-1"></i>Guarda estas credenciales — no se mostrarán de nuevo.
    </small>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted">Buscar</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nombre o key..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Tenant</label>
                <select name="tenant_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.api-keys.index') }}" class="btn btn-sm btn-outline-secondary w-100">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Key</th>
                        <th>Tenant</th>
                        <th>Estado</th>
                        <th>Último uso</th>
                        <th>Expira</th>
                        <th>Creada</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apiKeys as $apiKey)
                    <tr>
                        <td class="fw-semibold">{{ $apiKey->name }}</td>
                        <td><code class="small">{{ Str::limit($apiKey->key, 20) }}</code></td>
                        <td>{{ $apiKey->tenant?->name ?? '—' }}</td>
                        <td>
                            @if($apiKey->is_active)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-danger">Inactiva</span>
                            @endif
                            @if($apiKey->expires_at && $apiKey->expires_at->isPast())
                                <span class="badge bg-warning text-dark">Expirada</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $apiKey->last_used_at?->diffForHumans() ?? 'Nunca' }}</td>
                        <td class="small text-muted">{{ $apiKey->expires_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                        <td class="small text-muted">{{ $apiKey->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.api-keys.show', $apiKey->id) }}" class="btn btn-outline-primary" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.api-keys.toggle', $apiKey->id) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-outline-{{ $apiKey->is_active ? 'warning' : 'success' }}" title="{{ $apiKey->is_active ? 'Desactivar' : 'Activar' }}">
                                        <i class="fas fa-{{ $apiKey->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.api-keys.destroy', $apiKey->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta API Key?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay API Keys registradas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($apiKeys->hasPages())
    <div class="card-footer bg-white">
        {{ $apiKeys->links() }}
    </div>
    @endif
</div>
@endsection
