@extends('layouts.app')

@section('title', isset($refComprobante) && $tipoNota === '03' ? 'Emitir Nota de Crédito' : (isset($refComprobante) && $tipoNota === '02' ? 'Emitir Nota de Débito' : 'Emitir Comprobante'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    @if(isset($refComprobante) && $tipoNota)
        <h2 class="mb-0">
            <i class="fas fa-file-invoice me-2"></i>
            Emitir {{ $tipoNota === '03' ? 'Nota de Crédito' : 'Nota de Débito' }}
        </h2>
    @else
        <h2 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Emitir Comprobante</h2>
    @endif
</div>

@if(isset($refComprobante) && $tipoNota)
<div class="alert alert-{{ $tipoNota === '03' ? 'success' : 'danger' }} d-flex align-items-center mb-4" role="alert">
    <i class="fas fa-{{ $tipoNota === '03' ? 'minus-circle' : 'plus-circle' }} fa-2x me-3"></i>
    <div>
        <strong>{{ $tipoNota === '03' ? 'Nota de Crédito' : 'Nota de Débito' }}</strong> referenciando el comprobante:
        <code>{{ $refComprobante->clave }}</code>
        <br>
        <small>Receptor: {{ $refComprobante->Receptor_Nombre }} — Total: ₡{{ number_format((float)($refComprobante->TotalComprobante ?? 0), 2) }}</small>
    </div>
</div>
@endif

<div id="receptor-alert" class="alert alert-info alert-dismissible fade d-none" role="alert">
    <i class="fas fa-check-circle me-2"></i><span id="receptor-alert-text"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<div id="receptor-error" class="alert alert-warning alert-dismissible fade d-none" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><span id="receptor-error-text"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-exclamation-triangle me-2"></i>Por favor corrija los siguientes errores:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form action="{{ route('comprobantes.store') }}" method="POST" id="formComprobante">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Datos del Documento</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="id_empresa" class="form-label">Empresa Emisora</label>
                    <select class="form-select @error('id_empresa') is-invalid @enderror" id="id_empresa" name="id_empresa" required>
                        <option value="">Seleccione...</option>
                        @foreach($empresas ?? [] as $empresa)
                            <option value="{{ $empresa->id_empresa }}" {{ old('id_empresa', isset($refComprobante) ? $refComprobante->id_empresa : '') == $empresa->id_empresa ? 'selected' : '' }}>
                                {{ $empresa->Nombre }} ({{ $empresa->cedula }})
                            </option>
                        @endforeach
                    </select>
                    @error('id_empresa')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="tipo_documento" class="form-label">Tipo Documento</label>
                    @php $defaultTipoDoc = old('tipo_documento', $tipoNota ?? '01'); @endphp
                    <select class="form-select @error('tipo_documento') is-invalid @enderror" id="tipo_documento" name="tipo_documento" required>
                        <option value="">Seleccione...</option>
                        <option value="01" {{ $defaultTipoDoc == '01' ? 'selected' : '' }}>01 - Factura Electrónica</option>
                        <option value="02" {{ $defaultTipoDoc == '02' ? 'selected' : '' }}>02 - Nota de Débito</option>
                        <option value="03" {{ $defaultTipoDoc == '03' ? 'selected' : '' }}>03 - Nota de Crédito</option>
                        <option value="04" {{ $defaultTipoDoc == '04' ? 'selected' : '' }}>04 - Tiquete Electrónico</option>
                        <option value="08" {{ $defaultTipoDoc == '08' ? 'selected' : '' }}>08 - Factura Compra</option>
                        <option value="09" {{ $defaultTipoDoc == '09' ? 'selected' : '' }}>09 - Factura Exportación</option>
                        <option value="10" {{ $defaultTipoDoc == '10' ? 'selected' : '' }}>10 - Recibo Electrónico de Pago</option>
                    </select>
                    @error('tipo_documento')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="condicion_venta" class="form-label">Condición Venta</label>
                    @php $defaultCondVenta = old('condicion_venta', isset($refComprobante) ? $refComprobante->CondicionVenta : '01'); @endphp
                    <select class="form-select @error('condicion_venta') is-invalid @enderror" id="condicion_venta" name="condicion_venta" required>
                        <option value="">Seleccione...</option>
                        <option value="01" {{ $defaultCondVenta == '01' ? 'selected' : '' }}>01 - Contado</option>
                        <option value="02" {{ $defaultCondVenta == '02' ? 'selected' : '' }}>02 - Crédito</option>
                        <option value="03" {{ $defaultCondVenta == '03' ? 'selected' : '' }}>03 - Consignación</option>
                        <option value="04" {{ $defaultCondVenta == '04' ? 'selected' : '' }}>04 - Apartado</option>
                        <option value="05" {{ $defaultCondVenta == '05' ? 'selected' : '' }}>05 - Arrendamiento opción compra</option>
                        <option value="06" {{ $defaultCondVenta == '06' ? 'selected' : '' }}>06 - Arrendamiento función financiera</option>
                        <option value="07" {{ $defaultCondVenta == '07' ? 'selected' : '' }}>07 - Cobro a favor de un tercero</option>
                        <option value="08" {{ $defaultCondVenta == '08' ? 'selected' : '' }}>08 - Servicios prestados al Estado</option>
                        <option value="09" {{ $defaultCondVenta == '09' ? 'selected' : '' }}>09 - Pago del servicios prestado al Estado</option>
                        <option value="99" {{ $defaultCondVenta == '99' ? 'selected' : '' }}>99 - Otros</option>
                    </select>
                    @error('condicion_venta')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="medio_pago" class="form-label">Medio de Pago</label>
                    @php $defaultMedioPago = old('medio_pago', isset($refComprobante) ? $refComprobante->MedioPago : ''); @endphp
                    <select class="form-select @error('medio_pago') is-invalid @enderror" id="medio_pago" name="medio_pago" required>
                        <option value="">Seleccione...</option>
                        <option value="01" {{ $defaultMedioPago == '01' ? 'selected' : '' }}>01 - Efectivo</option>
                        <option value="02" {{ $defaultMedioPago == '02' ? 'selected' : '' }}>02 - Tarjeta</option>
                        <option value="03" {{ $defaultMedioPago == '03' ? 'selected' : '' }}>03 - Cheque</option>
                        <option value="04" {{ $defaultMedioPago == '04' ? 'selected' : '' }}>04 - Transferencia / SINPE</option>
                        <option value="05" {{ $defaultMedioPago == '05' ? 'selected' : '' }}>05 - Recaudado por terceros</option>
                        <option value="06" {{ $defaultMedioPago == '06' ? 'selected' : '' }}>06 - SINPE Móvil</option>
                        <option value="99" {{ $defaultMedioPago == '99' ? 'selected' : '' }}>99 - Otros</option>
                    </select>
                    @error('medio_pago')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Receptor</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="receptor_tipo_id" class="form-label">Tipo ID</label>
                    @php $defaultRecTipo = old('receptor_tipo_id', isset($refComprobante) ? $refComprobante->Receptor_TipoIdentificacion : ''); @endphp
                    <select class="form-select @error('receptor_tipo_id') is-invalid @enderror" id="receptor_tipo_id" name="receptor_tipo_id">
                        <option value="">Sin ID</option>
                        <option value="01" {{ $defaultRecTipo == '01' ? 'selected' : '' }}>01 - Física</option>
                        <option value="02" {{ $defaultRecTipo == '02' ? 'selected' : '' }}>02 - Jurídica</option>
                        <option value="03" {{ $defaultRecTipo == '03' ? 'selected' : '' }}>03 - DIMEX</option>
                        <option value="04" {{ $defaultRecTipo == '04' ? 'selected' : '' }}>04 - NITE</option>
                        <option value="05" {{ $defaultRecTipo == '05' ? 'selected' : '' }}>05 - Extranjero</option>
                    </select>
                    @error('receptor_tipo_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="receptor_numero_id" class="form-label">Cédula / Número ID</label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('receptor_numero_id') is-invalid @enderror"
                               id="receptor_numero_id" name="receptor_numero_id"
                               value="{{ old('receptor_numero_id', isset($refComprobante) ? $refComprobante->Receptor_NumeroIdentificacion : '') }}" placeholder="Ej: 3101234567">
                        <button type="button" class="btn btn-outline-primary" id="btn-buscar-receptor" title="Consultar en Hacienda">
                            <i class="fas fa-search"></i>
                        </button>
                        @error('receptor_numero_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">Ingrese la cédula y presione <i class="fas fa-search"></i> para buscar.</div>
                </div>
                <div class="col-md-4">
                    <label for="receptor_nombre" class="form-label">Nombre / Razón Social</label>
                    <input type="text" class="form-control @error('receptor_nombre') is-invalid @enderror"
                           id="receptor_nombre" name="receptor_nombre"
                           value="{{ old('receptor_nombre', isset($refComprobante) ? $refComprobante->Receptor_Nombre : '') }}" required>
                    @error('receptor_nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="receptor_email" class="form-label">Correo Electrónico</label>
                    <input type="email" class="form-control @error('receptor_email') is-invalid @enderror"
                           id="receptor_email" name="receptor_email"
                           value="{{ old('receptor_email', isset($refComprobante) ? $refComprobante->Receptor_CorreoElectronico : '') }}" placeholder="correo@ejemplo.com">
                    @error('receptor_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div id="receptor-info" class="mt-3 d-none">
                <div class="alert alert-light border mb-0 py-2">
                    <div class="row small">
                        <div class="col-md-4"><strong>Contribuyente:</strong> <span id="info-contribuyente">—</span></div>
                        <div class="col-md-4"><strong>Régimen:</strong> <span id="info-regimen">—</span></div>
                        <div class="col-md-4"><strong>Actividad:</strong> <span id="info-actividad">—</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 d-none" id="card-referencia">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-link me-2"></i>Información de Referencia</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 mb-3">
                <i class="fas fa-info-circle me-1"></i>Las Notas de Crédito y Débito requieren referencia al documento original.
            </div>
            <div class="row g-3">
                @php
                    $defaultRefTipoDoc = old('ref_tipo_doc', isset($refComprobante) ? ($refComprobante->tipo_documento ?? '01') : '01');
                    $defaultRefNumero = old('ref_numero', isset($refComprobante) ? $refComprobante->clave : '');
                    $defaultRefFecha = old('ref_fecha', isset($refComprobante) && $refComprobante->FechaEmision ? $refComprobante->FechaEmision->format('Y-m-d\TH:i') : '');
                @endphp
                <div class="col-md-2">
                    <label for="ref_tipo_doc" class="form-label">Tipo Doc. Ref.</label>
                    <select class="form-select @error('ref_tipo_doc') is-invalid @enderror" id="ref_tipo_doc" name="ref_tipo_doc">
                        <option value="01" {{ $defaultRefTipoDoc == '01' ? 'selected' : '' }}>01 - Factura Electrónica</option>
                        <option value="02" {{ $defaultRefTipoDoc == '02' ? 'selected' : '' }}>02 - Nota de Débito</option>
                        <option value="03" {{ $defaultRefTipoDoc == '03' ? 'selected' : '' }}>03 - Nota de Crédito</option>
                        <option value="04" {{ $defaultRefTipoDoc == '04' ? 'selected' : '' }}>04 - Tiquete Electrónico</option>
                        <option value="08" {{ $defaultRefTipoDoc == '08' ? 'selected' : '' }}>08 - Factura Compra</option>
                        <option value="09" {{ $defaultRefTipoDoc == '09' ? 'selected' : '' }}>09 - Factura Exportación</option>
                        <option value="10" {{ $defaultRefTipoDoc == '10' ? 'selected' : '' }}>10 - Recibo Electrónico</option>
                        <option value="99" {{ $defaultRefTipoDoc == '99' ? 'selected' : '' }}>99 - Otros</option>
                    </select>
                    @error('ref_tipo_doc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="ref_numero" class="form-label">Clave / Número Doc. Original</label>
                    <input type="text" class="form-control @error('ref_numero') is-invalid @enderror"
                           id="ref_numero" name="ref_numero" value="{{ $defaultRefNumero }}"
                           placeholder="Clave numérica de 50 dígitos" maxlength="50">
                    @error('ref_numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label for="ref_fecha" class="form-label">Fecha Emisión Ref.</label>
                    <input type="datetime-local" class="form-control @error('ref_fecha') is-invalid @enderror"
                           id="ref_fecha" name="ref_fecha" value="{{ $defaultRefFecha }}">
                    @error('ref_fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label for="ref_codigo" class="form-label">Código</label>
                    <select class="form-select @error('ref_codigo') is-invalid @enderror" id="ref_codigo" name="ref_codigo">
                        <option value="01" {{ old('ref_codigo', '01') == '01' ? 'selected' : '' }}>01 - Anula documento</option>
                        <option value="02" {{ old('ref_codigo') == '02' ? 'selected' : '' }}>02 - Corrige texto</option>
                        <option value="03" {{ old('ref_codigo') == '03' ? 'selected' : '' }}>03 - Aplica descuento</option>
                        <option value="04" {{ old('ref_codigo') == '04' ? 'selected' : '' }}>04 - Referencia</option>
                        <option value="05" {{ old('ref_codigo') == '05' ? 'selected' : '' }}>05 - Sustituye</option>
                        <option value="99" {{ old('ref_codigo') == '99' ? 'selected' : '' }}>99 - Otros</option>
                    </select>
                    @error('ref_codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label for="ref_razon" class="form-label">Razón</label>
                    <input type="text" class="form-control @error('ref_razon') is-invalid @enderror"
                           id="ref_razon" name="ref_razon" value="{{ old('ref_razon') }}"
                           placeholder="Motivo de la nota" maxlength="180">
                    @error('ref_razon')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Líneas de Detalle</h5>
            <button type="button" class="btn btn-sm btn-success" id="btnAgregarLinea">
                <i class="fas fa-plus me-1"></i>Agregar Línea
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="tablaLineas">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 200px;">Código CABYS</th>
                            <th>Detalle</th>
                            <th style="width: 90px;">Cantidad</th>
                            <th style="width: 100px;">Unidad</th>
                            <th style="width: 130px;">Precio Unit.</th>
                            <th style="width: 100px;">IVA %</th>
                            <th style="width: 120px;" class="text-end">Total</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="lineasBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Totales</h5>
        </div>
        <div class="card-body">
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="fw-semibold">Subtotal:</td>
                            <td class="text-end" id="subtotal">₡0.00</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Impuesto:</td>
                            <td class="text-end" id="totalImpuesto">₡0.00</td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold">Total:</td>
                            <td class="text-end fw-bold" id="totalComprobante">₡0.00</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-paper-plane me-1"></i>Emitir
        </button>
        <a href="{{ route('comprobantes.index') }}" class="btn btn-secondary btn-lg ms-2">
            <i class="fas fa-times me-1"></i>Cancelar
        </a>
    </div>
</form>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- Modal Buscar CABYS                                     --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalCabys" tabindex="-1" aria-labelledby="modalCabysLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCabysLabel">
                    <i class="fas fa-search me-2"></i>Buscar Código CABYS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="cabysModalInput"
                           placeholder="Escriba el nombre del producto o servicio, ej: café, servicio consultoría..."
                           autocomplete="off">
                    <button class="btn btn-primary" type="button" id="cabysModalBtn">
                        Buscar
                    </button>
                </div>
                <div class="form-text mb-3">
                    Puede buscar por <strong>nombre</strong> (ej: "café tostado") o por <strong>código</strong> (ej: "2391101000000").
                    Fuente: <a href="https://api.hacienda.go.cr/" target="_blank">Ministerio de Hacienda C.R.</a>
                </div>

                <div id="cabysModalLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Consultando Hacienda...</div>
                </div>

                <div id="cabysModalEmpty" class="text-center py-4 text-muted d-none">
                    <i class="fas fa-inbox fa-3x mb-2 d-block"></i>
                    No se encontraron resultados. Intente con otro término.
                </div>

                <div id="cabysModalError" class="alert alert-danger d-none">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="cabysModalErrorText"></span>
                </div>

                <div id="cabysModalResults" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small" id="cabysModalCount"></span>
                    </div>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width:140px">Código</th>
                                    <th>Descripción</th>
                                    <th style="width:60px" class="text-center">IVA</th>
                                    <th style="width:90px" class="text-center">Seleccionar</th>
                                </tr>
                            </thead>
                            <tbody id="cabysModalTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="cabysModalInitial" class="text-center py-4 text-muted">
                    <i class="fas fa-barcode fa-3x mb-2 d-block opacity-50"></i>
                    Escriba un término y presione <strong>Buscar</strong> o <strong>Enter</strong>.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ─── Búsqueda de Receptor por Cédula ─────────────────

    var btnBuscar = document.getElementById('btn-buscar-receptor');
    var inputCedula = document.getElementById('receptor_numero_id');
    var inputNombre = document.getElementById('receptor_nombre');
    var selectTipo = document.getElementById('receptor_tipo_id');
    var infoDiv = document.getElementById('receptor-info');
    var alertDiv = document.getElementById('receptor-alert');
    var alertText = document.getElementById('receptor-alert-text');
    var errorDiv = document.getElementById('receptor-error');
    var errorText = document.getElementById('receptor-error-text');

    function showReceptorAlert(msg) {
        alertText.textContent = msg;
        alertDiv.classList.remove('d-none');
        alertDiv.classList.add('show');
    }
    function showReceptorError(msg) {
        errorText.textContent = msg;
        errorDiv.classList.remove('d-none');
        errorDiv.classList.add('show');
    }
    function hideReceptorAlerts() {
        alertDiv.classList.add('d-none'); alertDiv.classList.remove('show');
        errorDiv.classList.add('d-none'); errorDiv.classList.remove('show');
    }

    function buscarReceptor() {
        var cedula = inputCedula.value.trim().replace(/[^0-9]/g, '');
        if (cedula.length < 9) { showReceptorError('Ingrese una cédula válida (mínimo 9 dígitos).'); return; }
        hideReceptorAlerts();
        btnBuscar.disabled = true;
        btnBuscar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch('{{ route("empresas.lookup-cedula") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ cedula: cedula }),
        })
        .then(function(res) { return res.json().then(function(data) { return { status: res.status, body: data }; }); })
        .then(function(result) {
            btnBuscar.disabled = false;
            btnBuscar.innerHTML = '<i class="fas fa-search"></i>';
            if (!result.body.success) { showReceptorError(result.body.message || 'No se encontró.'); infoDiv.classList.add('d-none'); return; }
            var d = result.body.data;
            if (d.nombre) inputNombre.value = d.nombre;
            if (d.tipoIdentificacion) {
                var padded = d.tipoIdentificacion.toString().padStart(2, '0');
                for (var i = 0; i < selectTipo.options.length; i++) { if (selectTipo.options[i].value === padded) { selectTipo.value = padded; break; } }
            }
            document.getElementById('info-contribuyente').textContent = d.nombre || '—';
            document.getElementById('info-regimen').textContent = (d.regimen && d.regimen.descripcion) ? d.regimen.descripcion : (d.regimen || '—');
            document.getElementById('info-actividad').textContent = (d.actividades && d.actividades.length > 0) ? d.actividades[0].codigo + ' - ' + d.actividades[0].descripcion : '—';
            infoDiv.classList.remove('d-none');
            showReceptorAlert('Datos del receptor obtenidos de Hacienda: ' + d.nombre);
        })
        .catch(function() {
            btnBuscar.disabled = false;
            btnBuscar.innerHTML = '<i class="fas fa-search"></i>';
            showReceptorError('Error de conexión con Hacienda. Ingrese los datos manualmente.');
        });
    }
    btnBuscar.addEventListener('click', buscarReceptor);
    inputCedula.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); buscarReceptor(); } });

    // ─── Modal CABYS ──────────────────────────────────────

    var cabysUrl = '{{ route("comprobantes.buscar-cabys") }}';
    var cabysModal = new bootstrap.Modal(document.getElementById('modalCabys'));
    var cabysInput = document.getElementById('cabysModalInput');
    var cabysBtn = document.getElementById('cabysModalBtn');
    var cabysTbody = document.getElementById('cabysModalTableBody');
    var cabysLoading = document.getElementById('cabysModalLoading');
    var cabysEmpty = document.getElementById('cabysModalEmpty');
    var cabysError = document.getElementById('cabysModalError');
    var cabysErrorText = document.getElementById('cabysModalErrorText');
    var cabysResults = document.getElementById('cabysModalResults');
    var cabysCount = document.getElementById('cabysModalCount');
    var cabysInitial = document.getElementById('cabysModalInitial');
    var currentCabysLineaIndex = null;

    function cabysResetModal() {
        cabysLoading.classList.add('d-none');
        cabysEmpty.classList.add('d-none');
        cabysError.classList.add('d-none');
        cabysResults.classList.add('d-none');
        cabysInitial.classList.remove('d-none');
        cabysTbody.innerHTML = '';
        cabysInput.value = '';
    }

    function openCabysModal(lineaIdx) {
        currentCabysLineaIndex = lineaIdx;
        cabysResetModal();
        cabysModal.show();
        setTimeout(function() { cabysInput.focus(); }, 300);
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function buscarCabys() {
        var query = cabysInput.value.trim();
        if (query.length < 2) return;

        cabysInitial.classList.add('d-none');
        cabysResults.classList.add('d-none');
        cabysEmpty.classList.add('d-none');
        cabysError.classList.add('d-none');
        cabysLoading.classList.remove('d-none');
        cabysBtn.disabled = true;
        cabysBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(cabysUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ q: query }),
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            cabysLoading.classList.add('d-none');
            cabysBtn.disabled = false;
            cabysBtn.innerHTML = 'Buscar';

            if (!data.success || !data.results || data.results.length === 0) {
                cabysEmpty.classList.remove('d-none');
                return;
            }

            cabysTbody.innerHTML = '';
            data.results.forEach(function(item) {
                var tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                var ivaBadge = item.impuesto === 0
                    ? '<span class="badge bg-success">Exento</span>'
                    : '<span class="badge bg-primary">' + item.impuesto + '%</span>';
                tr.innerHTML =
                    '<td><code class="small">' + escapeHtml(item.codigo) + '</code></td>' +
                    '<td class="small">' + escapeHtml(item.descripcion) +
                        (item.categoria ? '<br><span class="text-muted" style="font-size:0.75rem">' + escapeHtml(item.categoria) + '</span>' : '') +
                    '</td>' +
                    '<td class="text-center">' + ivaBadge + '</td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-primary btn-cabys-select">Usar</button></td>';

                tr.querySelector('.btn-cabys-select').addEventListener('click', function() {
                    seleccionarCabys(item);
                });
                tr.addEventListener('dblclick', function() {
                    seleccionarCabys(item);
                });
                cabysTbody.appendChild(tr);
            });

            var totalText = data.total > data.results.length
                ? 'Mostrando ' + data.results.length + ' de ' + data.total + ' resultados'
                : data.results.length + ' resultado' + (data.results.length !== 1 ? 's' : '');
            cabysCount.textContent = totalText;
            cabysResults.classList.remove('d-none');
        })
        .catch(function() {
            cabysLoading.classList.add('d-none');
            cabysBtn.disabled = false;
            cabysBtn.innerHTML = 'Buscar';
            cabysErrorText.textContent = 'No se pudo conectar con el API de Hacienda. Intente de nuevo.';
            cabysError.classList.remove('d-none');
        });
    }

    function seleccionarCabys(item) {
        if (currentCabysLineaIndex === null) return;
        var tr = document.querySelector('tr[data-linea="' + currentCabysLineaIndex + '"]');
        if (!tr) return;

        tr.querySelector('.cabys-code-hidden').value = item.codigo;
        tr.querySelector('.cabys-display').value = item.codigo;
        tr.querySelector('.cabys-desc').textContent = item.descripcion;
        tr.querySelector('.cabys-desc').title = item.descripcion;

        var detalle = tr.querySelector('.linea-detalle');
        if (!detalle.value) detalle.value = item.descripcion;

        var ivaSelect = tr.querySelector('.linea-iva');
        for (var o = 0; o < ivaSelect.options.length; o++) {
            if (parseInt(ivaSelect.options[o].value) === item.impuesto) {
                ivaSelect.value = ivaSelect.options[o].value;
                break;
            }
        }
        calcularLinea(tr);
        cabysModal.hide();
    }

    cabysBtn.addEventListener('click', buscarCabys);
    cabysInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); buscarCabys(); } });

    // ─── Líneas de Detalle ────────────────────────────────

    var lineaIndex = 0;

    function agregarLinea() {
        var tbody = document.getElementById('lineasBody');
        var tr = document.createElement('tr');
        var idx = lineaIndex;
        tr.setAttribute('data-linea', idx);
        tr.innerHTML =
            '<td>' +
                '<input type="hidden" class="cabys-code-hidden" name="lineas[' + idx + '][codigo_cabys]">' +
                '<div class="input-group input-group-sm">' +
                    '<input type="text" class="form-control cabys-display" readonly placeholder="—" style="background:#f8f9fa;font-size:0.8rem">' +
                    '<button type="button" class="btn btn-outline-primary btn-cabys-open" title="Buscar CABYS">' +
                        '<i class="fas fa-search"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="cabys-desc text-muted text-truncate" style="font-size:0.72rem;max-width:185px;margin-top:2px" title=""></div>' +
            '</td>' +
            '<td><input type="text" class="form-control form-control-sm linea-detalle" name="lineas[' + idx + '][detalle]" placeholder="Descripción del producto o servicio" required></td>' +
            '<td><input type="number" class="form-control form-control-sm linea-cantidad" name="lineas[' + idx + '][cantidad]" value="1" min="0.01" step="0.01" required></td>' +
            '<td>' +
                '<select class="form-select form-select-sm" name="lineas[' + idx + '][unidad]">' +
                    '<option value="Unid">Unid</option>' +
                    '<option value="Sp">Sp (Servicio Profesional)</option>' +
                    '<option value="m">m (Metro)</option>' +
                    '<option value="kg">kg (Kilogramo)</option>' +
                    '<option value="s">s (Segundo)</option>' +
                    '<option value="l">l (Litro)</option>' +
                    '<option value="cm">cm (Centímetro)</option>' +
                    '<option value="g">g (Gramo)</option>' +
                    '<option value="km">km (Kilómetro)</option>' +
                    '<option value="ln">ln (Pulgada)</option>' +
                    '<option value="m2">m² (Metro cuadrado)</option>' +
                    '<option value="m3">m³ (Metro cúbico)</option>' +
                    '<option value="ml">ml (Mililitro)</option>' +
                    '<option value="mm">mm (Milímetro)</option>' +
                    '<option value="oz">oz (Onza)</option>' +
                    '<option value="d">d (Día)</option>' +
                    '<option value="h">h (Hora)</option>' +
                    '<option value="min">min (Minuto)</option>' +
                    '<option value="Kw">Kw (Kilovatio)</option>' +
                    '<option value="Kwh">Kwh (Kilovatio hora)</option>' +
                    '<option value="Os">Otros</option>' +
                '</select>' +
            '</td>' +
            '<td><input type="number" class="form-control form-control-sm linea-precio" name="lineas[' + idx + '][precio_unitario]" value="0" min="0" step="0.01" required></td>' +
            '<td>' +
                '<select class="form-select form-select-sm linea-iva" name="lineas[' + idx + '][tarifa_iva]">' +
                    '<option value="0">0%</option>' +
                    '<option value="1">1%</option>' +
                    '<option value="2">2%</option>' +
                    '<option value="4">4%</option>' +
                    '<option value="8">8%</option>' +
                    '<option value="13" selected>13%</option>' +
                '</select>' +
            '</td>' +
            '<td class="text-end align-middle linea-total fw-semibold">₡0.00</td>' +
            '<td class="text-center align-middle">' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-linea" title="Eliminar">' +
                    '<i class="fas fa-trash"></i>' +
                '</button>' +
            '</td>';
        tbody.appendChild(tr);
        lineaIndex++;

        tr.querySelector('.btn-cabys-open').addEventListener('click', function() {
            openCabysModal(idx);
        });
        bindLineaEvents(tr);
    }

    function bindLineaEvents(tr) {
        var inputs = tr.querySelectorAll('.linea-cantidad, .linea-precio, .linea-iva');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() { calcularLinea(tr); });
            input.addEventListener('change', function() { calcularLinea(tr); });
        });
        tr.querySelector('.btn-eliminar-linea').addEventListener('click', function() {
            tr.remove();
            calcularTotales();
        });
    }

    function calcularLinea(tr) {
        var cantidad = parseFloat(tr.querySelector('.linea-cantidad').value) || 0;
        var precio = parseFloat(tr.querySelector('.linea-precio').value) || 0;
        var iva = parseFloat(tr.querySelector('.linea-iva').value) || 0;
        var subtotal = cantidad * precio;
        var impuesto = subtotal * (iva / 100);
        tr.querySelector('.linea-total').textContent = '₡' + (subtotal + impuesto).toFixed(2);
        calcularTotales();
    }

    function calcularTotales() {
        var subtotal = 0, totalImpuesto = 0;
        document.querySelectorAll('#lineasBody tr').forEach(function(tr) {
            var cantidad = parseFloat(tr.querySelector('.linea-cantidad').value) || 0;
            var precio = parseFloat(tr.querySelector('.linea-precio').value) || 0;
            var iva = parseFloat(tr.querySelector('.linea-iva').value) || 0;
            var s = cantidad * precio;
            subtotal += s;
            totalImpuesto += s * (iva / 100);
        });
        document.getElementById('subtotal').textContent = '₡' + subtotal.toFixed(2);
        document.getElementById('totalImpuesto').textContent = '₡' + totalImpuesto.toFixed(2);
        document.getElementById('totalComprobante').textContent = '₡' + (subtotal + totalImpuesto).toFixed(2);
    }

    document.getElementById('btnAgregarLinea').addEventListener('click', agregarLinea);

    // ─── Cargar líneas de referencia si existen ──────────
    @if(isset($refComprobante) && $refComprobante->lineas && $refComprobante->lineas->count() > 0)
    var refLineas = @json($refComprobante->lineas->map(fn($l) => [
        'codigo_cabys' => $l->Codigo ?? '',
        'detalle' => $l->Detalle ?? '',
        'cantidad' => (float) $l->Cantidad,
        'unidad' => $l->UnidadMedida ?? 'Unid',
        'precio_unitario' => (float) $l->PrecioUnitario,
        'tarifa_iva' => (float) ($l->Impuesto_Tarifa ?? 0),
    ]));
    refLineas.forEach(function(ref) {
        agregarLinea();
        var tr = document.querySelector('tr[data-linea="' + (lineaIndex - 1) + '"]');
        if (!tr) return;
        if (ref.codigo_cabys) {
            tr.querySelector('.cabys-code-hidden').value = ref.codigo_cabys;
            tr.querySelector('.cabys-display').value = ref.codigo_cabys;
        }
        tr.querySelector('.linea-detalle').value = ref.detalle;
        tr.querySelector('.linea-cantidad').value = ref.cantidad;
        tr.querySelector('.linea-precio').value = ref.precio_unitario;
        var unidadSelect = tr.querySelector('select[name$="[unidad]"]');
        for (var o = 0; o < unidadSelect.options.length; o++) {
            if (unidadSelect.options[o].value === ref.unidad) { unidadSelect.value = ref.unidad; break; }
        }
        var ivaSelect = tr.querySelector('.linea-iva');
        var ivaVal = String(Math.round(ref.tarifa_iva));
        for (var o = 0; o < ivaSelect.options.length; o++) {
            if (ivaSelect.options[o].value === ivaVal) { ivaSelect.value = ivaVal; break; }
        }
        calcularLinea(tr);
    });
    @else
    agregarLinea();
    @endif

    // ─── Mostrar/ocultar InformacionReferencia ────────────
    var tipoDocSelect = document.getElementById('tipo_documento');
    var cardRef = document.getElementById('card-referencia');
    function toggleReferencia() {
        var v = tipoDocSelect.value;
        if (v === '02' || v === '03') {
            cardRef.classList.remove('d-none');
            cardRef.querySelectorAll('input, select').forEach(function(el) { el.required = true; });
        } else {
            cardRef.classList.add('d-none');
            cardRef.querySelectorAll('input, select').forEach(function(el) { el.required = false; });
        }
    }
    tipoDocSelect.addEventListener('change', toggleReferencia);
    toggleReferencia();
});
</script>
@endpush
