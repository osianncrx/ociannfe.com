@extends('layouts.app')

@section('title', 'Nueva Empresa')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-building me-2"></i>Nueva Empresa</h2>
</div>

<div id="lookup-alert" class="alert alert-info alert-dismissible fade d-none" role="alert">
    <i class="fas fa-info-circle me-2"></i><span id="lookup-alert-text"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>

<div id="lookup-error" class="alert alert-warning alert-dismissible fade d-none" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><span id="lookup-error-text"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('empresas.store') }}" method="POST" enctype="multipart/form-data" id="form-empresa">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="Tipo" class="form-label">Tipo Identificación</label>
                    <select class="form-select @error('Tipo') is-invalid @enderror" id="Tipo" name="Tipo" required>
                        <option value="">Seleccione...</option>
                        <option value="01" {{ old('Tipo') == '01' ? 'selected' : '' }}>01 - Física</option>
                        <option value="02" {{ old('Tipo') == '02' ? 'selected' : '' }}>02 - Jurídica</option>
                        <option value="03" {{ old('Tipo') == '03' ? 'selected' : '' }}>03 - DIMEX</option>
                        <option value="04" {{ old('Tipo') == '04' ? 'selected' : '' }}>04 - NITE</option>
                    </select>
                    @error('Tipo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-5">
                    <label for="cedula" class="form-label">Cédula</label>
                    <div class="input-group">
                        <input type="text" class="form-control @error('cedula') is-invalid @enderror" id="cedula" name="cedula" value="{{ old('cedula') }}" required placeholder="Ej: 3101234567">
                        <button type="button" class="btn btn-outline-primary" id="btn-lookup" title="Consultar en Hacienda">
                            <i class="fas fa-search me-1"></i>Consultar
                        </button>
                        @error('cedula')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">Ingrese la cédula y presione "Consultar" para obtener datos de Hacienda.</div>
                </div>

                <div class="col-md-3">
                    <label for="Numero" class="form-label">Número</label>
                    <input type="text" class="form-control @error('Numero') is-invalid @enderror" id="Numero" name="Numero" value="{{ old('Numero') }}">
                    @error('Numero')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="Nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control @error('Nombre') is-invalid @enderror" id="Nombre" name="Nombre" value="{{ old('Nombre') }}" required>
                    @error('Nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="NombreComercial" class="form-label">Nombre Comercial</label>
                    <input type="text" class="form-control @error('NombreComercial') is-invalid @enderror" id="NombreComercial" name="NombreComercial" value="{{ old('NombreComercial') }}">
                    @error('NombreComercial')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="id_ambiente" class="form-label">Ambiente</label>
                    <select class="form-select @error('id_ambiente') is-invalid @enderror" id="id_ambiente" name="id_ambiente" required>
                        <option value="">Seleccione...</option>
                        @foreach($ambientes as $ambiente)
                            <option value="{{ $ambiente->id_ambiente }}" {{ old('id_ambiente') == $ambiente->id_ambiente ? 'selected' : '' }}>
                                {{ $ambiente->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_ambiente')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="CorreoElectronico" class="form-label">Email</label>
                    <input type="email" class="form-control @error('CorreoElectronico') is-invalid @enderror" id="CorreoElectronico" name="CorreoElectronico" value="{{ old('CorreoElectronico') }}">
                    @error('CorreoElectronico')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="sucursal_info" class="form-label">Sucursal</label>
                    <input type="text" class="form-control" id="sucursal_info" value="Se asignará automáticamente" readonly disabled>
                    <div class="form-text">Se auto-asigna según cédula y ambiente.</div>
                </div>

                <div class="col-md-6">
                    <label for="CodigoActividad" class="form-label">Código Actividad</label>
                    <select class="form-select @error('CodigoActividad') is-invalid @enderror" id="CodigoActividad" name="CodigoActividad">
                        <option value="">Seleccione o ingrese manualmente...</option>
                    </select>
                    @error('CodigoActividad')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6" id="actividad-manual-wrapper" style="display: none;">
                    <label for="CodigoActividadManual" class="form-label">Código Actividad (manual)</label>
                    <input type="text" class="form-control" id="CodigoActividadManual" placeholder="Ej: 620100">
                    <div class="form-text">Si no aparece en la lista, ingrese el código manualmente.</div>
                </div>

                <div class="col-12" id="situacion-info" style="display: none;">
                    <div class="alert alert-light border mb-0">
                        <h6 class="mb-2"><i class="fas fa-clipboard-check me-1"></i>Situación del Contribuyente</h6>
                        <div class="row">
                            <div class="col-md-3"><strong>Estado:</strong> <span id="info-estado">-</span></div>
                            <div class="col-md-3"><strong>Moroso:</strong> <span id="info-moroso">-</span></div>
                            <div class="col-md-3"><strong>Omiso:</strong> <span id="info-omiso">-</span></div>
                            <div class="col-md-3"><strong>Administración:</strong> <span id="info-admin">-</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <hr>
                    <h5><i class="fas fa-key me-2"></i>Credenciales Ministerio de Hacienda</h5>
                </div>

                <div class="col-md-4">
                    <label for="usuario_mh" class="form-label">Usuario MH</label>
                    <input type="text" class="form-control @error('usuario_mh') is-invalid @enderror" id="usuario_mh" name="usuario_mh" value="{{ old('usuario_mh') }}" required>
                    @error('usuario_mh')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="contra_mh" class="form-label">Contraseña MH</label>
                    <input type="password" class="form-control @error('contra_mh') is-invalid @enderror" id="contra_mh" name="contra_mh" required>
                    @error('contra_mh')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="pin_llave" class="form-label">PIN Llave</label>
                    <input type="password" class="form-control @error('pin_llave') is-invalid @enderror" id="pin_llave" name="pin_llave" required>
                    @error('pin_llave')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="llave_criptografica" class="form-label">Certificado .p12</label>
                    <input type="file" class="form-control @error('llave_criptografica') is-invalid @enderror" id="llave_criptografica" name="llave_criptografica" accept=".p12" required>
                    @error('llave_criptografica')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <hr>
                    <h5><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h5>
                </div>

                <div class="col-md-4">
                    <label for="Provincia" class="form-label">Provincia</label>
                    <input type="text" class="form-control @error('Provincia') is-invalid @enderror" id="Provincia" name="Provincia" value="{{ old('Provincia') }}">
                    @error('Provincia')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="Canton" class="form-label">Cantón</label>
                    <input type="text" class="form-control @error('Canton') is-invalid @enderror" id="Canton" name="Canton" value="{{ old('Canton') }}">
                    @error('Canton')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="Distrito" class="form-label">Distrito</label>
                    <input type="text" class="form-control @error('Distrito') is-invalid @enderror" id="Distrito" name="Distrito" value="{{ old('Distrito') }}">
                    @error('Distrito')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="OtrasSenas" class="form-label">Otras Señas</label>
                    <textarea class="form-control @error('OtrasSenas') is-invalid @enderror" id="OtrasSenas" name="OtrasSenas" rows="2">{{ old('OtrasSenas') }}</textarea>
                    @error('OtrasSenas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar
                </button>
                <a href="{{ route('empresas.index') }}" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnLookup = document.getElementById('btn-lookup');
    const cedulaInput = document.getElementById('cedula');
    const tipoSelect = document.getElementById('Tipo');
    const nombreInput = document.getElementById('Nombre');
    const numeroInput = document.getElementById('Numero');
    const actividadSelect = document.getElementById('CodigoActividad');
    const manualWrapper = document.getElementById('actividad-manual-wrapper');
    const manualInput = document.getElementById('CodigoActividadManual');
    const situacionDiv = document.getElementById('situacion-info');
    const alertDiv = document.getElementById('lookup-alert');
    const alertText = document.getElementById('lookup-alert-text');
    const errorDiv = document.getElementById('lookup-error');
    const errorText = document.getElementById('lookup-error-text');

    function showAlert(msg) {
        alertText.textContent = msg;
        alertDiv.classList.remove('d-none');
        alertDiv.classList.add('show');
    }

    function showError(msg) {
        errorText.textContent = msg;
        errorDiv.classList.remove('d-none');
        errorDiv.classList.add('show');
    }

    function hideAlerts() {
        alertDiv.classList.add('d-none');
        alertDiv.classList.remove('show');
        errorDiv.classList.add('d-none');
        errorDiv.classList.remove('show');
    }

    const tipoMap = {
        '01': '01',
        '02': '02',
        '03': '03',
        '04': '04',
    };

    btnLookup.addEventListener('click', function() {
        const cedula = cedulaInput.value.trim().replace(/[^0-9]/g, '');

        if (cedula.length < 9) {
            showError('Ingrese una cédula válida (mínimo 9 dígitos).');
            return;
        }

        hideAlerts();
        btnLookup.disabled = true;
        btnLookup.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Consultando...';

        fetch('{{ route("empresas.lookup-cedula") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ cedula: cedula }),
        })
        .then(function(res) { return res.json().then(function(data) { return { status: res.status, body: data }; }); })
        .then(function(result) {
            btnLookup.disabled = false;
            btnLookup.innerHTML = '<i class="fas fa-search me-1"></i>Consultar';

            if (!result.body.success) {
                showError(result.body.message || 'No se encontraron datos.');
                return;
            }

            const d = result.body.data;

            if (d.nombre) {
                nombreInput.value = d.nombre;
            }

            if (d.tipoIdentificacion && tipoMap[d.tipoIdentificacion]) {
                tipoSelect.value = tipoMap[d.tipoIdentificacion];
            }

            numeroInput.value = cedula;

            // Actividades
            while (actividadSelect.options.length > 1) {
                actividadSelect.remove(1);
            }

            if (d.actividades && d.actividades.length > 0) {
                d.actividades.forEach(function(act, idx) {
                    var opt = document.createElement('option');
                    opt.value = act.codigo;
                    opt.textContent = act.codigo + ' - ' + act.descripcion;
                    if (idx === 0) opt.selected = true;
                    actividadSelect.appendChild(opt);
                });
                manualWrapper.style.display = 'none';
            } else {
                manualWrapper.style.display = '';
            }

            // Situación
            if (d.situacion) {
                situacionDiv.style.display = '';
                document.getElementById('info-estado').textContent = d.situacion.estado || '-';
                document.getElementById('info-moroso').textContent = d.situacion.morpiuado || d.situacion.moroso || '-';
                document.getElementById('info-omiso').textContent = d.situacion.omiso || '-';
                document.getElementById('info-admin').textContent = d.situacion.administracionTributaria || '-';
            }

            showAlert('Datos obtenidos de Hacienda exitosamente para: ' + d.nombre);
        })
        .catch(function(err) {
            btnLookup.disabled = false;
            btnLookup.innerHTML = '<i class="fas fa-search me-1"></i>Consultar';
            showError('Error de conexión. Puede ingresar los datos manualmente.');
        });
    });

    cedulaInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnLookup.click();
        }
    });

    actividadSelect.addEventListener('change', function() {
        if (this.value === '') {
            manualWrapper.style.display = '';
        } else {
            manualWrapper.style.display = 'none';
            manualInput.value = '';
        }
    });

    // Sync manual input to select hidden option
    document.getElementById('form-empresa').addEventListener('submit', function() {
        if (actividadSelect.value === '' && manualInput.value.trim() !== '') {
            var opt = document.createElement('option');
            opt.value = manualInput.value.trim();
            opt.textContent = manualInput.value.trim();
            opt.selected = true;
            actividadSelect.appendChild(opt);
        }
    });
});
</script>
@endpush
@endsection
