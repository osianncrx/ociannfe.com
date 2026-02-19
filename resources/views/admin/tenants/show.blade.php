@extends('layouts.app')

@section('title', 'Detalle del Tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-database me-2"></i>Detalle del Tenant</h2>
    <div>
        <a href="{{ url('/admin/tenants/' . $tenant->id . '/edit') }}" class="btn btn-outline-warning">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="{{ url('/admin/tenants') }}" class="btn btn-outline-secondary ms-1">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="fas fa-info-circle me-2"></i>Información General
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th class="text-muted" style="width: 35%;">Nombre</th>
                        <td class="fw-semibold">{{ $tenant->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Email</th>
                        <td>{{ $tenant->email }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Slug</th>
                        <td><code>{{ $tenant->slug }}</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Creado</th>
                        <td>{{ $tenant->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Estado</th>
                        <td>
                            @if($tenant->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="fas fa-credit-card me-2"></i>Plan y Suscripción
            </div>
            <div class="card-body">
                @if($tenant->activeSubscription && $tenant->activeSubscription->plan)
                    @php $plan = $tenant->activeSubscription->plan; @endphp
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width: 35%;">Plan</th>
                            <td><span class="badge bg-info text-dark">{{ $plan->name }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Precio</th>
                            <td>{{ $plan->price }} {{ $plan->currency ?? 'CRC' }} / {{ $plan->billing_cycle ?? 'mes' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Inicio</th>
                            <td>{{ optional($tenant->activeSubscription->starts_at)->format('d/m/Y') ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Vencimiento</th>
                            <td>{{ optional($tenant->activeSubscription->ends_at)->format('d/m/Y') ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Estado</th>
                            <td><span class="badge bg-success">{{ ucfirst($tenant->activeSubscription->status ?? 'activa') }}</span></td>
                        </tr>
                    </table>
                @else
                    <p class="text-muted mb-0">Este tenant no tiene una suscripción activa.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i>Usuarios</span>
                <span class="badge bg-primary">{{ $tenant->users->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge bg-secondary">{{ $user->role ?? 'user' }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Sin usuarios</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-building me-2"></i>Empresas</span>
                <span class="badge bg-primary">{{ $tenant->empresas->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->empresas as $empresa)
                            <tr>
                                <td>{{ $empresa->nombre }}</td>
                                <td>{{ $empresa->cedula }}</td>
                                <td>
                                    @if($empresa->is_active ?? true)
                                        <span class="badge bg-success">Activa</span>
                                    @else
                                        <span class="badge bg-danger">Inactiva</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Sin empresas</td>
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
