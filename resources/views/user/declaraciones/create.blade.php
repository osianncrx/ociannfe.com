@extends('layouts.app')

@section('title', 'Generar Declaración D-104')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-calculator me-2"></i>Generar Declaración D-104</h2>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-exclamation-triangle me-2"></i>Errores:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($entreUnoYQuince && count($cedulasPendientes) > 0)
<div class="alert alert-warning" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Tiene comprobantes sin declarar.</strong> La fecha límite para la declaración D-104 es el día 15 de este mes.
    <ul class="mb-0 mt-2">
        @foreach($cedulasPendientes as $ced => $cantidad)
            <li>Cédula <strong>{{ $ced }}</strong>: {{ $cantidad }} comprobante{{ $cantidad > 1 ? 's' : '' }} pendiente{{ $cantidad > 1 ? 's' : '' }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('declaraciones.store') }}" method="POST">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Parámetros de la Declaración</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="cedula" class="form-label">Cédula</label>
                    <select class="form-select" id="cedula" name="cedula" required>
                        <option value="">Seleccione...</option>
                        @foreach($cedulas as $item)
                            <option value="{{ $item->cedula }}" {{ old('cedula') == $item->cedula ? 'selected' : '' }}>
                                {{ $item->cedula }} — {{ $item->nombres }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    @php
                        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                                  7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
                    @endphp
                    <input type="text" class="form-control" value="{{ $meses[$periodoMes] ?? $periodoMes }} {{ $periodoAnio }}" readonly>
                    <input type="hidden" name="periodo_anio" value="{{ $periodoAnio }}">
                    <input type="hidden" name="periodo_mes" value="{{ $periodoMes }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <input type="text" class="form-control" value="D-104 — IVA Mensual" readonly>
                </div>
            </div>

            <div class="alert alert-info mt-3 py-2">
                <i class="fas fa-info-circle me-1"></i>
                Se calcularán todas las emisiones y recepciones aceptadas del período para la cédula seleccionada, desglosadas por tarifa IVA.
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-calculator me-1"></i>Generar Declaración
        </button>
        <a href="{{ route('declaraciones.index') }}" class="btn btn-secondary btn-lg ms-2">
            <i class="fas fa-times me-1"></i>Cancelar
        </a>
    </div>
</form>
@endsection
