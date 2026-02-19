@extends('layouts.guest')

@section('title', 'Recuperar contraseña - Ociann Facturacion Electronica C.R.')

@section('content')
<h2 class="h5 fw-bold mb-2">¿Olvidaste tu contraseña?</h2>
<p class="text-muted small mb-4">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

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

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="correo@ejemplo.com">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-paper-plane me-2"></i>Enviar enlace de recuperación
    </button>

    <div class="text-center">
        <a href="{{ route('login') }}" class="text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Volver a iniciar sesión</a>
    </div>
</form>
@endsection
