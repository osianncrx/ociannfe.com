<?php
declare(strict_types=1);
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmitirComprobanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_empresa' => 'required|integer',
            'Receptor' => 'required|array',
            'Receptor.Nombre' => 'required|string|max:255',
            'Receptor.Identificacion' => 'nullable|array',
            'Receptor.Identificacion.Tipo' => 'required_with:Receptor.Identificacion|string|in:01,02,03,04',
            'Receptor.Identificacion.Numero' => 'required_with:Receptor.Identificacion|string|max:12',
            'Receptor.CorreoElectronico' => 'nullable|email|max:255',
            'CondicionVenta' => 'required|string|in:01,02,03,04,05,06,07,08,09,10,11,12,13,99',
            'Lineas' => 'required|array|min:1',
            'Lineas.*.NumeroLinea' => 'required|integer|min:1',
            'Lineas.*.Codigo' => 'required|string',
            'Lineas.*.Cantidad' => 'required|numeric|min:0.01',
            'Lineas.*.UnidadMedida' => 'required|string|max:20',
            'Lineas.*.Detalle' => 'required|string|max:255',
            'Lineas.*.PrecioUnitario' => 'required|numeric|min:0',
            'Lineas.*.Impuesto' => 'nullable|array',
            'Lineas.*.Impuesto.Codigo' => 'nullable|string',
            'Lineas.*.Impuesto.Tarifa' => 'nullable|numeric|min:0|max:100',
            'Lineas.*.Descuento' => 'nullable|array',
            'Lineas.*.Descuento.MontoDescuento' => 'nullable|numeric|min:0',
            'MediosPago' => 'nullable|array',
            'MediosPago.*' => 'string|in:01,02,03,04,05,99',
            'Sucursal' => 'nullable|string|size:3',
            'Terminal' => 'nullable|string|size:5',
            'TipoDoc' => 'nullable|string|in:01,02,03,04,08,09',
            'FechaEmision' => 'nullable|date',
            'CodigoTipoMoneda' => 'nullable|array',
            'CodigoTipoMoneda.CodigoMoneda' => 'nullable|string|size:3',
            'CodigoTipoMoneda.TipoCambio' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'id_empresa.required' => 'El ID de empresa es obligatorio.',
            'Receptor.required' => 'Los datos del receptor son obligatorios.',
            'Receptor.Nombre.required' => 'El nombre del receptor es obligatorio.',
            'CondicionVenta.required' => 'La condición de venta es obligatoria.',
            'Lineas.required' => 'Debe incluir al menos una línea de detalle.',
            'Lineas.min' => 'Debe incluir al menos una línea de detalle.',
            'Lineas.*.Detalle.required' => 'El detalle de cada línea es obligatorio.',
            'Lineas.*.Cantidad.required' => 'La cantidad de cada línea es obligatoria.',
            'Lineas.*.PrecioUnitario.required' => 'El precio unitario de cada línea es obligatorio.',
        ];
    }
}
