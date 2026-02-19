@extends('layouts.guest')

@section('title', 'Restablecer contraseña - Ociann Facturacion Electronica C.R.')

@section('content')
<h2 class="h5 fw-bold mb-4">Restablecer contraseña</h2>

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

<form method="POST" action="{{ route('password.store') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control bg-light" id="email" name="email" value="{{ old('email', $email) }}" readonly>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Nueva contraseña</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autofocus autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-key me-2"></i>Restablecer contraseña
    </button>

    <div class="text-center">
        <a href="{{ route('login') }}" class="text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Volver a iniciar sesión</a>
    </div>
</form>
@endsection
