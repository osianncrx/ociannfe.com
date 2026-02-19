<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmpresaResource;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmpresaApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $this->getTenantId($request);
        $empresas = Empresa::where('tenant_id', $tenantId)->orderByDesc('id_empresa')->paginate(20);
        return EmpresaResource::collection($empresas);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cedula' => 'required|string|max:12',
            'Nombre' => 'required|string|max:255',
            'Tipo' => 'required|string|max:2',
            'Numero' => 'required|string|max:12',
            'id_ambiente' => 'required|integer|in:1,2',
            'usuario_mh' => 'required|string',
            'contra_mh' => 'required|string',
            'pin_llave' => 'required|string',
            'llave_criptografica' => 'required',
            'sucursal' => 'nullable|string|size:3|regex:/^[0-9]{3}$/',
        ]);

        $tenantId = $this->getTenantId($request);
        $data = $request->all();
        $data['tenant_id'] = $tenantId;
        $data['id_cliente'] = (string) $tenantId;

        if ($request->filled('sucursal')) {
            $exists = Empresa::where('tenant_id', $tenantId)
                ->where('cedula', $request->cedula)
                ->where('id_ambiente', $request->id_ambiente)
                ->where('sucursal', $request->sucursal)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Ya existe una empresa con esa cédula, ambiente y sucursal.',
                    'errors' => ['sucursal' => ['La sucursal ya está en uso para esta cédula y ambiente.']],
                ], 422);
            }
        } else {
            $data['sucursal'] = Empresa::autoAssignSucursal(
                $tenantId,
                $request->cedula,
                (int) $request->id_ambiente
            );
        }

        if ($request->hasFile('llave_criptografica')) {
            $data['llave_criptografica'] = file_get_contents($request->file('llave_criptografica')->getRealPath());
        } elseif (is_string($request->llave_criptografica)) {
            $data['llave_criptografica'] = base64_decode($request->llave_criptografica);
        }

        $empresa = Empresa::create($data);
        return response()->json(new EmpresaResource($empresa), 201);
    }

    public function show(Request $request, int $id): EmpresaResource
    {
        $empresa = Empresa::where('tenant_id', $this->getTenantId($request))
            ->where('id_empresa', $id)->firstOrFail();
        return new EmpresaResource($empresa);
    }

    public function update(Request $request, int $id): EmpresaResource
    {
        $empresa = Empresa::where('tenant_id', $this->getTenantId($request))
            ->where('id_empresa', $id)->firstOrFail();

        $data = $request->only([
            'Nombre', 'CorreoElectronico', 'Provincia', 'Canton', 'Distrito',
            'OtrasSenas', 'CodigoActividad', 'NombreComercial',
        ]);

        if ($request->filled('usuario_mh')) $data['usuario_mh'] = $request->usuario_mh;
        if ($request->filled('contra_mh')) $data['contra_mh'] = $request->contra_mh;
        if ($request->filled('pin_llave')) $data['pin_llave'] = $request->pin_llave;

        $empresa->update($data);
        return new EmpresaResource($empresa);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresa = Empresa::where('tenant_id', $this->getTenantId($request))
            ->where('id_empresa', $id)->firstOrFail();
        $empresa->delete();
        return response()->json(['message' => 'Empresa eliminada.']);
    }

    private function getTenantId(Request $request): int
    {
        if (app()->bound('current_tenant')) {
            return app('current_tenant')->id;
        }
        return $request->user()->tenant_id;
    }
}
