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
                            <dt class="col-sm-4">Clave</dt>
                            <dd class="col-sm-8"><code class="small">{{ $comprobante->clave }}</code></dd>

                            <dt class="col-sm-4">Consecutivo</dt>
                            <dd class="col-sm-8"><code>{{ $comprobante->consecutivo }}</code></dd>

                            <dt class="col-sm-4">Fecha</dt>
                            <dd class="col-sm-8">{{ $comprobante->created_at->format('d/m/Y H:i:s') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Estado</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $comprobante->estado_badge }} fs-6">{{ $comprobante->estado_texto }}</span>
                            </dd>

                            <dt class="col-sm-4">Mensaje Hacienda</dt>
                            <dd class="col-sm-8">{{ $comprobante->mensaje_hacienda ?? 'Sin respuesta' }}</dd>
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
                    <dd class="col-sm-8">{{ $comprobante->emisor_nombre ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Cédula</dt>
                    <dd class="col-sm-8"><code>{{ $comprobante->emisor_cedula ?? 'N/A' }}</code></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $comprobante->emisor_email ?? 'N/A' }}</dd>
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
                    <dd class="col-sm-8">{{ $comprobante->receptor_nombre ?? 'N/A' }}</dd>

                    <dt class="col-sm-4">Identificación</dt>
                    <dd class="col-sm-8"><code>{{ $comprobante->receptor_numero_id ?? 'N/A' }}</code></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $comprobante->receptor_email ?? 'N/A' }}</dd>
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
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio Unit.</th>
                                <th class="text-end">Impuesto</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comprobante->lineas ?? [] as $index => $linea)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><code>{{ $linea->codigo ?? 'N/A' }}</code></td>
                                <td>{{ $linea->detalle }}</td>
                                <td class="text-end">{{ number_format($linea->cantidad, 2) }}</td>
                                <td class="text-end">₡{{ number_format($linea->precio_unitario, 2) }}</td>
                                <td class="text-end">₡{{ number_format($linea->impuesto ?? 0, 2) }}</td>
                                <td class="text-end">₡{{ number_format($linea->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Sin líneas de detalle.</td>
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
                                <td class="fw-semibold">Subtotal:</td>
                                <td class="text-end">₡{{ number_format($comprobante->total_venta ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Descuento:</td>
                                <td class="text-end">₡{{ number_format($comprobante->total_descuento ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Impuesto:</td>
                                <td class="text-end">₡{{ number_format($comprobante->total_impuesto ?? 0, 2) }}</td>
                            </tr>
                            <tr class="table-light">
                                <td class="fw-bold fs-5">Total:</td>
                                <td class="text-end fw-bold fs-5">₡{{ number_format($comprobante->total_comprobante ?? 0, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($comprobante->xml_path)
<div class="mt-4">
    <a href="{{ route('comprobantes.download-xml', $comprobante) }}" class="btn btn-outline-primary">
        <i class="fas fa-download me-1"></i>Descargar XML
    </a>
</div>
@endif
@endsection
