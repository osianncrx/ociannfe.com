@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-users me-2"></i>Gestión de Usuarios</h2>
    <a href="{{ url('/admin/usuarios/create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nuevo Usuario
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ url('/admin/usuarios') }}" class="row g-3 mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2">Buscar</button>
                <a href="{{ url('/admin/usuarios') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Tenant</th>
                        <th>Rol</th>
                        <th class="text-center">Activo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr>
                        <td>{{ $usuario->id }}</td>
                        <td class="fw-semibold">{{ $usuario->name }}</td>
                        <td>{{ $usuario->email }}</td>
                        <td>{{ $usuario->tenant->name ?? 'N/A' }}</td>
                        <td>
                            @if($usuario->isSuperAdmin())
                                <span class="badge bg-dark"><i class="fas fa-crown me-1"></i>Super Admin</span>
                            @elseif($usuario->hasRole('admin'))
                                <span class="badge bg-danger">Admin</span>
                            @else
                                <span class="badge bg-secondary">Usuario</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($usuario->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($usuario->isSuperAdmin() && !auth()->user()->isSuperAdmin())
                                <span class="badge bg-light text-muted"><i class="fas fa-lock me-1"></i>Protegido</span>
                            @else
                                <a href="{{ url('/admin/usuarios/' . $usuario->id . '/edit') }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @unless($usuario->isSuperAdmin())
                                <form method="POST" action="{{ url('/admin/usuarios/' . $usuario->id) }}" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este usuario?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endunless
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se encontraron usuarios</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $usuarios->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
