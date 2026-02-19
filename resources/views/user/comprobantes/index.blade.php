@extends('layouts.app')

@section('title', 'Comprobantes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-receipt me-2"></i>Comprobantes</h2>
    <a href="{{ route('comprobantes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Emitir Comprobante
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('comprobantes.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select form-select-sm" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="aceptado" {{ request('estado') == 'aceptado' ? 'selected' : '' }}>Aceptado</option>
                        <option value="rechazado" {{ request('estado') == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                        <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="enviado" {{ request('estado') == 'enviado' ? 'selected' : '' }}>Enviado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="empresa_id" class="form-label">Empresa</label>
                    <select class="form-select form-select-sm" id="empresa_id" name="empresa_id">
                        <option value="">Todas</option>
                        @foreach($empresas ?? [] as $empresa)
                            <option value="{{ $empresa->id }}" {{ request('empresa_id') == $empresa->id ? 'selected' : '' }}>{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-2">
                    <label for="buscar" class="form-label">Buscar</label>
                    <input type="text" class="form-control form-control-sm" id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Clave, receptor...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                </div>
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
                        <th>Clave</th>
                        <th>Consecutivo</th>
                        <th>Receptor</th>
                        <th class="text-end">Total Comprobante</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comprobantes as $comprobante)
                    <tr>
                        <td><code title="{{ $comprobante->clave }}">{{ Str::limit($comprobante->clave, 20) }}</code></td>
                        <td><code>{{ $comprobante->consecutivo }}</code></td>
                        <td>{{ $comprobante->receptor_nombre ?? 'N/A' }}</td>
                        <td class="text-end">₡{{ number_format($comprobante->total_comprobante, 2) }}</td>
                        <td><span class="badge bg-{{ $comprobante->estado_badge }}">{{ $comprobante->estado_texto }}</span></td>
                        <td>{{ $comprobante->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('comprobantes.show', $comprobante) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se encontraron comprobantes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($comprobantes->hasPages())
    <div class="card-footer bg-white">
        {{ $comprobantes->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
