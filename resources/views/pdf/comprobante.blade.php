<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 30px 40px; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .fw-bold { font-weight: bold; }
    .small { font-size: 8px; }
    .border { border: 1px solid #999; }
    .border-bottom { border-bottom: 1px solid #999; }

    .header-table td { vertical-align: top; padding: 2px 0; }
    .header-info { font-size: 9px; line-height: 1.5; }
    .header-info .company-name { font-size: 13px; font-weight: bold; }

    .doc-title { font-size: 12px; font-weight: bold; margin: 8px 0 2px 0; }
    .clave { font-size: 8px; color: #555; }

    .fecha-box { border: 1px solid #999; }
    .fecha-box td { text-align: center; padding: 2px 8px; font-size: 9px; }
    .fecha-box .label { background: #e0e0e0; font-weight: bold; font-size: 8px; }

    .info-section { margin-top: 6px; }
    .info-section .section-header { background: #e0e0e0; font-weight: bold; font-size: 9px; padding: 3px 6px; border: 1px solid #999; }
    .info-section td { padding: 2px 6px; font-size: 9px; vertical-align: top; }

    .items-table { margin-top: 8px; }
    .items-table th { background: #e0e0e0; font-weight: bold; font-size: 9px; padding: 4px 6px; border: 1px solid #999; }
    .items-table td { padding: 3px 6px; font-size: 9px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; vertical-align: top; }
    .items-table .item-sub { font-size: 8px; color: #555; padding-left: 20px; }
    .items-table .last-row td { border-bottom: 1px solid #999; }

    .separator { text-align: center; font-size: 8px; padding: 4px 0; color: #555; }

    .summary-section { margin-top: 6px; font-size: 9px; }
    .summary-section td { padding: 2px 6px; }
    .summary-label { font-weight: bold; }

    .footer-table { margin-top: 8px; }
    .footer-table td { vertical-align: top; font-size: 9px; padding: 2px 6px; }
    .terms-box { border: 1px solid #999; }
    .terms-header { background: #e0e0e0; font-weight: bold; font-size: 8px; padding: 3px 6px; border-bottom: 1px solid #999; }
    .terms-content { padding: 6px; font-size: 8px; line-height: 1.4; min-height: 80px; }
    .signature-box { border: 1px solid #999; text-align: center; }
    .signature-header { background: #e0e0e0; font-weight: bold; font-size: 8px; padding: 3px 6px; border-bottom: 1px solid #999; }
    .signature-content { padding: 6px; min-height: 80px; }

    .totals-box { border: 1px solid #999; }
    .totals-box td { padding: 2px 8px; font-size: 9px; border-bottom: 1px solid #ddd; }
    .totals-box .label-cell { text-align: right; font-weight: bold; background: #f5f5f5; }
    .totals-box .total-final td { font-weight: bold; background: #e8e8e8; }

    .legal-footer { margin-top: 6px; font-size: 7px; color: #555; border-top: 1px solid #999; padding-top: 4px; }
</style>
</head>
<body>

@php
    $color = $empresa->pdf_color_primario ?? '#000000';
    $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $fecha = $comprobante->FechaEmision;
    $simbolo = '₡';
@endphp

{{-- HEADER --}}
<table class="header-table" style="margin-bottom: 4px;">
    <tr>
        <td style="width: 70%;">
            <div class="header-info">
                @if($empresa->NombreComercial)
                    <div class="company-name" style="color: {{ $color }};">{{ $empresa->NombreComercial }}</div>
                @endif
                <div style="font-weight: bold;">{{ $empresa->Nombre }}</div>
                @if($empresa->pdf_encabezado)
                    {!! nl2br(e($empresa->pdf_encabezado)) !!}
                @else
                    @if($empresa->OtrasSenas){{ $empresa->OtrasSenas }}<br>@endif
                    @if($empresa->NumTelefono)Tel: {{ $empresa->NumTelefono }}<br>@endif
                    Cédula Jurídica: {{ $empresa->cedula }}<br>
                    @if($empresa->CorreoElectronico)Email: {{ $empresa->CorreoElectronico }}@endif
                @endif
            </div>
        </td>
        <td style="width: 30%; text-align: right;">
            @if($empresa->pdf_logo)
                <img src="{{ $empresa->pdf_logo }}" style="max-width: 160px; max-height: 80px;">
            @endif
        </td>
    </tr>
</table>

{{-- DOCUMENT TITLE + DATE --}}
<table style="margin-bottom: 4px;">
    <tr>
        <td style="width: 65%; vertical-align: top;">
            <div class="doc-title" style="color: {{ $color }};">{{ $comprobante->tipo_documento_texto }} No. {{ $comprobante->NumeroConsecutivo }}</div>
            <div class="clave">Clave: {{ $comprobante->clave }}</div>
        </td>
        <td style="width: 35%; vertical-align: top;">
            <table class="fecha-box" style="float: right; width: auto;">
                <tr class="label">
                    <td>Día</td>
                    <td>Mes</td>
                    <td>Año</td>
                </tr>
                <tr>
                    <td>{{ $fecha ? $fecha->format('d') : '—' }}</td>
                    <td>{{ $fecha ? $meses[$fecha->month - 1] : '—' }}</td>
                    <td>{{ $fecha ? $fecha->format('Y') : '—' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- CLIENT INFO + DETAILS --}}
<table class="info-section" style="border: 1px solid #999;">
    <tr>
        <td style="width: 50%; border-right: 1px solid #999; vertical-align: top;">
            <div class="section-header" style="background: #e0e0e0; margin: -2px -6px 4px -6px; padding: 3px 6px;">Información de Cliente:</div>
            <div style="padding: 2px;">
                {{ $comprobante->Receptor_Nombre ?? 'N/A' }}<br>
                @if($comprobante->Receptor_TipoIdentificacion || $comprobante->Receptor_NumeroIdentificacion)
                    Cédula {{ $comprobante->Receptor_TipoIdentificacion == '01' ? 'Física' : ($comprobante->Receptor_TipoIdentificacion == '02' ? 'Jurídica' : ($comprobante->Receptor_TipoIdentificacion == '03' ? 'DIMEX' : '')) }}:
                    {{ $comprobante->Receptor_NumeroIdentificacion }}<br>
                @endif
                @if($comprobante->Receptor_OtrasSenas){{ $comprobante->Receptor_OtrasSenas }}<br>@endif
                @if($comprobante->Receptor_CorreoElectronico){{ $comprobante->Receptor_CorreoElectronico }}<br>@endif
            </div>
        </td>
        <td style="width: 50%; vertical-align: top;">
            <div class="section-header" style="background: #e0e0e0; margin: -2px -6px 4px -6px; padding: 3px 6px;">Detalles:</div>
            <table style="width: 100%;">
                <tr><td class="fw-bold" style="width: 45%;">Email:</td><td>{{ $comprobante->Receptor_CorreoElectronico ?? '' }}</td></tr>
                <tr><td class="fw-bold">Condición Venta:</td><td>{{ $comprobante->CondicionVenta == '01' ? 'Contado' : ($comprobante->CondicionVenta == '02' ? 'Crédito' : ($comprobante->CondicionVenta ?? '')) }}</td></tr>
                <tr><td class="fw-bold">Medio de Pago:</td><td>
                    @switch($comprobante->MedioPago)
                        @case('01') Efectivo @break
                        @case('02') Tarjeta @break
                        @case('03') Cheque @break
                        @case('04') Transferencia @break
                        @case('99') Otros @break
                        @default {{ $comprobante->MedioPago ?? '' }}
                    @endswitch
                </td></tr>
                @if($fecha)
                <tr><td class="fw-bold">Fecha Emisión:</td><td>{{ $fecha->format('d/m/Y H:i') }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- LINE ITEMS TABLE --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 12%;">Producto</th>
            <th style="width: 38%;">Descripción</th>
            <th style="width: 10%;" class="text-right">Cantidad</th>
            <th style="width: 10%;">Unidades</th>
            <th style="width: 15%;" class="text-right">Precio Unitario</th>
            <th style="width: 15%;" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($comprobante->lineas as $i => $linea)
        <tr @if($loop->last) class="last-row" @endif>
            <td>{{ $linea->Codigo ?? '' }}</td>
            <td>
                {{ $linea->Detalle }}
                @if($linea->Descuento_MontoDescuento && $linea->Descuento_MontoDescuento > 0)
                    <div class="item-sub">Descuento: {{ $simbolo }}{{ number_format((float)$linea->Descuento_MontoDescuento, 2) }}</div>
                @endif
                @if($linea->Impuesto_Monto)
                    <div class="item-sub">IVA - Tarifa Plena {{ $linea->Impuesto_Tarifa ?? 13 }}% ({{ $linea->Impuesto_Tarifa ?? 13 }}%): {{ $simbolo }}{{ number_format((float)$linea->Impuesto_Monto, 2) }}</div>
                @endif
            </td>
            <td class="text-right">{{ number_format((float)$linea->Cantidad, 2) }}</td>
            <td>{{ $linea->UnidadMedida ?? 'Unidad' }}</td>
            <td class="text-right">{{ $simbolo }}{{ number_format((float)$linea->PrecioUnitario, 2) }}</td>
            <td class="text-right">{{ $simbolo }}{{ number_format((float)$linea->MontoTotalLinea, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="separator">**** ULTIMA LINEA ****</div>

{{-- TAXES + PAYMENT SUMMARY --}}
<table class="summary-section">
    @if($comprobante->TotalImpuesto > 0)
    <tr>
        <td class="summary-label" style="width: 15%;">Impuestos:</td>
        <td style="width: 35%;">IVA - Tarifa Plena 13%:</td>
        <td style="width: 20%;">{{ $simbolo }}{{ number_format((float)$comprobante->TotalImpuesto, 2) }}</td>
        <td style="width: 30%;"></td>
    </tr>
    @endif
    <tr>
        <td class="summary-label">Medio de Pago:</td>
        <td colspan="3">
            @switch($comprobante->MedioPago)
                @case('01') Efectivo @break
                @case('02') Tarjeta @break
                @case('03') Cheque @break
                @case('04') Transferencia / Depósito @break
                @case('99') Otros @break
                @default {{ $comprobante->MedioPago ?? '' }}
            @endswitch
        </td>
    </tr>
</table>

{{-- COMMENTS / OBSERVATIONS --}}
@if($empresa->pdf_mostrar_comentarios !== false)
<div style="margin-top: 8px; font-size: 9px;">
    <div class="fw-bold" style="margin-bottom: 2px;">Comentarios y Observaciones:</div>
    <div style="border-top: 1px solid #ddd; padding-top: 4px; min-height: 30px; color: #555;">
        &nbsp;
    </div>
</div>
@endif

{{-- FOOTER: TERMS + SIGNATURE + TOTALS --}}
<table class="footer-table" style="width: 100%;">
    <tr>
        <td style="width: 35%; vertical-align: top;">
            <div class="terms-box">
                <div class="terms-header">Términos y Condiciones:</div>
                <div class="terms-content">
                    @if($empresa->pdf_pie_pagina)
                        {!! nl2br(e($empresa->pdf_pie_pagina)) !!}
                    @else
                        &nbsp;
                    @endif
                </div>
            </div>
        </td>
        <td style="width: 25%; vertical-align: top;">
            <div class="signature-box">
                <div class="signature-header">Recibido Por:</div>
                <div class="signature-content">
                    <br><br><br>
                    <div style="border-top: 1px solid #999; margin-top: 10px; padding-top: 2px;">(Firma)</div>
                </div>
            </div>
        </td>
        <td style="width: 40%; vertical-align: top;">
            <table class="totals-box" style="width: 100%;">
                <tr>
                    <td class="label-cell">SubTotal Gravado:</td>
                    <td class="text-right">{{ $simbolo }}{{ number_format((float)($comprobante->TotalGravado ?? $comprobante->TotalVenta ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Descuento Gravado:</td>
                    <td class="text-right">{{ $simbolo }}{{ number_format((float)($comprobante->TotalDescuentos ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="label-cell">SubTotal Exento:</td>
                    <td class="text-right">{{ $simbolo }}{{ number_format((float)($comprobante->TotalExento ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Descuento Exento:</td>
                    <td class="text-right">{{ $simbolo }}0.00</td>
                </tr>
                <tr>
                    <td class="label-cell">Impuestos:</td>
                    <td class="text-right">{{ $simbolo }}{{ number_format((float)($comprobante->TotalImpuesto ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Total:</td>
                    <td class="text-right">{{ $simbolo }}{{ number_format((float)($comprobante->TotalComprobante ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Otros:</td>
                    <td class="text-right">{{ $simbolo }}0.00</td>
                </tr>
                <tr class="total-final">
                    <td class="label-cell">Total a Pagar:</td>
                    <td class="text-right">{{ $simbolo }}{{ number_format((float)($comprobante->TotalComprobante ?? 0), 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- LEGAL FOOTER --}}
<div class="legal-footer">
    <table style="width: 100%;">
        <tr>
            <td style="width: 70%;">
                Emitida conforme lo establecido en la resolución de Facturación Electrónica No. DGT-RES-0027-2024 del 19-Nov-2024. de la D.G.T.D.<br>
                Versión 4.4.<br>
                Factura electrónica generada por Ociann FE (ociannfe.com).
            </td>
            <td class="text-right">
                @if($fecha)
                    Factura emitida el: {{ $fecha->format('d/m/Y h:i a') }}
                @endif
            </td>
        </tr>
    </table>
</div>

</body>
</html>
