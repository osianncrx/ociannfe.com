@extends('layouts.app')

@section('title', 'Editar Plan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Plan</h2>
    <a href="{{ url('/admin/planes') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ url('/admin/planes/' . $plan->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $plan->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $plan->slug) }}" required>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $plan->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="price" class="form-label">Precio <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $plan->price) }}" required>
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="currency" class="form-label">Moneda <span class="text-danger">*</span></label>
                    <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency" required>
                        <option value="CRC" {{ old('currency', $plan->currency) == 'CRC' ? 'selected' : '' }}>CRC - Colón</option>
                        <option value="USD" {{ old('currency', $plan->currency) == 'USD' ? 'selected' : '' }}>USD - Dólar</option>
                    </select>
                    @error('currency')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="billing_cycle" class="form-label">Ciclo de Facturación <span class="text-danger">*</span></label>
                    <select class="form-select @error('billing_cycle') is-invalid @enderror" id="billing_cycle" name="billing_cycle" required>
                        <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) == 'monthly' ? 'selected' : '' }}>Mensual</option>
                        <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle) == 'yearly' ? 'selected' : '' }}>Anual</option>
                    </select>
                    @error('billing_cycle')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="max_empresas" class="form-label">Máx Empresas <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('max_empresas') is-invalid @enderror" id="max_empresas" name="max_empresas" value="{{ old('max_empresas', $plan->max_empresas) }}" required>
                    <div class="form-text">Usar -1 para ilimitado</div>
                    @error('max_empresas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="max_comprobantes" class="form-label">Máx Comprobantes/Mes <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('max_comprobantes') is-invalid @enderror" id="max_comprobantes" name="max_comprobantes" value="{{ old('max_comprobantes', $plan->max_comprobantes) }}" required>
                    <div class="form-text">Usar -1 para ilimitado</div>
                    @error('max_comprobantes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="max_api_keys" class="form-label">Máx API Keys <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('max_api_keys') is-invalid @enderror" id="max_api_keys" name="max_api_keys" value="{{ old('max_api_keys', $plan->max_api_keys) }}" required>
                    <div class="form-text">Usar -1 para ilimitado</div>
                    @error('max_api_keys')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="sort_order" class="form-label">Orden</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label d-block">Opciones</label>
                    <div class="d-flex gap-4 mt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="api_access" name="api_access" value="1" {{ old('api_access', $plan->api_access) ? 'checked' : '' }}>
                            <label class="form-check-label" for="api_access">Acceso API</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="s3_storage" name="s3_storage" value="1" {{ old('s3_storage', $plan->s3_storage) ? 'checked' : '' }}>
                            <label class="form-check-label" for="s3_storage">Almacenamiento S3</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Activo</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Actualizar Plan
                </button>
                <a href="{{ url('/admin/planes') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
