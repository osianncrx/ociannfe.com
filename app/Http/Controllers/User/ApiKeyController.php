<?php
declare(strict_types=1);
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyController extends Controller
{
    public function index()
    {
        $apiKeys = ApiKey::where('tenant_id', auth()->user()->tenant_id)
            ->orderByDesc('created_at')
            ->get();
        return view('user.api-keys.index', compact('apiKeys'));
    }

    public function create()
    {
        return view('user.api-keys.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $key = ApiKey::generateKey();
        $secret = ApiKey::generateSecret();

        ApiKey::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'key' => $key,
            'secret_hash' => Hash::make($secret),
            'is_active' => true,
        ]);

        return redirect()->route('api-keys.index')
            ->with('success', 'API Key creada exitosamente.')
            ->with('new_key', $key)
            ->with('new_secret', $secret);
    }

    public function destroy(int $id)
    {
        $apiKey = ApiKey::where('tenant_id', auth()->user()->tenant_id)
            ->where('id', $id)
            ->firstOrFail();
        $apiKey->delete();
        return redirect()->route('api-keys.index')->with('success', 'API Key eliminada.');
    }
}
