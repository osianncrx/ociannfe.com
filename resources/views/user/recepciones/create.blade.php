@extends('layouts.app')

@section('title', 'Recepcionar Documentos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-file-import me-2"></i>Recepcionar Documentos</h2>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="fas fa-exclamation-triangle me-2"></i>Errores:</strong>
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form action="{{ route('recepciones.store-masivo') }}" method="POST" enctype="multipart/form-data" id="formRecepcion">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-upload me-2"></i>1. Cargar archivos XML</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="id_empresa" class="form-label">Empresa Receptora</label>
                    <select class="form-select @error('id_empresa') is-invalid @enderror" id="id_empresa" name="id_empresa" required>
                        <option value="">Seleccione...</option>
                        @foreach($empresas ?? [] as $empresa)
                            <option value="{{ $empresa->id_empresa }}"
                                    data-actividad="{{ $empresa->CodigoActividad ?? '' }}"
                                    {{ old('id_empresa') == $empresa->id_empresa ? 'selected' : '' }}>
                                {{ $empresa->Nombre }} ({{ $empresa->cedula }})
                            </option>
                        @endforeach
                    </select>
                    @error('id_empresa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label for="archivos_xml" class="form-label">Archivos XML <span class="text-muted">(hasta 100)</span></label>
                    <input type="file" class="form-control @error('archivos_xml') is-invalid @enderror"
                           id="archivos_xml" name="archivos_xml[]" accept=".xml,text/xml,application/xml" multiple required>
                    <div class="form-text">Seleccione uno o varios archivos XML de comprobantes electrónicos recibidos.</div>
                    @error('archivos_xml')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary w-100" id="btnParsear">
                        <i class="fas fa-eye me-1"></i>Previsualizar
                    </button>
                </div>
            </div>
            <div id="file-count-info" class="mt-2 d-none">
                <span class="badge bg-info fs-6" id="file-count-badge"></span>
            </div>
        </div>
    </div>

    <div id="parsear-loading" class="text-center py-4 d-none">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2 text-muted">Analizando archivos XML... <span id="parsear-progress"></span></p>
    </div>
    <div id="parsear-error" class="alert alert-danger d-none">
        <i class="fas fa-exclamation-triangle me-2"></i><span id="parsear-error-text"></span>
    </div>

    <div class="alert alert-warning d-none" id="alerta-no-recepcionables">
        <i class="fas fa-exclamation-triangle me-2"></i><span></span>
    </div>

    <div class="card border-0 shadow-sm mb-4 d-none" id="card-preview">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>2. Documentos encontrados</h5>
            <div>
                <span class="badge bg-success me-1" id="badge-validos">0 válidos</span>
                <span class="badge bg-warning text-dark me-1 d-none" id="badge-no-recepcionables">0 no recepcionables</span>
                <span class="badge bg-danger" id="badge-errores">0 con error</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width:30px">
                                <input type="checkbox" class="form-check-input" id="checkAll" checked title="Seleccionar/Deseleccionar todos">
                            </th>
                            <th>#</th>
                            <th>Archivo</th>
                            <th>Tipo</th>
                            <th>Emisor</th>
                            <th>Cédula Emisor</th>
                            <th>Fecha</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-preview"></tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <strong>Totales:</strong>
                    <span class="ms-2" id="resumen-totales"></span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted" id="resumen-seleccionados"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 d-none" id="card-respuesta">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-reply me-2"></i>3. Respuesta (Mensaje Receptor)</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="respuesta_tipo" class="form-label">Acción (se aplica a todos)</label>
                    <select class="form-select @error('respuesta_tipo') is-invalid @enderror" id="respuesta_tipo" name="respuesta_tipo" required>
                        <option value="05" {{ old('respuesta_tipo', '05') == '05' ? 'selected' : '' }}>05 - Aceptación Total</option>
                        <option value="06" {{ old('respuesta_tipo') == '06' ? 'selected' : '' }}>06 - Aceptación Parcial</option>
                        <option value="07" {{ old('respuesta_tipo') == '07' ? 'selected' : '' }}>07 - Rechazo</option>
                    </select>
                    @error('respuesta_tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="actividad_economica" class="form-label">Actividad Económica</label>
                    <input type="text" class="form-control @error('actividad_economica') is-invalid @enderror"
                           id="actividad_economica" name="actividad_economica"
                           value="{{ old('actividad_economica') }}" placeholder="Ej: 620100" maxlength="6">
                    <div class="form-text">Código de actividad del receptor.</div>
                    @error('actividad_economica')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label for="detalle_mensaje" class="form-label">Detalle / Mensaje</label>
                    <input type="text" class="form-control @error('detalle_mensaje') is-invalid @enderror"
                           id="detalle_mensaje" name="detalle_mensaje"
                           value="{{ old('detalle_mensaje', 'Aceptado') }}" maxlength="160">
                    @error('detalle_mensaje')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4 d-none" id="btn-submit-area">
        <button type="button" class="btn btn-primary btn-lg" id="btnEnviar">
            <i class="fas fa-paper-plane me-1"></i>Enviar Respuestas a Hacienda
        </button>
        <a href="{{ route('recepciones.index') }}" class="btn btn-secondary btn-lg ms-2">
            <i class="fas fa-times me-1"></i>Cancelar
        </a>
        <span class="ms-3 text-muted" id="submit-count-info"></span>
    </div>
</form>

<div id="submit-progress" class="d-none">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-cog fa-spin me-2"></i>Procesando documentos...</h5>
        </div>
        <div class="card-body">
            <div class="progress mb-3" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" id="progress-bar" style="width: 0%">0%</div>
            </div>
            <p class="text-muted mb-2" id="progress-text">Preparando envío...</p>
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Archivo</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody id="progress-results"></tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-none" id="progress-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-success fs-6 me-1" id="final-exitosos">0 exitosos</span>
                    <span class="badge bg-danger fs-6" id="final-errores">0 errores</span>
                </div>
                <a href="{{ route('recepciones.index') }}" class="btn btn-primary">
                    <i class="fas fa-list me-1"></i>Ver Recepciones
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var btnParsear = document.getElementById('btnParsear');
    var archivosInput = document.getElementById('archivos_xml');
    var cardPreview = document.getElementById('card-preview');
    var cardRespuesta = document.getElementById('card-respuesta');
    var btnSubmitArea = document.getElementById('btn-submit-area');
    var loadingDiv = document.getElementById('parsear-loading');
    var progressText = document.getElementById('parsear-progress');
    var errorDiv = document.getElementById('parsear-error');
    var errorText = document.getElementById('parsear-error-text');
    var fileCountInfo = document.getElementById('file-count-info');
    var fileCountBadge = document.getElementById('file-count-badge');
    var checkAll = document.getElementById('checkAll');
    var formRecepcion = document.getElementById('formRecepcion');

    var MAX_FILES = 100;
    var BATCH_SIZE = 10;
    var allResults = [];

    archivosInput.addEventListener('change', function () {
        var count = this.files.length;
        if (count > MAX_FILES) {
            alert('Puede seleccionar un máximo de ' + MAX_FILES + ' archivos. Se seleccionaron ' + count + '.');
            this.value = '';
            fileCountInfo.classList.add('d-none');
            return;
        }
        if (count > 0) {
            fileCountBadge.textContent = count + ' archivo' + (count > 1 ? 's' : '') + ' seleccionado' + (count > 1 ? 's' : '');
            fileCountInfo.classList.remove('d-none');
        } else {
            fileCountInfo.classList.add('d-none');
        }
        cardPreview.classList.add('d-none');
        cardRespuesta.classList.add('d-none');
        btnSubmitArea.classList.add('d-none');
    });

    btnParsear.addEventListener('click', function () {
        var files = archivosInput.files;
        if (!files || files.length === 0) {
            alert('Seleccione al menos un archivo XML.');
            return;
        }
        if (files.length > MAX_FILES) {
            alert('Máximo ' + MAX_FILES + ' archivos permitidos.');
            return;
        }

        errorDiv.classList.add('d-none');
        document.getElementById('alerta-no-recepcionables').classList.add('d-none');
        cardPreview.classList.add('d-none');
        cardRespuesta.classList.add('d-none');
        btnSubmitArea.classList.add('d-none');
        loadingDiv.classList.remove('d-none');

        allResults = [];
        var totalFiles = files.length;
        var batches = [];

        for (var i = 0; i < totalFiles; i += BATCH_SIZE) {
            batches.push(Array.from(files).slice(i, i + BATCH_SIZE));
        }

        var batchIndex = 0;
        var processedCount = 0;

        function processBatch() {
            if (batchIndex >= batches.length) {
                loadingDiv.classList.add('d-none');
                renderResults();
                return;
            }

            var batch = batches[batchIndex];
            var formData = new FormData();
            formData.append('_token', csrfToken);
            batch.forEach(function (file) {
                formData.append('archivos_xml[]', file);
            });

            progressText.textContent = '(' + processedCount + ' de ' + totalFiles + ')';

            fetch('{{ route("recepciones.parsear-xml-multiple") }}', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.resultados) {
                    var offset = batchIndex * BATCH_SIZE;
                    data.resultados.forEach(function (r) {
                        r.index = offset + r.index;
                        allResults.push(r);
                    });
                }
                processedCount += batch.length;
                batchIndex++;
                processBatch();
            })
            .catch(function () {
                batch.forEach(function (file, idx) {
                    allResults.push({
                        index: batchIndex * BATCH_SIZE + idx,
                        nombre: file.name,
                        success: false,
                        message: 'Error de conexión.'
                    });
                });
                processedCount += batch.length;
                batchIndex++;
                processBatch();
            });
        }

        processBatch();
    });

    function renderResults() {
        var tbody = document.getElementById('tabla-preview');
        tbody.innerHTML = '';

        var validos = 0;
        var erroresCount = 0;
        var totalGeneral = 0;

        allResults.sort(function (a, b) { return a.index - b.index; });

        var noRecepcionables = 0;
        allResults.forEach(function (r, i) {
            var tr = document.createElement('tr');
            if (r.success && r.recepcionable === false) {
                noRecepcionables++;
                tr.classList.add('table-warning');
                var total = parseFloat(r.total_comprobante) || 0;
                tr.innerHTML =
                    '<td></td>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td><small title="' + escapeHtml(r.nombre) + '">' + escapeHtml(truncate(r.nombre, 30)) + '</small></td>' +
                    '<td><span class="badge bg-warning text-dark">' + escapeHtml(r.tipo_documento || '') + '</span> <small>' + escapeHtml(r.tipo_documento_texto || '') + '</small></td>' +
                    '<td>' + escapeHtml(truncate(r.emisor_nombre || '', 25)) + '</td>' +
                    '<td><code>' + escapeHtml(r.emisor_id || '') + '</code></td>' +
                    '<td><small>' + escapeHtml(formatFecha(r.fecha_emision || '')) + '</small></td>' +
                    '<td class="text-end fw-semibold">' + (r.moneda_simbolo || '₡') + total.toFixed(2) + '</td>' +
                    '<td><span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>No recepcionable</span></td>';
            } else if (r.success) {
                validos++;
                var total = parseFloat(r.total_comprobante) || 0;
                totalGeneral += total;
                tr.innerHTML =
                    '<td><input type="checkbox" class="form-check-input file-check" data-idx="' + r.index + '" checked></td>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td><small title="' + escapeHtml(r.nombre) + '">' + escapeHtml(truncate(r.nombre, 30)) + '</small></td>' +
                    '<td><span class="badge bg-secondary">' + escapeHtml(r.tipo_documento || '') + '</span> <small>' + escapeHtml(r.tipo_documento_texto || '') + '</small></td>' +
                    '<td>' + escapeHtml(truncate(r.emisor_nombre || '', 25)) + '</td>' +
                    '<td><code>' + escapeHtml(r.emisor_id || '') + '</code></td>' +
                    '<td><small>' + escapeHtml(formatFecha(r.fecha_emision || '')) + '</small></td>' +
                    '<td class="text-end fw-semibold">' + (r.moneda_simbolo || '₡') + total.toFixed(2) + '</td>' +
                    '<td><span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span></td>';
            } else {
                erroresCount++;
                tr.classList.add('table-danger');
                tr.innerHTML =
                    '<td></td>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td><small title="' + escapeHtml(r.nombre) + '">' + escapeHtml(truncate(r.nombre, 30)) + '</small></td>' +
                    '<td colspan="5"><span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>' + escapeHtml(r.message || 'Error desconocido') + '</span></td>' +
                    '<td><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Error</span></td>';
            }
            tbody.appendChild(tr);
        });

        document.getElementById('badge-validos').textContent = validos + ' válido' + (validos !== 1 ? 's' : '');
        document.getElementById('badge-no-recepcionables').textContent = noRecepcionables + ' no recepcionable' + (noRecepcionables !== 1 ? 's' : '');
        document.getElementById('badge-no-recepcionables').classList.toggle('d-none', noRecepcionables === 0);
        document.getElementById('badge-errores').textContent = erroresCount + ' con error';
        document.getElementById('badge-errores').classList.toggle('d-none', erroresCount === 0);
        document.getElementById('resumen-totales').textContent = '₡' + totalGeneral.toFixed(2) + ' en ' + validos + ' documento' + (validos !== 1 ? 's' : '');
        updateSelectionCount();

        if (noRecepcionables > 0) {
            var alertDiv = document.getElementById('alerta-no-recepcionables');
            alertDiv.classList.remove('d-none');
            alertDiv.querySelector('span').textContent = noRecepcionables + ' documento' + (noRecepcionables !== 1 ? 's son' : ' es') +
                ' de tipo no recepcionable (ej: Tiquete Electrónico 04). Estos no se enviarán a Hacienda. El emisor debe emitir una Factura Electrónica (01) en su lugar.';
        }

        cardPreview.classList.remove('d-none');
        if (validos > 0) {
            cardRespuesta.classList.remove('d-none');
            btnSubmitArea.classList.remove('d-none');
        }
    }

    function updateSelectionCount() {
        var checks = document.querySelectorAll('.file-check:checked');
        var total = document.querySelectorAll('.file-check').length;
        document.getElementById('resumen-seleccionados').textContent = checks.length + ' de ' + total + ' seleccionados para enviar';
        document.getElementById('submit-count-info').textContent = 'Se enviarán ' + checks.length + ' documento' + (checks.length !== 1 ? 's' : '');
        document.getElementById('btnEnviar').disabled = checks.length === 0;
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('file-check')) {
            updateSelectionCount();
            var allChecks = document.querySelectorAll('.file-check');
            var allChecked = document.querySelectorAll('.file-check:checked');
            checkAll.checked = allChecks.length === allChecked.length;
            checkAll.indeterminate = allChecked.length > 0 && allChecked.length < allChecks.length;
        }
    });

    checkAll.addEventListener('change', function () {
        var checks = document.querySelectorAll('.file-check');
        var val = this.checked;
        checks.forEach(function (c) { c.checked = val; });
        updateSelectionCount();
    });

    document.getElementById('btnEnviar').addEventListener('click', function () {
        var checkedIndexes = [];
        document.querySelectorAll('.file-check:checked').forEach(function (c) {
            checkedIndexes.push(parseInt(c.dataset.idx));
        });

        if (checkedIndexes.length === 0) {
            alert('Seleccione al menos un documento para enviar.');
            return;
        }

        var idEmpresa = document.getElementById('id_empresa').value;
        if (!idEmpresa) { alert('Seleccione una empresa.'); return; }

        var respuestaTipo = document.getElementById('respuesta_tipo').value;
        var actividadEconomica = document.getElementById('actividad_economica').value;
        var detalleMensaje = document.getElementById('detalle_mensaje').value;

        var selectedFiles = [];
        var files = archivosInput.files;
        for (var i = 0; i < files.length; i++) {
            if (checkedIndexes.indexOf(i) !== -1) {
                selectedFiles.push(files[i]);
            }
        }

        formRecepcion.classList.add('d-none');
        cardPreview.classList.add('d-none');
        cardRespuesta.classList.add('d-none');
        btnSubmitArea.classList.add('d-none');

        var submitProgress = document.getElementById('submit-progress');
        var progressBar = document.getElementById('progress-bar');
        var progressTextEl = document.getElementById('progress-text');
        var progressResults = document.getElementById('progress-results');
        var progressFooter = document.getElementById('progress-footer');
        submitProgress.classList.remove('d-none');

        var totalFiles = selectedFiles.length;
        var sendBatchSize = 5;
        var batches = [];
        for (var i = 0; i < totalFiles; i += sendBatchSize) {
            batches.push(selectedFiles.slice(i, i + sendBatchSize));
        }

        var processedCount = 0;
        var exitosos = 0;
        var erroresCount = 0;
        var batchIdx = 0;

        function sendBatch() {
            if (batchIdx >= batches.length) {
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add(erroresCount > 0 ? 'bg-warning' : 'bg-success');
                progressTextEl.innerHTML = '<strong>Proceso completado.</strong> ' + exitosos + ' exitosos, ' + erroresCount + ' con error.';
                document.getElementById('final-exitosos').textContent = exitosos + ' exitoso' + (exitosos !== 1 ? 's' : '');
                document.getElementById('final-errores').textContent = erroresCount + ' error' + (erroresCount !== 1 ? 'es' : '');
                document.getElementById('final-errores').classList.toggle('d-none', erroresCount === 0);
                progressFooter.classList.remove('d-none');
                return;
            }

            var batch = batches[batchIdx];
            progressTextEl.textContent = 'Enviando lote ' + (batchIdx + 1) + ' de ' + batches.length + ' (' + processedCount + '/' + totalFiles + ' procesados)...';

            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('id_empresa', idEmpresa);
            formData.append('respuesta_tipo', respuestaTipo);
            formData.append('actividad_economica', actividadEconomica);
            formData.append('detalle_mensaje', detalleMensaje);
            batch.forEach(function (file) {
                formData.append('archivos_xml[]', file);
            });

            fetch('{{ route("recepciones.store-masivo") }}', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.resultados) {
                    data.resultados.forEach(function (r) {
                        processedCount++;
                        var tr = document.createElement('tr');
                        if (r.success) {
                            exitosos++;
                            tr.innerHTML = '<td>' + escapeHtml(r.nombre) + '</td><td><span class="badge bg-success"><i class="fas fa-check me-1"></i>Enviado</span></td>';
                        } else {
                            erroresCount++;
                            tr.classList.add('table-danger');
                            tr.innerHTML = '<td>' + escapeHtml(r.nombre) + '</td><td><span class="text-danger"><i class="fas fa-times me-1"></i>' + escapeHtml(r.message || 'Error') + '</span></td>';
                        }
                        progressResults.appendChild(tr);
                    });
                }
                var pct = Math.round((processedCount / totalFiles) * 100);
                progressBar.style.width = pct + '%';
                progressBar.textContent = pct + '%';
                batchIdx++;
                sendBatch();
            })
            .catch(function () {
                batch.forEach(function (file) {
                    processedCount++;
                    erroresCount++;
                    var tr = document.createElement('tr');
                    tr.classList.add('table-danger');
                    tr.innerHTML = '<td>' + escapeHtml(file.name) + '</td><td><span class="text-danger"><i class="fas fa-times me-1"></i>Error de conexión</span></td>';
                    progressResults.appendChild(tr);
                });
                var pct = Math.round((processedCount / totalFiles) * 100);
                progressBar.style.width = pct + '%';
                progressBar.textContent = pct + '%';
                batchIdx++;
                sendBatch();
            });
        }

        sendBatch();
    });

    var selectEmpresa = document.getElementById('id_empresa');
    var actividadInput = document.getElementById('actividad_economica');
    selectEmpresa.addEventListener('change', function () {
        var selected = this.options[this.selectedIndex];
        if (selected && selected.dataset.actividad) {
            actividadInput.value = selected.dataset.actividad;
        }
    });
    if (selectEmpresa.value) {
        var initialOption = selectEmpresa.options[selectEmpresa.selectedIndex];
        if (initialOption && initialOption.dataset.actividad && !actividadInput.value) {
            actividadInput.value = initialOption.dataset.actividad;
        }
    }

    var selectRespuesta = document.getElementById('respuesta_tipo');
    var detalleMensaje = document.getElementById('detalle_mensaje');
    selectRespuesta.addEventListener('change', function () {
        if (this.value === '05') detalleMensaje.value = 'Aceptado';
        else if (this.value === '06') detalleMensaje.value = 'Aceptado parcialmente';
        else if (this.value === '07') detalleMensaje.value = 'Rechazado';
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function truncate(str, len) {
        return str.length > len ? str.substring(0, len) + '...' : str;
    }

    function formatFecha(fecha) {
        if (!fecha) return '—';
        try {
            var d = new Date(fecha);
            if (isNaN(d.getTime())) return fecha;
            return d.toLocaleDateString('es-CR');
        } catch (e) { return fecha; }
    }
});
</script>
@endpush
