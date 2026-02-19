<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $planes = Plan::withCount('subscriptions')->orderBy('sort_order')->get();
        return view('admin.planes.index', compact('planes'));
    }

    public function create()
    {
        return view('admin.planes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:plans',
            'price' => 'required|numeric|min:0',
            'max_empresas' => 'required|integer',
            'max_comprobantes_mes' => 'required|integer',
        ]);

        Plan::create($request->all());
        return redirect()->route('admin.planes.index')->with('success', 'Plan creado exitosamente.');
    }

    public function edit(Plan $plane)
    {
        return view('admin.planes.edit', ['plan' => $plane]);
    }

    public function update(Request $request, Plan $plane)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'max_empresas' => 'required|integer',
            'max_comprobantes_mes' => 'required|integer',
        ]);

        $plane->update($request->all());
        return redirect()->route('admin.planes.index')->with('success', 'Plan actualizado.');
    }

    public function destroy(Plan $plane)
    {
        if ($plane->subscriptions()->exists()) {
            return back()->with('error', 'No se puede eliminar un plan con suscripciones activas.');
        }
        $plane->delete();
        return redirect()->route('admin.planes.index')->with('success', 'Plan eliminado.');
    }
}
