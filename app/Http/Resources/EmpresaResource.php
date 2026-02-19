<?php
declare(strict_types=1);
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_empresa,
            'cedula' => $this->cedula,
            'sucursal' => $this->sucursal,
            'nombre' => $this->Nombre,
            'nombre_comercial' => $this->NombreComercial,
            'tipo_identificacion' => $this->Tipo,
            'numero_identificacion' => $this->Numero,
            'ambiente' => $this->id_ambiente == 1 ? 'Staging' : 'Producción',
            'ambiente_id' => $this->id_ambiente,
            'ubicacion' => [
                'provincia' => $this->Provincia,
                'canton' => $this->Canton,
                'distrito' => $this->Distrito,
                'otras_senas' => $this->OtrasSenas,
            ],
            'correo' => $this->CorreoElectronico,
            'telefono' => [
                'codigo_pais' => $this->CodigoPais,
                'numero' => $this->NumTelefono,
            ],
            'codigo_actividad' => $this->CodigoActividad,
        ];
    }
}
