@extends('layouts.app')

@section('title', 'Plantilla PDF - ' . $empresa->Nombre)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Plantilla PDF</h2>
        <p class="text-muted mb-0 mt-1">{{ $empresa->Nombre }} ({{ $empresa->cedula }})</p>
    </div>
    <div>
        <a href="{{ route('empresas.plantilla-pdf.preview', $empresa->id_empresa) }}" class="btn btn-outline-primary" target="_blank">
            <i class="fas fa-eye me-1"></i>Vista Previa
        </a>
        <a href="{{ route('empresas.show', $empresa->id_empresa) }}" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

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

<form action="{{ route('empresas.plantilla-pdf.update', $empresa->id_empresa) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-image me-2"></i>Logo de la Empresa</h5>
                </div>
                <div class="card-body">
                    @if($empresa->pdf_logo)
                        <div class="mb-3 text-center">
                            <img src="{{ $empresa->pdf_logo }}" alt="Logo actual" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="eliminar_logo" value="1" id="eliminar_logo">
                            <label class="form-check-label" for="eliminar_logo">Eliminar logo actual</label>
                        </div>
                    @endif
                    <label for="pdf_logo" class="form-label">{{ $empresa->pdf_logo ? 'Cambiar logo' : 'Subir logo' }}</label>
                    <input type="file" class="form-control" id="pdf_logo" name="pdf_logo" accept="image/png,image/jpeg,image/gif,image/svg+xml">
                    <div class="form-text">PNG, JPG o SVG. Máximo 2MB. Se recomienda fondo transparente.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Apariencia</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="pdf_color_primario" class="form-label">Color Primario</label>
                        <div class="input-group" style="max-width: 200px;">
                            <input type="color" class="form-control form-control-color" id="pdf_color_picker"
                                   value="{{ $empresa->pdf_color_primario ?? '#000000' }}">
                            <input type="text" class="form-control" id="pdf_color_primario" name="pdf_color_primario"
                                   value="{{ $empresa->pdf_color_primario ?? '#000000' }}" maxlength="7" pattern="#[0-9a-fA-F]{6}">
                        </div>
                        <div class="form-text">Se usa para el nombre comercial y título del documento.</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="pdf_mostrar_comentarios"
                               name="pdf_mostrar_comentarios" value="1"
                               {{ old('pdf_mostrar_comentarios', $empresa->pdf_mostrar_comentarios ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="pdf_mostrar_comentarios">Mostrar sección "Comentarios y Observaciones"</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-heading me-2"></i>Encabezado del PDF</h5>
                </div>
                <div class="card-body">
                    <label for="pdf_encabezado" class="form-label">Texto del encabezado</label>
                    <textarea class="form-control" id="pdf_encabezado" name="pdf_encabezado" rows="4"
                              placeholder="Ej: Heredia, Santo Domingo, Santa Rosa&#10;Tel: 2222-3333&#10;www.miempresa.com">{{ old('pdf_encabezado', $empresa->pdf_encabezado) }}</textarea>
                    <div class="form-text">
                        Si se deja vacío se usará la dirección, teléfono, cédula y correo registrados en la empresa.
                        Cada línea aparecerá como una línea separada debajo del nombre de la empresa.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Términos y Condiciones / Pie de Página</h5>
                </div>
                <div class="card-body">
                    <label for="pdf_pie_pagina" class="form-label">Texto de términos y condiciones</label>
                    <textarea class="form-control" id="pdf_pie_pagina" name="pdf_pie_pagina" rows="4"
                              placeholder="Ej: Esta factura devenga intereses del 3% mensual después de su vencimiento.&#10;Un año de garantía, por defectos de fábrica.">{{ old('pdf_pie_pagina', $empresa->pdf_pie_pagina) }}</textarea>
                    <div class="form-text">Aparecerá en el cuadro "Términos y Condiciones" al pie del PDF.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 mb-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-1"></i>Guardar Plantilla
        </button>
        <a href="{{ route('empresas.plantilla-pdf.preview', $empresa->id_empresa) }}" class="btn btn-outline-primary btn-lg" target="_blank">
            <i class="fas fa-eye me-1"></i>Vista Previa PDF
        </a>
        <a href="{{ route('empresas.show', $empresa->id_empresa) }}" class="btn btn-secondary btn-lg">
            <i class="fas fa-times me-1"></i>Cancelar
        </a>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var picker = document.getElementById('pdf_color_picker');
    var text = document.getElementById('pdf_color_primario');
    picker.addEventListener('input', function () { text.value = this.value; });
    text.addEventListener('input', function () {
        if (/^#[0-9a-fA-F]{6}$/.test(this.value)) picker.value = this.value;
    });
});
</script>
@endpush
