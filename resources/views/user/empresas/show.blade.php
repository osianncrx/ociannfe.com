@extends('layouts.app')

@section('title', 'Detalle Empresa')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-building me-2"></i>Detalle de Empresa</h2>
    <div>
        <a href="{{ route('empresas.edit', $empresa) }}" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="{{ route('empresas.index') }}" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nombre</dt>
                    <dd class="col-sm-7">{{ $empresa->Nombre }}</dd>

                    <dt class="col-sm-5">Nombre Comercial</dt>
                    <dd class="col-sm-7">{{ $empresa->NombreComercial ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Cédula</dt>
                    <dd class="col-sm-7"><code>{{ $empresa->cedula }}</code></dd>

                    <dt class="col-sm-5">Sucursal</dt>
                    <dd class="col-sm-7"><span class="badge bg-info text-dark">{{ $empresa->sucursal }}</span></dd>

                    <dt class="col-sm-5">Tipo Identificación</dt>
                    <dd class="col-sm-7">
                        @switch($empresa->Tipo)
                            @case('01') Física @break
                            @case('02') Jurídica @break
                            @case('03') DIMEX @break
                            @case('04') NITE @break
                            @default {{ $empresa->Tipo ?? 'N/A' }}
                        @endswitch
                    </dd>

                    <dt class="col-sm-5">Número</dt>
                    <dd class="col-sm-7">{{ $empresa->Numero ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7">{{ $empresa->CorreoElectronico ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Ambiente</dt>
                    <dd class="col-sm-7">
                        @if($empresa->id_ambiente == 1)
                            <span class="badge bg-warning text-dark">Staging/Sandbox</span>
                        @elseif($empresa->id_ambiente == 2)
                            <span class="badge bg-success">Producción</span>
                        @else
                            <span class="badge bg-secondary">{{ $empresa->id_ambiente }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5">Código Actividad</dt>
                    <dd class="col-sm-7">{{ $empresa->CodigoActividad ?? 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Provincia</dt>
                    <dd class="col-sm-7">{{ $empresa->Provincia ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Cantón</dt>
                    <dd class="col-sm-7">{{ $empresa->Canton ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Distrito</dt>
                    <dd class="col-sm-7">{{ $empresa->Distrito ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Otras Señas</dt>
                    <dd class="col-sm-7">{{ $empresa->OtrasSenas ?? 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-key me-2"></i>Credenciales MH</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Usuario MH</dt>
                    <dd class="col-sm-7">
                        @if($empresa->usuario_mh)
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Configurado</span>
                        @else
                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i>No configurado</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5">Certificado .p12</dt>
                    <dd class="col-sm-7">
                        @if($empresa->llave_criptografica)
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Cargado</span>
                        @else
                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i>No cargado</span>
                        @endif
                    </dd>
                </dl>
                <hr>
                <p class="mb-2 small text-muted">Verifica usuario y contraseña MH (token Hacienda), certificado .p12 y PIN de la llave criptográfica.</p>
                <form action="{{ route('empresas.verificar-credenciales', $empresa) }}" method="POST" class="d-inline" id="form-verificar-credenciales">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm" id="btn-verificar-credenciales">
                        <i class="fas fa-shield-alt me-1"></i>Verificar credenciales y certificado
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
