@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Panel de Administración</h2>
    <span class="text-muted">{{ now()->format('d/m/Y H:i') }}</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary-custom mb-2"><i class="fas fa-database fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['tenants'] ?? 0 }}</h3>
                <small class="text-muted">Tenants</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-info mb-2"><i class="fas fa-users fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['users'] ?? 0 }}</h3>
                <small class="text-muted">Usuarios</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2"><i class="fas fa-building fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['empresas'] ?? 0 }}</h3>
                <small class="text-muted">Empresas</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-secondary mb-2"><i class="fas fa-file-invoice fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['emisiones_total'] ?? 0 }}</h3>
                <small class="text-muted">Emisiones Total</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-warning mb-2"><i class="fas fa-calendar-day fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['emisiones_hoy'] ?? 0 }}</h3>
                <small class="text-muted">Emisiones Hoy</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2"><i class="fas fa-check-circle fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['emisiones_aceptadas'] ?? 0 }}</h3>
                <small class="text-muted">Aceptadas</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-danger mb-2"><i class="fas fa-times-circle fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['emisiones_rechazadas'] ?? 0 }}</h3>
                <small class="text-muted">Rechazadas</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-warning mb-2"><i class="fas fa-clock fa-2x"></i></div>
                <h3 class="fw-bold mb-0">{{ $stats['cola_pendiente'] ?? 0 }}</h3>
                <small class="text-muted">Cola</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="fas fa-database me-2"></i>Últimos Tenants
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestTenants ?? [] as $tenant)
                            <tr>
                                <td>{{ $tenant->name }}</td>
                                <td>{{ $tenant->email }}</td>
                                <td>{{ $tenant->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No hay tenants registrados</td>
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
            <div class="card-header bg-white fw-semibold">
                <i class="fas fa-file-invoice me-2"></i>Últimas Emisiones
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Clave</th>
                                <th>Consecutivo</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestEmisiones ?? [] as $emision)
                            <tr>
                                <td><span title="{{ $emision->clave }}">{{ Str::limit($emision->clave, 20) }}</span></td>
                                <td>{{ $emision->consecutivo }}</td>
                                <td>
                                    @if($emision->estado === 'aceptado')
                                        <span class="badge bg-success">Aceptado</span>
                                    @elseif($emision->estado === 'rechazado')
                                        <span class="badge bg-danger">Rechazado</span>
                                    @elseif($emision->estado === 'procesando')
                                        <span class="badge bg-warning text-dark">Procesando</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($emision->estado) }}</span>
                                    @endif
                                </td>
                                <td>{{ $emision->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No hay emisiones registradas</td>
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
