@extends('layouts.app')

@section('title', $declaracion->tipo_declaracion . ' — ' . $declaracion->periodo_texto)

@php
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
              7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $tarifaLabels = ['0' => '0%', '1' => '1%', '2' => '2%', '4' => '4%', '8' => '8%', '13' => '13%'];
    $tarifas = $declaracion->detalle_tarifas ?? [];
    $emitidosBase = $tarifas['emitidos'] ?? [];
    $recibidosBase = $tarifas['recibidos'] ?? [];
    $emitidosIva = $tarifas['emitidos_iva'] ?? [];
    $recibidosIva = $tarifas['recibidos_iva'] ?? [];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">
            <i class="fas fa-file-invoice-dollar me-2"></i>
            {{ $declaracion->periodo_anio }} — {{ $meses[$declaracion->periodo_mes] ?? $declaracion->periodo_mes }} — {{ $declaracion->cedula }}
        </h2>
        <p class="text-muted mb-0 mt-1">{{ $declaracion->tipo_declaracion }}</p>
    </div>
    <div>
        <span class="badge bg-{{ $declaracion->estado_badge }} fs-6">{{ $declaracion->estado_texto }}</span>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('info'))
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">IVA Trasladado (Ventas)</h6>
                <p class="fs-3 fw-bold text-primary mb-0">{{ number_format((float) $declaracion->total_iva_trasladado, 2) }}</p>
                <small class="text-muted">Base gravada: {{ number_format((float) $declaracion->total_ventas_gravadas, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">IVA Acreditable (Compras)</h6>
                <p class="fs-3 fw-bold text-success mb-0">{{ number_format((float) $declaracion->total_iva_acreditable, 2) }}</p>
                <small class="text-muted">Base gravada: {{ number_format((float) $declaracion->total_compras_gravadas, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Impuesto Neto</h6>
                <p class="fs-3 fw-bold {{ (float) $declaracion->impuesto_neto >= 0 ? 'text-danger' : 'text-success' }} mb-0">
                    {{ number_format((float) $declaracion->impuesto_neto, 2) }}
                </p>
                <small class="text-muted">{{ (float) $declaracion->impuesto_neto >= 0 ? 'A pagar' : 'Saldo a favor' }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-arrow-up me-2 text-primary"></i>Emitidos</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarifa</th>
                            <th class="text-end">Base</th>
                            <th class="text-end">IVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalEmitido = 0; $totalIvaEmitido = 0; @endphp
                        @foreach($tarifaLabels as $key => $label)
                            @php
                                $base = (float) ($emitidosBase[$key] ?? 0);
                                $iva = (float) ($emitidosIva[$key] ?? 0);
                                $totalEmitido += $base;
                                $totalIvaEmitido += $iva;
                            @endphp
                            @if($base != 0 || $iva != 0)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ number_format($base, 2) }}</td>
                                <td class="text-end">{{ number_format($iva, 2) }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td>Total Emitido</td>
                            <td class="text-end">{{ number_format($totalEmitido, 2) }}</td>
                            <td class="text-end">{{ number_format($totalIvaEmitido, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-arrow-down me-2 text-success"></i>Recibidos</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarifa</th>
                            <th class="text-end">Base</th>
                            <th class="text-end">IVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalRecibido = 0; $totalIvaRecibido = 0; @endphp
                        @foreach($tarifaLabels as $key => $label)
                            @php
                                $base = (float) ($recibidosBase[$key] ?? 0);
                                $iva = (float) ($recibidosIva[$key] ?? 0);
                                $totalRecibido += $base;
                                $totalIvaRecibido += $iva;
                            @endphp
                            @if($base != 0 || $iva != 0)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ number_format($base, 2) }}</td>
                                <td class="text-end">{{ number_format($iva, 2) }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td>Total Recibido</td>
                            <td class="text-end">{{ number_format($totalRecibido, 2) }}</td>
                            <td class="text-end">{{ number_format($totalIvaRecibido, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@if($declaracion->detalle_actividades && count($declaracion->detalle_actividades) > 0)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Desglose por Actividad Económica (Ventas)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Actividad</th>
                        <th class="text-end">Gravado</th>
                        <th class="text-end">Exento</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($declaracion->detalle_actividades as $actividad => $datos)
                    <tr>
                        <td><code>{{ $actividad }}</code></td>
                        <td class="text-end">{{ number_format((float) ($datos['gravado'] ?? 0), 2) }}</td>
                        <td class="text-end">{{ number_format((float) ($datos['exento'] ?? 0), 2) }}</td>
                        <td class="text-end">{{ number_format((float) ($datos['iva'] ?? 0), 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) ($datos['total'] ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if($declaracion->datos_calculados)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Cálculo</h5>
    </div>
    <div class="card-body">
        @php $calc = $declaracion->datos_calculados; @endphp
        <div class="row">
            <div class="col-md-3"><strong>Emisiones procesadas:</strong> {{ $calc['total_emisiones'] ?? 0 }}</div>
            <div class="col-md-3"><strong>Recepciones procesadas:</strong> {{ $calc['total_recepciones'] ?? 0 }}</div>
            <div class="col-md-3"><strong>Período:</strong> {{ $calc['periodo_inicio'] ?? '' }} a {{ $calc['periodo_fin'] ?? '' }}</div>
            <div class="col-md-3"><strong>Calculado:</strong> {{ $calc['fecha_calculo'] ?? '' }}</div>
        </div>
    </div>
</div>
@endif

<div class="mb-4 d-flex gap-2">
    @if($declaracion->estado !== 'presentada')
    <form action="{{ route('declaraciones.update', $declaracion->id_declaracion) }}" method="POST" class="d-inline">
        @csrf @method('PUT')
        <input type="hidden" name="recalcular" value="1">
        <button type="submit" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-sync me-1"></i>Recalcular
        </button>
    </form>
    <form action="{{ route('declaraciones.update', $declaracion->id_declaracion) }}" method="POST" class="d-inline">
        @csrf @method('PUT')
        <input type="hidden" name="marcar_presentada" value="1">
        <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('¿Marcar como presentada?')">
            <i class="fas fa-check me-1"></i>Marcar como Presentada
        </button>
    </form>
    <form action="{{ route('declaraciones.destroy', $declaracion->id_declaracion) }}" method="POST" class="d-inline">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-lg" onclick="return confirm('¿Eliminar esta declaración?')">
            <i class="fas fa-trash me-1"></i>Eliminar
        </button>
    </form>
    @endif
    <a href="{{ route('declaraciones.index') }}" class="btn btn-secondary btn-lg">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>
@endsection
