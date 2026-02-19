<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiKey::with(['tenant', 'user'])->orderByDesc('created_at');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('key', 'like', "%{$search}%");
            });
        }

        $apiKeys = $query->paginate(20)->withQueryString();
        $tenants = Tenant::orderBy('name')->get(['id', 'name']);

        return view('admin.api-keys.index', compact('apiKeys', 'tenants'));
    }

    public function show(int $id)
    {
        $apiKey = ApiKey::with(['tenant', 'user'])->findOrFail($id);

        $usageLogs = ApiUsageLog::where('api_key_id', $id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $usageStats = [
            'total_requests' => ApiUsageLog::where('api_key_id', $id)->count(),
            'today_requests' => ApiUsageLog::where('api_key_id', $id)->whereDate('created_at', today())->count(),
            'avg_response_time' => (int) ApiUsageLog::where('api_key_id', $id)->avg('response_time_ms'),
            'error_count' => ApiUsageLog::where('api_key_id', $id)->where('status_code', '>=', 400)->count(),
        ];

        return view('admin.api-keys.show', compact('apiKey', 'usageLogs', 'usageStats'));
    }

    public function create()
    {
        $tenants = Tenant::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.api-keys.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'tenant_id' => 'required|exists:tenants,id',
            'permissions' => 'nullable|array',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $key = ApiKey::generateKey();
        $secret = ApiKey::generateSecret();

        ApiKey::create([
            'tenant_id' => $request->tenant_id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'key' => $key,
            'secret_hash' => Hash::make($secret),
            'permissions' => $request->permissions,
            'expires_at' => $request->expires_at,
            'is_active' => true,
        ]);

        return redirect()->route('admin.api-keys.index')
            ->with('success', 'API Key creada exitosamente.')
            ->with('new_key', $key)
            ->with('new_secret', $secret);
    }

    public function toggleStatus(int $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->update(['is_active' => !$apiKey->is_active]);
        $status = $apiKey->is_active ? 'activada' : 'desactivada';

        return redirect()->back()->with('success', "API Key {$status}.");
    }

    public function destroy(int $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->delete();

        return redirect()->route('admin.api-keys.index')
            ->with('success', 'API Key eliminada.');
    }
}
