@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
    <div>
        <a href="{{ route('comprobantes.create') }}" class="btn btn-primary me-2">
            <i class="fas fa-plus me-1"></i>Emitir Factura
        </a>
        <a href="{{ route('empresas.create') }}" class="btn btn-outline-primary">
            <i class="fas fa-building me-1"></i>Nueva Empresa
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary-custom mb-2"><i class="fas fa-building fa-2x"></i></div>
                <h3 class="fw-bold mb-1">{{ $empresasCount ?? 0 }}</h3>
                <small class="text-muted">Empresas</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-info mb-2"><i class="fas fa-paper-plane fa-2x"></i></div>
                <h3 class="fw-bold mb-1">{{ $emisionesHoy ?? 0 }}</h3>
                <small class="text-muted">Emitidos Hoy</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2"><i class="fas fa-check-circle fa-2x"></i></div>
                <h3 class="fw-bold mb-1">{{ $emisionesAceptadas ?? 0 }}</h3>
                <small class="text-muted">Aceptados</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-danger mb-2"><i class="fas fa-times-circle fa-2x"></i></div>
                <h3 class="fw-bold mb-1">{{ $emisionesRechazadas ?? 0 }}</h3>
                <small class="text-muted">Rechazados</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-warning mb-2"><i class="fas fa-clock fa-2x"></i></div>
                <h3 class="fw-bold mb-1">{{ $emisionesPendientes ?? 0 }}</h3>
                <small class="text-muted">Pendientes</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-secondary mb-2"><i class="fas fa-list-ol fa-2x"></i></div>
                <h3 class="fw-bold mb-1">{{ $colaCount ?? 0 }}</h3>
                <small class="text-muted">Cola</small>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimas Emisiones</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Consecutivo</th>
                        <th>Receptor</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ultimasEmisiones ?? [] as $emision)
                    <tr>
                        <td><code>{{ $emision->consecutivo }}</code></td>
                        <td>{{ $emision->receptor_nombre ?? 'N/A' }}</td>
                        <td class="text-end">₡{{ number_format($emision->total_comprobante, 2) }}</td>
                        <td><span class="badge bg-{{ $emision->estado_badge }}">{{ $emision->estado_texto }}</span></td>
                        <td>{{ $emision->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No hay emisiones recientes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
