<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Ociann Facturacion Electronica C.R.')</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --fe-primary: #1a237e;
            --fe-primary-dark: #0d1542;
        }
        .navbar-brand, .btn-primary, .bg-primary-custom { background-color: var(--fe-primary) !important; }
        .navbar { background-color: var(--fe-primary) !important; }
        .text-primary-custom { color: var(--fe-primary) !important; }
        .btn-primary { border-color: var(--fe-primary) !important; }
        .btn-primary:hover { background-color: var(--fe-primary-dark) !important; border-color: var(--fe-primary-dark) !important; }
        .sidebar { background-color: #f8f9fa; min-height: calc(100vh - 56px); }
        .sidebar .nav-link { color: #495057; }
        .sidebar .nav-link:hover { color: var(--fe-primary); }
        .sidebar .nav-link.active { color: var(--fe-primary); font-weight: 600; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                <i class="fas fa-file-invoice me-2"></i>Ociann FE C.R.
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                @auth
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/dashboard') }}"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/empresas') }}"><i class="fas fa-building me-1"></i>Empresas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/comprobantes') }}"><i class="fas fa-receipt me-1"></i>Comprobantes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/recepciones') }}"><i class="fas fa-inbox me-1"></i>Recepciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/declaraciones') }}"><i class="fas fa-file-invoice-dollar me-1"></i>Declaraciones</a>
                    </li>
                    @if(auth()->user()->isAdmin())
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-shield-alt me-1"></i>Admin
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="{{ url('/admin/dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/tenants') }}"><i class="fas fa-database me-2"></i>Tenants</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/planes') }}"><i class="fas fa-list me-2"></i>Planes</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/usuarios') }}"><i class="fas fa-users me-2"></i>Usuarios</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/api-keys') }}"><i class="fas fa-key me-2"></i>API Keys</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/api-logs') }}"><i class="fas fa-chart-bar me-2"></i>API Logs</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/api-docs') }}"><i class="fas fa-book me-2"></i>API Docs</a></li>
                            <li><a class="dropdown-item" href="{{ url('/api/docs') }}" target="_blank"><i class="fas fa-external-link-alt me-2"></i>Swagger UI</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/logs') }}"><i class="fas fa-history me-2"></i>Logs</a></li>
                        </ul>
                    </li>
                    @endif
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>{{ auth()->user()->name ?? 'Usuario' }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="{{ url('/profile') }}"><i class="fas fa-user me-2"></i>Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
                @else
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Iniciar sesión</a></li>
                    @if(Route::has('register'))
                    <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Registrarse</a></li>
                    @endif
                </ul>
                @endauth
            </div>
        </div>
    </nav>

    @auth
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-md-3 col-lg-2 sidebar d-none d-md-block py-3">
                <nav class="nav flex-column px-3">
                    <a class="nav-link py-2" href="{{ url('/dashboard') }}"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                    <a class="nav-link py-2" href="{{ url('/empresas') }}"><i class="fas fa-building me-2"></i>Empresas</a>
                    <a class="nav-link py-2" href="{{ url('/comprobantes') }}"><i class="fas fa-receipt me-2"></i>Comprobantes</a>
                    <a class="nav-link py-2" href="{{ url('/recepciones') }}"><i class="fas fa-inbox me-2"></i>Recepciones</a>
                    <a class="nav-link py-2" href="{{ url('/declaraciones') }}"><i class="fas fa-file-invoice-dollar me-2"></i>Declaraciones</a>
                </nav>
            </div>
            <div class="col-md-9 col-lg-10">
                <main class="p-4">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
    @else
    <main class="container py-4">
        @yield('content')
    </main>
    @endauth

    <footer class="bg-light py-3 mt-auto">
        <div class="container">
            <p class="text-muted small mb-0 text-center">&copy; {{ date('Y') }} Ociann Facturacion Electronica C.R.. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
