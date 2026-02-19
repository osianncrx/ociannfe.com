@extends('layouts.app')

@section('title', 'Empresas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-building me-2"></i>Empresas</h2>
    <a href="{{ route('empresas.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nueva Empresa
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Sucursal</th>
                        <th>Ambiente</th>
                        <th>Email</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($empresas as $empresa)
                    <tr>
                        <td>{{ $empresa->id_empresa }}</td>
                        <td>{{ $empresa->Nombre }}</td>
                        <td><code>{{ $empresa->cedula }}</code></td>
                        <td><span class="badge bg-info text-dark">{{ $empresa->sucursal }}</span></td>
                        <td>
                            @if($empresa->id_ambiente == 1)
                                <span class="badge bg-warning text-dark">Staging</span>
                            @elseif($empresa->id_ambiente == 2)
                                <span class="badge bg-success">Producción</span>
                            @else
                                <span class="badge bg-secondary">{{ $empresa->id_ambiente }}</span>
                            @endif
                        </td>
                        <td>{{ $empresa->CorreoElectronico }}</td>
                        <td class="text-end">
                            <a href="{{ route('empresas.show', $empresa) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('empresas.edit', $empresa) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('empresas.destroy', $empresa) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta empresa?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No hay empresas registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($empresas->hasPages())
    <div class="card-footer bg-white">
        {{ $empresas->links() }}
    </div>
    @endif
</div>
@endsection
