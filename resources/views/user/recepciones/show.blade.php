@extends('layouts.app')

@section('title', 'Detalle de Recepción')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fas fa-inbox me-2"></i>Detalle de Recepción</h2>
        @if($recepcion->TipoDocumento)
        <p class="text-muted mb-0 mt-1">{{ $recepcion->tipo_documento_texto }} — {{ $recepcion->Emisor_Nombre ?? '' }}</p>
        @endif
    </div>
    <a href="{{ route('recepciones.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Documento</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Clave</dt>
                    <dd class="col-sm-7"><code class="small">{{ $recepcion->clave }}</code></dd>

                    <dt class="col-sm-5">Tipo Documento</dt>
                    <dd class="col-sm-7">{{ $recepcion->TipoDocumento ? $recepcion->TipoDocumento . ' - ' . $recepcion->tipo_documento_texto : 'N/A' }}</dd>

                    <dt class="col-sm-5">Fecha Emisión</dt>
                    <dd class="col-sm-7">{{ $recepcion->FechaEmision ? $recepcion->FechaEmision->format('d/m/Y H:i:s') : 'N/A' }}</dd>

                    <dt class="col-sm-5">Moneda</dt>
                    <dd class="col-sm-7">{{ $recepcion->CodigoMoneda ?? 'CRC' }}</dd>

                    <dt class="col-sm-5">Total Impuesto</dt>
                    <dd class="col-sm-7 fw-semibold">{{ number_format((float) $recepcion->TotalImpuesto, 2) }}</dd>

                    <dt class="col-sm-5">Total Comprobante</dt>
                    <dd class="col-sm-7 fw-bold fs-5 text-primary">{{ number_format((float) $recepcion->TotalComprobante, 2) }}</dd>

                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-{{ $recepcion->estado_badge }} fs-6">{{ $recepcion->estado_texto }}</span>
                    </dd>

                    @if($recepcion->mensaje)
                    <dt class="col-sm-5">Mensaje Hacienda</dt>
                    <dd class="col-sm-7">{{ $recepcion->mensaje }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Emisor / Receptor</h5>
            </div>
            <div class="card-body">
                <h6><i class="fas fa-building me-1"></i>Emisor (quien envió el documento)</h6>
                <dl class="row mb-3">
                    <dt class="col-sm-5">Nombre</dt>
                    <dd class="col-sm-7">{{ $recepcion->Emisor_Nombre ?? 'N/A' }}</dd>
                    <dt class="col-sm-5">Identificación</dt>
                    <dd class="col-sm-7">{{ $recepcion->Emisor_TipoIdentificacion ?? '' }} {{ $recepcion->Emisor_NumeroIdentificacion ?? 'N/A' }}</dd>
                    @if($recepcion->Emisor_CorreoElectronico)
                    <dt class="col-sm-5">Correo</dt>
                    <dd class="col-sm-7">{{ $recepcion->Emisor_CorreoElectronico }}</dd>
                    @endif
                </dl>

                <h6><i class="fas fa-user me-1"></i>Receptor (nuestra empresa)</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Empresa</dt>
                    <dd class="col-sm-7">{{ $recepcion->empresa->Nombre ?? $recepcion->Receptor_Nombre ?? 'N/A' }}</dd>
                    <dt class="col-sm-5">Cédula</dt>
                    <dd class="col-sm-7"><code>{{ $recepcion->empresa->cedula ?? $recepcion->Receptor_NumeroIdentificacion ?? 'N/A' }}</code></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@if($recepcion->respuesta_tipo)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-reply me-2"></i>Respuesta (Mensaje Receptor)</h5>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Tipo Respuesta</dt>
            <dd class="col-sm-9">{{ $recepcion->respuesta_tipo }} - {{ $recepcion->respuesta_tipo_texto }}</dd>

            @if($recepcion->respuesta_consecutivo)
            <dt class="col-sm-3">Consecutivo</dt>
            <dd class="col-sm-9"><code>{{ $recepcion->respuesta_consecutivo }}</code></dd>
            @endif

            @if($recepcion->respuesta_mensaje)
            <dt class="col-sm-3">Mensaje</dt>
            <dd class="col-sm-9">{{ $recepcion->respuesta_mensaje }}</dd>
            @endif
        </dl>
    </div>
</div>
@endif

@if($recepcion->xml_original)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-code me-2"></i>XML Original</h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="btnCopiarXml" title="Copiar XML">
                <i class="fas fa-copy me-1"></i>Copiar
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnDescargarXml" title="Descargar XML">
                <i class="fas fa-download me-1"></i>Descargar
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <pre class="mb-0 p-3 bg-light" style="max-height: 500px; overflow: auto; font-size: 0.8rem; white-space: pre-wrap; word-wrap: break-word;"><code id="xml-content">{{ $recepcion->xml_original }}</code></pre>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var xmlContent = document.getElementById('xml-content');
    if (!xmlContent) return;

    var btnCopiar = document.getElementById('btnCopiarXml');
    if (btnCopiar) {
        btnCopiar.addEventListener('click', function () {
            navigator.clipboard.writeText(xmlContent.textContent).then(function () {
                btnCopiar.innerHTML = '<i class="fas fa-check me-1"></i>Copiado';
                setTimeout(function () {
                    btnCopiar.innerHTML = '<i class="fas fa-copy me-1"></i>Copiar';
                }, 2000);
            });
        });
    }

    var btnDescargar = document.getElementById('btnDescargarXml');
    if (btnDescargar) {
        btnDescargar.addEventListener('click', function () {
            var blob = new Blob([xmlContent.textContent], { type: 'application/xml' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = '{{ $recepcion->clave }}.xml';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }
});
</script>
@endpush
