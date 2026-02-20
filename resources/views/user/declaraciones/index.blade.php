@extends('layouts.app')

@section('title', 'Declaraciones de Impuestos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>Declaraciones de Impuestos</h2>
    <a href="{{ route('declaraciones.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Generar Declaración
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Cédula</label>
                <select name="cedula" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach($cedulas as $ced)
                        <option value="{{ $ced }}" {{ request('cedula') == $ced ? 'selected' : '' }}>{{ $ced }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="D-104" {{ request('tipo') == 'D-104' ? 'selected' : '' }}>D-104 (IVA)</option>
                    <option value="D-101" {{ request('tipo') == 'D-101' ? 'selected' : '' }}>D-101 (Renta)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Cédula</th>
                        <th>Período</th>
                        <th class="text-end">IVA Trasladado</th>
                        <th class="text-end">IVA Acreditable</th>
                        <th class="text-end">Impuesto Neto</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($declaraciones as $dec)
                    <tr>
                        <td><span class="badge bg-primary">{{ $dec->tipo_declaracion }}</span></td>
                        <td>{{ $dec->cedula ?? 'N/A' }}</td>
                        <td>{{ $dec->periodo_texto }}</td>
                        <td class="text-end">{{ number_format((float) $dec->total_iva_trasladado, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $dec->total_iva_acreditable, 2) }}</td>
                        <td class="text-end fw-bold {{ (float) $dec->impuesto_neto >= 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format((float) $dec->impuesto_neto, 2) }}
                        </td>
                        <td><span class="badge bg-{{ $dec->estado_badge }}">{{ $dec->estado_texto }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('declaraciones.show', $dec->id_declaracion) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay declaraciones generadas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($declaraciones->hasPages())
    <div class="card-footer bg-white">{{ $declaraciones->links() }}</div>
    @endif
</div>
@endsection
