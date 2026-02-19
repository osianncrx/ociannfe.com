<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('tenant');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $usuarios = $query->latest()->paginate(20)->withQueryString();
        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $tenants = Tenant::where('is_active', true)->get();
        return view('admin.usuarios.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'tenant_id' => 'nullable|exists:tenants,id',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $request->tenant_id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($request->role);
        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $usuario)
    {
        if ($usuario->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'No tienes permisos para editar al super administrador.');
        }

        $tenants = Tenant::where('is_active', true)->get();
        return view('admin.usuarios.edit', compact('usuario', 'tenants'));
    }

    public function update(Request $request, User $usuario)
    {
        if ($usuario->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'No tienes permisos para modificar al super administrador.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $usuario->id,
            'is_active' => 'boolean',
        ]);

        $usuario->update($request->only('name', 'email', 'is_active', 'tenant_id'));

        if ($request->filled('role') && !$usuario->isSuperAdmin()) {
            $usuario->syncRoles([$request->role]);
        }

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->isSuperAdmin()) {
            return back()->with('error', 'El super administrador no puede ser eliminado.');
        }

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $usuario->delete();
        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario eliminado.');
    }
}
