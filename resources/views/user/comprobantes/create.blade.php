@extends('layouts.app')

@section('title', 'Emitir Comprobante')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Emitir Comprobante</h2>
</div>

<form action="{{ route('comprobantes.store') }}" method="POST" id="formComprobante">
    @csrf

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Datos del Documento</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="empresa_id" class="form-label">Empresa</label>
                    <select class="form-select @error('empresa_id') is-invalid @enderror" id="empresa_id" name="empresa_id" required>
                        <option value="">Seleccione...</option>
                        @foreach($empresas ?? [] as $empresa)
                            <option value="{{ $empresa->id }}" {{ old('empresa_id') == $empresa->id ? 'selected' : '' }}>{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                    @error('empresa_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="tipo_documento" class="form-label">Tipo Documento</label>
                    <select class="form-select @error('tipo_documento') is-invalid @enderror" id="tipo_documento" name="tipo_documento" required>
                        <option value="">Seleccione...</option>
                        <option value="01" {{ old('tipo_documento') == '01' ? 'selected' : '' }}>01 - Factura Electrónica</option>
                        <option value="02" {{ old('tipo_documento') == '02' ? 'selected' : '' }}>02 - Nota de Débito</option>
                        <option value="03" {{ old('tipo_documento') == '03' ? 'selected' : '' }}>03 - Nota de Crédito</option>
                        <option value="04" {{ old('tipo_documento') == '04' ? 'selected' : '' }}>04 - Tiquete Electrónico</option>
                        <option value="08" {{ old('tipo_documento') == '08' ? 'selected' : '' }}>08 - Factura Compra</option>
                        <option value="09" {{ old('tipo_documento') == '09' ? 'selected' : '' }}>09 - Factura Exportación</option>
                    </select>
                    @error('tipo_documento')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="condicion_venta" class="form-label">Condición Venta</label>
                    <select class="form-select @error('condicion_venta') is-invalid @enderror" id="condicion_venta" name="condicion_venta" required>
                        <option value="">Seleccione...</option>
                        <option value="01" {{ old('condicion_venta') == '01' ? 'selected' : '' }}>01 - Contado</option>
                        <option value="02" {{ old('condicion_venta') == '02' ? 'selected' : '' }}>02 - Crédito</option>
                        <option value="03" {{ old('condicion_venta') == '03' ? 'selected' : '' }}>03 - Consignación</option>
                        <option value="04" {{ old('condicion_venta') == '04' ? 'selected' : '' }}>04 - Apartado</option>
                        <option value="05" {{ old('condicion_venta') == '05' ? 'selected' : '' }}>05 - Arrendamiento con opción de compra</option>
                        <option value="06" {{ old('condicion_venta') == '06' ? 'selected' : '' }}>06 - Arrendamiento en función financiera</option>
                        <option value="99" {{ old('condicion_venta') == '99' ? 'selected' : '' }}>99 - Otros</option>
                    </select>
                    @error('condicion_venta')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="medio_pago" class="form-label">Medio de Pago</label>
                    <select class="form-select @error('medio_pago') is-invalid @enderror" id="medio_pago" name="medio_pago" required>
                        <option value="">Seleccione...</option>
                        <option value="01" {{ old('medio_pago') == '01' ? 'selected' : '' }}>01 - Efectivo</option>
                        <option value="02" {{ old('medio_pago') == '02' ? 'selected' : '' }}>02 - Tarjeta</option>
                        <option value="03" {{ old('medio_pago') == '03' ? 'selected' : '' }}>03 - Cheque</option>
                        <option value="04" {{ old('medio_pago') == '04' ? 'selected' : '' }}>04 - Transferencia</option>
                        <option value="05" {{ old('medio_pago') == '05' ? 'selected' : '' }}>05 - Recaudado por terceros</option>
                        <option value="99" {{ old('medio_pago') == '99' ? 'selected' : '' }}>99 - Otros</option>
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
            <h5 class="mb-0">Receptor</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="receptor_nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control @error('receptor_nombre') is-invalid @enderror" id="receptor_nombre" name="receptor_nombre" value="{{ old('receptor_nombre') }}">
                    @error('receptor_nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="receptor_tipo_id" class="form-label">Tipo ID</label>
                    <select class="form-select @error('receptor_tipo_id') is-invalid @enderror" id="receptor_tipo_id" name="receptor_tipo_id">
                        <option value="">Seleccione...</option>
                        <option value="01" {{ old('receptor_tipo_id') == '01' ? 'selected' : '' }}>01 - Física</option>
                        <option value="02" {{ old('receptor_tipo_id') == '02' ? 'selected' : '' }}>02 - Jurídica</option>
                        <option value="03" {{ old('receptor_tipo_id') == '03' ? 'selected' : '' }}>03 - DIMEX</option>
                        <option value="04" {{ old('receptor_tipo_id') == '04' ? 'selected' : '' }}>04 - NITE</option>
                    </select>
                    @error('receptor_tipo_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="receptor_numero_id" class="form-label">Número ID</label>
                    <input type="text" class="form-control @error('receptor_numero_id') is-invalid @enderror" id="receptor_numero_id" name="receptor_numero_id" value="{{ old('receptor_numero_id') }}">
                    @error('receptor_numero_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <label for="receptor_email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('receptor_email') is-invalid @enderror" id="receptor_email" name="receptor_email" value="{{ old('receptor_email') }}">
                    @error('receptor_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Líneas de Detalle</h5>
            <button type="button" class="btn btn-sm btn-success" id="btnAgregarLinea">
                <i class="fas fa-plus me-1"></i>Agregar Línea
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="tablaLineas">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px;">Código CABYS</th>
                            <th>Detalle</th>
                            <th style="width: 90px;">Cantidad</th>
                            <th style="width: 100px;">Unidad</th>
                            <th style="width: 130px;">Precio Unit.</th>
                            <th style="width: 120px;">Tarifa IVA</th>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let lineaIndex = 0;

    function agregarLinea() {
        const tbody = document.getElementById('lineasBody');
        const tr = document.createElement('tr');
        tr.setAttribute('data-linea', lineaIndex);
        tr.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" name="lineas[${lineaIndex}][codigo_cabys]" placeholder="Código"></td>
            <td><input type="text" class="form-control form-control-sm" name="lineas[${lineaIndex}][detalle]" placeholder="Descripción del producto o servicio" required></td>
            <td><input type="number" class="form-control form-control-sm linea-cantidad" name="lineas[${lineaIndex}][cantidad]" value="1" min="0.01" step="0.01" required></td>
            <td>
                <select class="form-select form-select-sm" name="lineas[${lineaIndex}][unidad]">
                    <option value="Unid">Unid</option>
                    <option value="Sp">Sp</option>
                    <option value="m">m</option>
                    <option value="kg">kg</option>
                    <option value="s">s</option>
                    <option value="l">l</option>
                    <option value="cm">cm</option>
                    <option value="Os">Otros</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-sm linea-precio" name="lineas[${lineaIndex}][precio_unitario]" value="0" min="0" step="0.01" required></td>
            <td>
                <select class="form-select form-select-sm linea-iva" name="lineas[${lineaIndex}][tarifa_iva]">
                    <option value="0">Exento (0%)</option>
                    <option value="1">1%</option>
                    <option value="2">2%</option>
                    <option value="4">4%</option>
                    <option value="8">8%</option>
                    <option value="13" selected>13%</option>
                </select>
            </td>
            <td class="text-end align-middle linea-total fw-semibold">₡0.00</td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-linea" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        lineaIndex++;
        bindLineaEvents(tr);
    }

    function bindLineaEvents(tr) {
        const inputs = tr.querySelectorAll('.linea-cantidad, .linea-precio, .linea-iva');
        inputs.forEach(function (input) {
            input.addEventListener('input', function () { calcularLinea(tr); });
            input.addEventListener('change', function () { calcularLinea(tr); });
        });
        tr.querySelector('.btn-eliminar-linea').addEventListener('click', function () {
            tr.remove();
            calcularTotales();
        });
    }

    function calcularLinea(tr) {
        const cantidad = parseFloat(tr.querySelector('.linea-cantidad').value) || 0;
        const precio = parseFloat(tr.querySelector('.linea-precio').value) || 0;
        const iva = parseFloat(tr.querySelector('.linea-iva').value) || 0;
        const subtotal = cantidad * precio;
        const impuesto = subtotal * (iva / 100);
        const total = subtotal + impuesto;
        tr.querySelector('.linea-total').textContent = '₡' + total.toFixed(2);
        calcularTotales();
    }

    function calcularTotales() {
        let subtotal = 0;
        let totalImpuesto = 0;
        document.querySelectorAll('#lineasBody tr').forEach(function (tr) {
            const cantidad = parseFloat(tr.querySelector('.linea-cantidad').value) || 0;
            const precio = parseFloat(tr.querySelector('.linea-precio').value) || 0;
            const iva = parseFloat(tr.querySelector('.linea-iva').value) || 0;
            const lineaSubtotal = cantidad * precio;
            subtotal += lineaSubtotal;
            totalImpuesto += lineaSubtotal * (iva / 100);
        });
        document.getElementById('subtotal').textContent = '₡' + subtotal.toFixed(2);
        document.getElementById('totalImpuesto').textContent = '₡' + totalImpuesto.toFixed(2);
        document.getElementById('totalComprobante').textContent = '₡' + (subtotal + totalImpuesto).toFixed(2);
    }

    document.getElementById('btnAgregarLinea').addEventListener('click', agregarLinea);

    agregarLinea();
});
</script>
@endpush
