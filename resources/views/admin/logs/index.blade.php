@extends('layouts.app')

@section('title', 'Logs del Sistema')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="fas fa-history me-2"></i>Logs del Sistema</h2>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ url('/admin/logs') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <select name="level" class="form-select">
                    <option value="">Todos los niveles</option>
                    <option value="100" {{ request('level') == '100' ? 'selected' : '' }}>Debug (100)</option>
                    <option value="200" {{ request('level') == '200' ? 'selected' : '' }}>Info (200)</option>
                    <option value="250" {{ request('level') == '250' ? 'selected' : '' }}>Notice (250)</option>
                    <option value="300" {{ request('level') == '300' ? 'selected' : '' }}>Warning (300)</option>
                    <option value="400" {{ request('level') == '400' ? 'selected' : '' }}>Error (400)</option>
                </select>
            </div>
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Buscar en mensajes..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                <a href="{{ url('/admin/logs') }}" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 180px;">Tiempo</th>
                        <th style="width: 120px;">Canal</th>
                        <th style="width: 100px;">Nivel</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap">
                            {{ \Carbon\Carbon::createFromTimestamp($log->timestamp)->format('d/m/Y H:i:s') }}
                        </td>
                        <td>{{ $log->channel ?? 'N/A' }}</td>
                        <td>
                            @switch($log->level)
                                @case(100)
                                    <span class="badge bg-secondary">Debug</span>
                                    @break
                                @case(200)
                                    <span class="badge bg-info text-dark">Info</span>
                                    @break
                                @case(250)
                                    <span class="badge bg-primary">Notice</span>
                                    @break
                                @case(300)
                                    <span class="badge bg-warning text-dark">Warning</span>
                                    @break
                                @case(400)
                                    <span class="badge bg-danger">Error</span>
                                    @break
                                @default
                                    <span class="badge bg-dark">{{ $log->level }}</span>
                            @endswitch
                        </td>
                        <td>
                            <span class="text-break">{{ Str::limit($log->message, 200) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No se encontraron registros</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center">
            {{ $logs->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
