@extends('layouts.app')

@section('title', 'Detalle de Recepción')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-inbox me-2"></i>Detalle de Recepción</h2>
    <a href="{{ route('recepciones.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de la Recepción</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Clave</dt>
                            <dd class="col-sm-8"><code class="small">{{ $recepcion->clave }}</code></dd>

                            <dt class="col-sm-4">Estado</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $recepcion->estado_badge ?? 'secondary' }} fs-6">{{ $recepcion->estado_texto ?? $recepcion->estado }}</span>
                            </dd>

                            <dt class="col-sm-4">Mensaje</dt>
                            <dd class="col-sm-8">{{ $recepcion->mensaje ?? 'Sin mensaje' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Empresa</dt>
                            <dd class="col-sm-8">{{ $recepcion->empresa->nombre ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Cédula Empresa</dt>
                            <dd class="col-sm-8"><code>{{ $recepcion->empresa->cedula ?? 'N/A' }}</code></dd>

                            <dt class="col-sm-4">Fecha</dt>
                            <dd class="col-sm-8">{{ $recepcion->created_at->format('d/m/Y H:i:s') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
