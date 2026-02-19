@extends('layouts.app')

@section('title', 'Crear API Key — Admin')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.api-keys.index') }}" class="text-muted text-decoration-none small">
        <i class="fas fa-arrow-left me-1"></i>Volver a API Keys
    </a>
    <h2 class="mb-0 mt-1"><i class="fas fa-plus-circle me-2"></i>Nueva API Key</h2>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.api-keys.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}"
                               placeholder="Ej: Integración ERP, App Mobile..." required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="tenant_id" class="form-label fw-semibold">Tenant <span class="text-danger">*</span></label>
                        <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id" required>
                            <option value="">Seleccionar tenant...</option>
                            @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                        @error('tenant_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="expires_at" class="form-label fw-semibold">Fecha de expiración</label>
                        <input type="date" class="form-control @error('expires_at') is-invalid @enderror"
                               id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                        <small class="text-muted">Dejar vacío para que no expire.</small>
                        @error('expires_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Permisos</label>
                        <div class="row">
                            @foreach(['empresas:read', 'empresas:write', 'comprobantes:read', 'comprobantes:write', 'recepciones:read', 'cola:read', 'cola:write'] as $perm)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm }}" id="perm_{{ $perm }}"
                                           {{ in_array($perm, old('permissions', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="perm_{{ $perm }}">
                                        <code>{{ $perm }}</code>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Sin permisos seleccionados = acceso completo.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i>Crear API Key
                        </button>
                        <a href="{{ route('admin.api-keys.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-1"></i>Información</h6>
                <p class="small text-muted mb-2">
                    Al crear la API Key se generará un par <strong>key/secret</strong> que solo se mostrará una vez.
                </p>
                <p class="small text-muted mb-2">
                    La API Key se usa en el header <code>X-API-Key</code> para autenticar llamadas a la API.
                </p>
                <p class="small text-muted mb-0">
                    Consulta la <a href="{{ url('/api/docs') }}" target="_blank">documentación de la API</a> para más detalles.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
