<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use Illuminate\Http\Request;

class ApiUsageLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiUsageLog::with(['apiKey', 'user', 'tenant'])
            ->orderByDesc('created_at');

        if ($request->filled('api_key_id')) {
            $query->where('api_key_id', $request->api_key_id);
        }
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }
        if ($request->filled('status_code')) {
            if ($request->status_code === 'error') {
                $query->where('status_code', '>=', 400);
            } elseif ($request->status_code === 'success') {
                $query->where('status_code', '<', 400);
            }
        }
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }
        if ($request->filled('search')) {
            $query->where('endpoint', 'like', "%{$request->search}%");
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        $apiKeys = ApiKey::orderBy('name')->get(['id', 'name', 'key']);

        $stats = [
            'total_today' => ApiUsageLog::whereDate('created_at', today())->count(),
            'errors_today' => ApiUsageLog::whereDate('created_at', today())->where('status_code', '>=', 400)->count(),
            'avg_response' => (int) ApiUsageLog::whereDate('created_at', today())->avg('response_time_ms'),
            'unique_ips' => ApiUsageLog::whereDate('created_at', today())->distinct('ip_address')->count('ip_address'),
        ];

        return view('admin.api-logs.index', compact('logs', 'apiKeys', 'stats'));
    }
}
