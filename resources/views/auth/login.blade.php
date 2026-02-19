@extends('layouts.guest')

@section('title', 'Iniciar sesión - Ociann Facturacion Electronica C.R.')

@section('content')
<h2 class="h5 fw-bold mb-4">Iniciar sesión</h2>

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

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="correo@ejemplo.com">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Recordarme</label>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-sign-in-alt me-2"></i>Iniciar sesión
    </button>

    <div class="text-center">
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-decoration-none small">¿Olvidaste tu contraseña?</a>
        @endif
        @if (Route::has('register'))
            <span class="text-muted mx-1">|</span>
            <a href="{{ route('register') }}" class="text-decoration-none small">Crear cuenta</a>
        @endif
    </div>
</form>
@endsection
