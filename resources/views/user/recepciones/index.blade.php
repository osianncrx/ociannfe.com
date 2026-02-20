@extends('layouts.app')

@section('title', 'Recepciones')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-inbox me-2"></i>Recepciones</h2>
    <a href="{{ route('recepciones.create') }}" class="btn btn-primary">
        <i class="fas fa-file-import me-1"></i>Recepcionar Documento
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

@if(session('errores_masivo'))
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-exclamation-triangle me-2"></i>Algunos archivos tuvieron errores:</strong>
    <ul class="mb-0 mt-2">
        @foreach(session('errores_masivo') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Empresa</label>
                <select name="empresa" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach($empresas as $emp)
                        <option value="{{ $emp->id_empresa }}" {{ request('empresa') == $emp->id_empresa ? 'selected' : '' }}>{{ $emp->Nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Pendiente</option>
                    <option value="2" {{ request('estado') == '2' ? 'selected' : '' }}>Enviado</option>
                    <option value="3" {{ request('estado') == '3' ? 'selected' : '' }}>Aceptado</option>
                    <option value="4" {{ request('estado') == '4' ? 'selected' : '' }}>Rechazado</option>
                    <option value="5" {{ request('estado') == '5' ? 'selected' : '' }}>Error</option>
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
                        <th>Emisor</th>
                        <th>Clave</th>
                        <th>Fecha</th>
                        <th class="text-end">Total</th>
                        <th>Respuesta</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recepciones ?? [] as $recepcion)
                    <tr>
                        <td>
                            @if($recepcion->TipoDocumento)
                                <span class="badge bg-secondary">{{ $recepcion->TipoDocumento }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ Str::limit($recepcion->Emisor_Nombre ?? 'N/A', 30) }}</td>
                        <td><code title="{{ $recepcion->clave }}">{{ Str::limit($recepcion->clave, 20) }}</code></td>
                        <td>{{ $recepcion->FechaEmision ? $recepcion->FechaEmision->format('d/m/Y') : '—' }}</td>
                        <td class="text-end fw-semibold">{{ $recepcion->TotalComprobante ? number_format((float) $recepcion->TotalComprobante, 2) : '—' }}</td>
                        <td>
                            @if($recepcion->respuesta_tipo)
                                <span class="badge bg-{{ $recepcion->respuesta_tipo === '07' ? 'danger' : 'success' }}">{{ $recepcion->respuesta_tipo_texto }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge bg-{{ $recepcion->estado_badge }}">{{ $recepcion->estado_texto }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('recepciones.show', $recepcion->id_recepcion) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No hay recepciones registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(isset($recepciones) && $recepciones->hasPages())
    <div class="card-footer bg-white">
        {{ $recepciones->links() }}
    </div>
    @endif
</div>
@endsection
