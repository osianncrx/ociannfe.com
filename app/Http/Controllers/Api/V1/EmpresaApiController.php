<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmpresaResource;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'Provincia' => 'nullable|string|regex:/^[1-7]$/',
            'Canton' => 'nullable|string|regex:/^[0-9]{2}$/',
            'Distrito' => 'nullable|string|regex:/^[0-9]{2}$/',
        ]);

        $tenantId = $this->getTenantId($request);
        $data = $request->all();
        $data['tenant_id'] = $tenantId;
        $data['id_cliente'] = (string) $tenantId;
        if (empty($data['CodigoActividad'] ?? null)) {
            $data['CodigoActividad'] = $this->fetchCodigoActividadFromHacienda((string) ($data['Numero'] ?? '')) ?? null;
        }

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

        $request->validate([
            'Provincia' => 'nullable|string|regex:/^[1-7]$/',
            'Canton' => 'nullable|string|regex:/^[0-9]{2}$/',
            'Distrito' => 'nullable|string|regex:/^[0-9]{2}$/',
        ]);

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

    private function fetchCodigoActividadFromHacienda(string $identificacion): ?string
    {
        $identificacion = preg_replace('/\D+/', '', trim($identificacion)) ?? '';
        if ($identificacion === '') {
            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://api.hacienda.go.cr/fe/ae', [
                'identificacion' => $identificacion,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $actividades = collect($response->json('actividades', []))
                ->where('estado', 'A')
                ->values();

            if ($actividades->isEmpty()) {
                return null;
            }

            foreach ($actividades as $actividad) {
                foreach (($actividad['ciiu3'] ?? []) as $ciiu) {
                    $code = preg_replace('/\D+/', '', (string) ($ciiu['codigo'] ?? '')) ?? '';
                    if (strlen($code) === 6) {
                        return $code;
                    }
                }
            }

            $code = preg_replace('/\D+/', '', (string) ($actividades[0]['codigo'] ?? '')) ?? '';
            return $code !== '' ? str_pad(substr($code, 0, 6), 6, '0', STR_PAD_RIGHT) : null;
        } catch (\Throwable $e) {
            Log::warning('No se pudo autocompletar CodigoActividad desde Hacienda (API): ' . $e->getMessage());
            return null;
        }
    }
}
