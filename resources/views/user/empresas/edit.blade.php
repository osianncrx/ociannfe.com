@extends('layouts.app')

@section('title', 'Editar Empresa')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Empresa</h2>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('empresas.update', $empresa) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="Tipo" class="form-label">Tipo Identificación</label>
                    <select class="form-select" id="Tipo" disabled>
                        <option value="01" {{ $empresa->Tipo == '01' ? 'selected' : '' }}>01 - Física</option>
                        <option value="02" {{ $empresa->Tipo == '02' ? 'selected' : '' }}>02 - Jurídica</option>
                        <option value="03" {{ $empresa->Tipo == '03' ? 'selected' : '' }}>03 - DIMEX</option>
                        <option value="04" {{ $empresa->Tipo == '04' ? 'selected' : '' }}>04 - NITE</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="cedula" class="form-label">Cédula</label>
                    <input type="text" class="form-control" id="cedula" value="{{ $empresa->cedula }}" readonly disabled>
                </div>

                <div class="col-md-4">
                    <label for="sucursal" class="form-label">Sucursal</label>
                    <input type="text" class="form-control" id="sucursal" value="{{ $empresa->sucursal }}" readonly disabled>
                    <div class="form-text">Asignada automáticamente, no modificable.</div>
                </div>

                <div class="col-md-6">
                    <label for="Nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control @error('Nombre') is-invalid @enderror" id="Nombre" name="Nombre" value="{{ old('Nombre', $empresa->Nombre) }}" required>
                    @error('Nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="NombreComercial" class="form-label">Nombre Comercial</label>
                    <input type="text" class="form-control @error('NombreComercial') is-invalid @enderror" id="NombreComercial" name="NombreComercial" value="{{ old('NombreComercial', $empresa->NombreComercial) }}">
                    @error('NombreComercial')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="id_ambiente" class="form-label">Ambiente</label>
                    <select class="form-select" id="id_ambiente" disabled>
                        @foreach($ambientes as $ambiente)
                            <option value="{{ $ambiente->id_ambiente }}" {{ $empresa->id_ambiente == $ambiente->id_ambiente ? 'selected' : '' }}>
                                {{ $ambiente->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="CorreoElectronico" class="form-label">Email</label>
                    <input type="email" class="form-control @error('CorreoElectronico') is-invalid @enderror" id="CorreoElectronico" name="CorreoElectronico" value="{{ old('CorreoElectronico', $empresa->CorreoElectronico) }}">
                    @error('CorreoElectronico')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="CodigoActividad" class="form-label">Código Actividad</label>
                    <input type="text" class="form-control @error('CodigoActividad') is-invalid @enderror" id="CodigoActividad" name="CodigoActividad" value="{{ old('CodigoActividad', $empresa->CodigoActividad) }}">
                    @error('CodigoActividad')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <hr>
                    <h5><i class="fas fa-key me-2"></i>Credenciales Ministerio de Hacienda</h5>
                    <p class="form-text mb-0">Dejar los campos vacíos para mantener los valores actuales.</p>
                </div>

                <div class="col-md-4">
                    <label for="usuario_mh" class="form-label">Usuario MH</label>
                    <input type="text" class="form-control @error('usuario_mh') is-invalid @enderror" id="usuario_mh" name="usuario_mh" placeholder="Dejar vacío para no cambiar">
                    @error('usuario_mh')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="contra_mh" class="form-label">Contraseña MH</label>
                    <input type="password" class="form-control @error('contra_mh') is-invalid @enderror" id="contra_mh" name="contra_mh" placeholder="Dejar vacío para no cambiar">
                    @error('contra_mh')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="pin_llave" class="form-label">PIN Llave</label>
                    <input type="password" class="form-control @error('pin_llave') is-invalid @enderror" id="pin_llave" name="pin_llave" placeholder="Dejar vacío para no cambiar">
                    @error('pin_llave')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="llave_criptografica" class="form-label">Certificado .p12</label>
                    <input type="file" class="form-control @error('llave_criptografica') is-invalid @enderror" id="llave_criptografica" name="llave_criptografica" accept=".p12">
                    <div class="form-text">Si reemplaza el certificado, <strong>debe escribir también el PIN de la llave</strong> en el campo «PIN Llave» arriba (ej. si lo obtuvo en apis.gometa.org/p12 use ese) y luego guardar.</div>
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
                    <input type="text" class="form-control @error('Provincia') is-invalid @enderror" id="Provincia" name="Provincia" value="{{ old('Provincia', $empresa->Provincia) }}">
                    @error('Provincia')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="Canton" class="form-label">Cantón</label>
                    <input type="text" class="form-control @error('Canton') is-invalid @enderror" id="Canton" name="Canton" value="{{ old('Canton', $empresa->Canton) }}">
                    @error('Canton')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="Distrito" class="form-label">Distrito</label>
                    <input type="text" class="form-control @error('Distrito') is-invalid @enderror" id="Distrito" name="Distrito" value="{{ old('Distrito', $empresa->Distrito) }}">
                    @error('Distrito')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="OtrasSenas" class="form-label">Otras Señas</label>
                    <textarea class="form-control @error('OtrasSenas') is-invalid @enderror" id="OtrasSenas" name="OtrasSenas" rows="2">{{ old('OtrasSenas', $empresa->OtrasSenas) }}</textarea>
                    @error('OtrasSenas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Actualizar
                </button>
                <a href="{{ route('empresas.index') }}" class="btn btn-secondary ms-2">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
