<?php
declare(strict_types=1);
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmitirComprobanteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $lineas = $this->input('Lineas', []);

        if (!is_array($lineas)) {
            return;
        }

        foreach ($lineas as $index => $linea) {
            if (!is_array($linea)) {
                continue;
            }

            $codigo = trim((string) ($linea['Codigo'] ?? ''));
            $codigoCabys = trim((string) ($linea['CodigoCABYS'] ?? ''));

            if ($codigo === '' && $codigoCabys !== '') {
                $linea['Codigo'] = $codigoCabys;
                $lineas[$index] = $linea;
            }
        }

        $this->merge([
            'Lineas' => $lineas,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_empresa' => 'required|integer',

            // Receptor
            'Receptor' => 'required|array',
            'Receptor.Nombre' => 'required|string|max:255',
            'Receptor.Identificacion' => 'nullable|array',
            'Receptor.Identificacion.Tipo' => 'required_with:Receptor.Identificacion|string|in:01,02,03,04,05',
            'Receptor.Identificacion.Numero' => 'required_with:Receptor.Identificacion|string|max:12',
            'Receptor.NombreComercial' => 'nullable|string|max:255',
            'Receptor.CorreoElectronico' => 'nullable|email|max:255',
            'Receptor.CodigoActividad' => 'nullable|string|max:10',
            'Receptor.Ubicacion' => 'nullable|array',
            'Receptor.Ubicacion.Provincia' => 'nullable|string|max:2',
            'Receptor.Ubicacion.Canton' => 'nullable|string|max:3',
            'Receptor.Ubicacion.Distrito' => 'nullable|string|max:3',
            'Receptor.Ubicacion.OtrasSenas' => 'nullable|string|max:255',

            'CondicionVenta' => 'required|string|in:01,02,03,04,05,06,07,08,09,10,11,12,13,99',
            'PlazoCredito' => 'nullable|integer|min:0|max:999',

            // Lineas de detalle
            'Lineas' => 'required|array|min:1',
            'Lineas.*.NumeroLinea' => 'required|integer|min:1',
            'Lineas.*.Codigo' => 'required|string',
            'Lineas.*.CodigoCABYS' => 'nullable|string|max:13',
            'Lineas.*.Cantidad' => 'required|numeric|min:0.0001',
            'Lineas.*.UnidadMedida' => 'required|string|max:20',
            'Lineas.*.Detalle' => 'required|string|max:200',
            'Lineas.*.PrecioUnitario' => 'required|numeric|min:0',
            'Lineas.*.Impuesto' => 'nullable|array',
            'Lineas.*.Impuesto.Codigo' => 'nullable|string|in:01,02,03,04,05,06,07,08,12,98,99',
            'Lineas.*.Impuesto.CodigoTarifa' => 'nullable|string|in:01,02,03,04,05,06,07,08',
            'Lineas.*.Impuesto.CodigoTarifaIVA' => 'nullable|string|in:01,02,03,04,05,06,07,08',
            'Lineas.*.Impuesto.Tarifa' => 'nullable|numeric|min:0|max:100',
            'Lineas.*.Impuesto.FactorIVA' => 'nullable|numeric|min:0|max:1',
            'Lineas.*.Impuesto.Exoneracion' => 'nullable|array',
            'Lineas.*.Impuesto.Exoneracion.TipoDocumento' => 'nullable|string|max:10',
            'Lineas.*.Impuesto.Exoneracion.NumeroDocumento' => 'nullable|string|max:40',
            'Lineas.*.Impuesto.Exoneracion.Porcentaje' => 'nullable|numeric|min:0|max:100',
            'Lineas.*.Impuesto.Exoneracion.MontoExoneracion' => 'nullable|numeric|min:0',
            'Lineas.*.Descuento' => 'nullable|array',
            'Lineas.*.Descuento.MontoDescuento' => 'nullable|numeric|min:0',
            'Lineas.*.Descuento.CodigoDescuento' => 'nullable|string|size:2',
            'Lineas.*.Descuento.NaturalezaDescuento' => 'nullable|string|max:80',
            'Lineas.*.PartidaArancelaria' => 'nullable|string|max:20',
            'Lineas.*.BaseImponible' => 'nullable|numeric|min:0',

            // Medios de pago (FE 4.4: incluye Sinpe Movil, plataformas digitales)
            'MediosPago' => 'nullable|array',
            'MediosPago.*' => 'string|in:01,02,03,04,05,06,07,08,99',

            // Otros cargos
            'OtrosCargos' => 'nullable|array',
            'OtrosCargos.*.TipoDocumento' => 'nullable|string|max:2',
            'OtrosCargos.*.Detalle' => 'nullable|string|max:160',
            'OtrosCargos.*.MontoCargo' => 'nullable|numeric|min:0',

            'Sucursal' => 'nullable|string|size:3',
            'Terminal' => 'nullable|string|size:5',
            'TipoDoc' => 'nullable|string|in:01,02,03,04,08,09,10',

            // Referencia
            'InformacionReferencia' => 'nullable|array',
            'InformacionReferencia.TipoDoc' => 'required_with:InformacionReferencia|string|in:01,02,03,04,08,09,10,99',
            'InformacionReferencia.Numero' => 'required_with:InformacionReferencia|string|max:50',
            'InformacionReferencia.FechaEmision' => 'required_with:InformacionReferencia|date',
            'InformacionReferencia.Codigo' => 'required_with:InformacionReferencia|string|in:01,02,03,04,05,99',
            'InformacionReferencia.Razon' => 'required_with:InformacionReferencia|string|max:180',

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
