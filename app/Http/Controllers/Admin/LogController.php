<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('fe_monolog')->orderByDesc('time');
        
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('buscar')) {
            $query->where('message', 'like', "%{$request->buscar}%");
        }
        
        $logs = $query->paginate(50)->withQueryString();
        return view('admin.logs.index', compact('logs'));
    }
}
