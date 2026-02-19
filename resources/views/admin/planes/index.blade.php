@extends('layouts.app')

@section('title', 'Gestión de Planes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-list me-2"></i>Gestión de Planes</h2>
    <a href="{{ url('/admin/planes/create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Plan
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th class="text-end">Precio</th>
                        <th class="text-center">Empresas Máx</th>
                        <th class="text-center">Comprobantes Máx</th>
                        <th class="text-center">API Keys Máx</th>
                        <th class="text-center">Suscripciones</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($planes as $plan)
                    <tr>
                        <td class="fw-semibold">{{ $plan->name }}</td>
                        <td class="text-end">{{ number_format($plan->price, 2) }} {{ $plan->currency ?? 'CRC' }}</td>
                        <td class="text-center">{{ $plan->max_empresas == -1 ? 'Ilimitado' : $plan->max_empresas }}</td>
                        <td class="text-center">{{ $plan->max_comprobantes == -1 ? 'Ilimitado' : number_format($plan->max_comprobantes) }}</td>
                        <td class="text-center">{{ $plan->max_api_keys == -1 ? 'Ilimitado' : $plan->max_api_keys }}</td>
                        <td class="text-center">{{ $plan->subscriptions_count ?? $plan->subscriptions->count() }}</td>
                        <td class="text-center">
                            @if($plan->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ url('/admin/planes/' . $plan->id . '/edit') }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ url('/admin/planes/' . $plan->id) }}" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este plan?')">
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
                        <td colspan="8" class="text-center text-muted py-4">No hay planes registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
