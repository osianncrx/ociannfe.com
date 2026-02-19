@extends('layouts.app')

@section('title', 'Gestión de Tenants')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-database me-2"></i>Gestión de Tenants</h2>
    <a href="{{ url('/admin/tenants/create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Tenant
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ url('/admin/tenants') }}" class="row g-3 mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2">Buscar</button>
                <a href="{{ url('/admin/tenants') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th class="text-center">Usuarios</th>
                        <th class="text-center">Empresas</th>
                        <th class="text-center">Emisiones</th>
                        <th>Plan</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    <tr>
                        <td>{{ $tenant->id }}</td>
                        <td class="fw-semibold">{{ $tenant->name }}</td>
                        <td>{{ $tenant->email }}</td>
                        <td class="text-center">{{ $tenant->users_count ?? $tenant->users->count() }}</td>
                        <td class="text-center">{{ $tenant->empresas_count ?? $tenant->empresas->count() }}</td>
                        <td class="text-center">{{ $tenant->emisiones_count ?? $tenant->emisiones->count() }}</td>
                        <td>
                            @if($tenant->activeSubscription && $tenant->activeSubscription->plan)
                                <span class="badge bg-info text-dark">{{ $tenant->activeSubscription->plan->name }}</span>
                            @else
                                <span class="text-muted">Sin plan</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($tenant->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ url('/admin/tenants/' . $tenant->id) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ url('/admin/tenants/' . $tenant->id . '/edit') }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ url('/admin/tenants/' . $tenant->id) }}" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este tenant?')">
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
                        <td colspan="9" class="text-center text-muted py-4">No se encontraron tenants</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $tenants->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
