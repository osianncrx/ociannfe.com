@extends('layouts.app')

@section('title', 'Detalle del Comprobante')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Detalle del Comprobante</h2>
    <a href="{{ route('comprobantes.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Tipo</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-primary">{{ $comprobante->tipo_documento ?? '—' }}</span>
                                {{ $comprobante->tipo_documento_texto }}
                            </dd>

                            <dt class="col-sm-4">Clave</dt>
                            <dd class="col-sm-8"><code class="small">{{ $comprobante->clave }}</code></dd>

                            <dt class="col-sm-4">Consecutivo</dt>
                            <dd class="col-sm-8"><code>{{ $comprobante->NumeroConsecutivo }}</code></dd>

                            <dt class="col-sm-4">Fecha Emisión</dt>
                            <dd class="col-sm-8">{{ $comprobante->FechaEmision ? $comprobante->FechaEmision->format('d/m/Y H:i:s') : ($comprobante->FechaCreacion ?? '—') }}</dd>

                            <dt class="col-sm-4">Condición Venta</dt>
                            <dd class="col-sm-8">{{ $comprobante->CondicionVenta ?? '—' }}</dd>

                            <dt class="col-sm-4">Medio de Pago</dt>
                            <dd class="col-sm-8">{{ $comprobante->MedioPago ?? '—' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Estado</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $comprobante->estado_badge }} fs-6">{{ $comprobante->estado_texto }}</span>
                            </dd>

                            <dt class="col-sm-4">Mensaje Hacienda</dt>
                            <dd class="col-sm-8">{{ $comprobante->mensaje ?? 'Sin respuesta' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Emisor</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nombre</dt>
                    <dd class="col-sm-8">{{ $comprobante->Emisor_Nombre ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Identificación</dt>
                    <dd class="col-sm-8">
                        <code>{{ $comprobante->Emisor_TipoIdentificacion ?? '' }}-{{ $comprobante->Emisor_NumeroIdentificacion ?? 'N/A' }}</code>
                    </dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $comprobante->Emisor_CorreoElectronico ?? 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Receptor</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nombre</dt>
                    <dd class="col-sm-8">{{ $comprobante->Receptor_Nombre ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Identificación</dt>
                    <dd class="col-sm-8">
                        <code>{{ $comprobante->Receptor_TipoIdentificacion ?? '' }}{{ $comprobante->Receptor_NumeroIdentificacion ? '-' . $comprobante->Receptor_NumeroIdentificacion : 'N/A' }}</code>
                    </dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $comprobante->Receptor_CorreoElectronico ?? 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Líneas de Detalle</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Código</th>
                                <th>Detalle</th>
                                <th>Unidad</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">Impuesto</th>
                                <th class="text-end">Total Línea</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comprobante->lineas ?? [] as $linea)
                            <tr>
                                <td>{{ $linea->NumeroLinea }}</td>
                                <td><code>{{ $linea->Codigo ?? 'N/A' }}</code></td>
                                <td>{{ $linea->Detalle }}</td>
                                <td>{{ $linea->UnidadMedida ?? '—' }}</td>
                                <td class="text-end">{{ number_format((float)$linea->Cantidad, 2) }}</td>
                                <td class="text-end">₡{{ number_format((float)$linea->PrecioUnitario, 2) }}</td>
                                <td class="text-end">₡{{ number_format((float)$linea->SubTotal, 2) }}</td>
                                <td class="text-end">
                                    ₡{{ number_format((float)($linea->Impuesto_Monto ?? 0), 2) }}
                                    @if($linea->Impuesto_Tarifa)
                                        <span class="text-muted small">({{ $linea->Impuesto_Tarifa }}%)</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">₡{{ number_format((float)$linea->MontoTotalLinea, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">Sin líneas de detalle.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Totales</h5>
            </div>
            <div class="card-body">
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="fw-semibold">Total Venta:</td>
                                <td class="text-end">₡{{ number_format((float)($comprobante->TotalVenta ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Descuento:</td>
                                <td class="text-end">₡{{ number_format((float)($comprobante->TotalDescuentos ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Venta Neta:</td>
                                <td class="text-end">₡{{ number_format((float)($comprobante->TotalVentaNeta ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Impuesto:</td>
                                <td class="text-end">₡{{ number_format((float)($comprobante->TotalImpuesto ?? 0), 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-bold fs-5">Total Comprobante:</td>
                                <td class="text-end fw-bold fs-5">₡{{ number_format((float)($comprobante->TotalComprobante ?? 0), 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex flex-wrap gap-2">
    @if($comprobante->estado == \App\Models\Emision::ESTADO_PENDIENTE)
        <form action="{{ route('comprobantes.procesar-envio', $comprobante->id_emision) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-paper-plane me-1"></i>Enviar a Hacienda
            </button>
        </form>
    @endif

    @if($comprobante->estado == \App\Models\Emision::ESTADO_ENVIADO)
        <form action="{{ route('comprobantes.consultar-estado', $comprobante->id_emision) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-info text-white">
                <i class="fas fa-sync-alt me-1"></i>Consultar Estado en Hacienda
            </button>
        </form>
    @endif

    @if($comprobante->permiteNotaCredito())
        <a href="{{ route('comprobantes.create', ['ref' => $comprobante->id_emision, 'tipo_nota' => '03']) }}" class="btn btn-success">
            <i class="fas fa-minus-circle me-1"></i>Crear Nota de Crédito
        </a>
    @endif

    @if($comprobante->permiteNotaDebito())
        <a href="{{ route('comprobantes.create', ['ref' => $comprobante->id_emision, 'tipo_nota' => '02']) }}" class="btn btn-danger">
            <i class="fas fa-plus-circle me-1"></i>Crear Nota de Débito
        </a>
    @endif

    <a href="{{ route('comprobantes.pdf', $comprobante->id_emision) }}" class="btn btn-outline-danger" target="_blank">
        <i class="fas fa-file-pdf me-1"></i>Descargar PDF
    </a>

    @if($comprobante->clave)
    <a href="{{ route('comprobantes.xml', $comprobante->clave) }}" class="btn btn-outline-primary" target="_blank">
        <i class="fas fa-code me-1"></i>Ver XML
    </a>
    @endif

    <a href="{{ route('comprobantes.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver a la lista
    </a>
</div>

@if($comprobante->xml_comprobante)
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-code me-2"></i>XML del Comprobante</h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-copiar-xml" data-target="xml-comprobante-content" title="Copiar XML">
                <i class="fas fa-copy me-1"></i>Copiar
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-descargar-xml" data-target="xml-comprobante-content" data-filename="{{ $comprobante->clave }}.xml" title="Descargar XML">
                <i class="fas fa-download me-1"></i>Descargar
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <pre class="mb-0 p-3 bg-light" style="max-height: 400px; overflow: auto; font-size: 0.8rem; white-space: pre-wrap; word-wrap: break-word;"><code id="xml-comprobante-content">{{ $comprobante->xml_comprobante }}</code></pre>
    </div>
</div>
@endif

@if(!empty($xmlRespuesta))
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-reply me-2"></i>XML Respuesta de Hacienda</h5>
        <div>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-copiar-xml" data-target="xml-respuesta-content" title="Copiar XML">
                <i class="fas fa-copy me-1"></i>Copiar
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-descargar-xml" data-target="xml-respuesta-content" data-filename="{{ $comprobante->clave }}-respuesta.xml" title="Descargar XML">
                <i class="fas fa-download me-1"></i>Descargar
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <pre class="mb-0 p-3 bg-light" style="max-height: 400px; overflow: auto; font-size: 0.8rem; white-space: pre-wrap; word-wrap: break-word;"><code id="xml-respuesta-content">{{ $xmlRespuesta }}</code></pre>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-copiar-xml').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var content = document.getElementById(this.dataset.target).textContent;
            var self = this;
            navigator.clipboard.writeText(content).then(function () {
                self.innerHTML = '<i class="fas fa-check me-1"></i>Copiado';
                setTimeout(function () { self.innerHTML = '<i class="fas fa-copy me-1"></i>Copiar'; }, 2000);
            });
        });
    });

    document.querySelectorAll('.btn-descargar-xml').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var content = document.getElementById(this.dataset.target).textContent;
            var blob = new Blob([content], { type: 'application/xml' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = this.dataset.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    });
});
</script>
@endpush
