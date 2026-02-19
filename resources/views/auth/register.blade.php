@extends('layouts.guest')

@section('title', 'Registrarse - Ociann Facturacion Electronica C.R.')

@section('content')
<h2 class="h5 fw-bold mb-4">Crear cuenta</h2>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Nombre completo</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="correo@ejemplo.com">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="company_name" class="form-label">Nombre de la empresa</label>
        <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name') }}" required>
        @error('company_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if(isset($plans) && $plans->isNotEmpty())
    <div class="mb-3">
        <label class="form-label">Plan</label>
        <div class="border rounded p-3">
            @foreach($plans as $plan)
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="plan_id" id="plan_{{ $plan->id }}" value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'checked' : '' }}>
                <label class="form-check-label" for="plan_{{ $plan->id }}">
                    <strong>{{ $plan->name ?? $plan->nombre ?? 'Plan' }}</strong>
                    @if(!empty($plan->description) || !empty($plan->descripcion))
                        <span class="text-muted small">— {{ $plan->description ?? $plan->descripcion }}</span>
                    @endif
                    @if(!empty($plan->price) || !empty($plan->precio))
                        <span class="badge bg-primary ms-1">{{ $plan->price ?? $plan->precio }}</span>
                    @endif
                </label>
            </div>
            @endforeach
        </div>
        @error('plan_id')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    @endif

    <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-user-plus me-2"></i>Registrarse
    </button>

    <div class="text-center">
        <a href="{{ route('login') }}" class="text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Volver a iniciar sesión</a>
    </div>
</form>
@endsection
