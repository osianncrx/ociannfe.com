@extends('layouts.app')

@section('title', 'API Keys')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-key me-2"></i>API Keys</h2>
    <a href="{{ route('api-keys.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nueva API Key
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

@if(session('new_api_key') || session('new_api_secret'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Guarde estas credenciales</h5>
    <p class="mb-1">Esta es la única vez que se mostrarán. Cópielas y guárdelas en un lugar seguro.</p>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <strong>API Key:</strong>
            <code class="d-block bg-light p-2 mt-1 user-select-all">{{ session('new_api_key') }}</code>
        </div>
        <div class="col-md-6">
            <strong>API Secret:</strong>
            <code class="d-block bg-light p-2 mt-1 user-select-all">{{ session('new_api_secret') }}</code>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Key</th>
                        <th>Último Uso</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apiKeys ?? [] as $apiKey)
                    <tr>
                        <td>{{ $apiKey->nombre }}</td>
                        <td><code>{{ Str::limit($apiKey->key, 12, '••••••••') }}</code></td>
                        <td>{{ $apiKey->last_used_at ? $apiKey->last_used_at->format('d/m/Y H:i') : 'Nunca' }}</td>
                        <td>
                            @if($apiKey->activa)
                                <span class="badge bg-success">Activa</span>
                            @else
                                <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <form action="{{ route('api-keys.destroy', $apiKey) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta API Key?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No hay API Keys registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
