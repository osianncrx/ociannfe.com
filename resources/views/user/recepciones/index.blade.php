@extends('layouts.app')

@section('title', 'Recepciones')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-inbox me-2"></i>Recepciones</h2>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Clave</th>
                        <th>Empresa</th>
                        <th>Estado</th>
                        <th>Mensaje</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recepciones ?? [] as $recepcion)
                    <tr>
                        <td><code title="{{ $recepcion->clave }}">{{ Str::limit($recepcion->clave, 25) }}</code></td>
                        <td>{{ $recepcion->empresa->nombre ?? 'N/A' }}</td>
                        <td><span class="badge bg-{{ $recepcion->estado_badge ?? 'secondary' }}">{{ $recepcion->estado_texto ?? $recepcion->estado }}</span></td>
                        <td>{{ Str::limit($recepcion->mensaje ?? 'Sin mensaje', 50) }}</td>
                        <td class="text-end">
                            <a href="{{ route('recepciones.show', $recepcion) }}" class="btn btn-sm btn-outline-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No hay recepciones registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(isset($recepciones) && $recepciones->hasPages())
    <div class="card-footer bg-white">
        {{ $recepciones->links() }}
    </div>
    @endif
</div>
@endsection
