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
        :root { --fe-primary: #1a237e; }
        .btn-primary { background-color: var(--fe-primary) !important; border-color: var(--fe-primary) !important; }
        .btn-primary:hover { background-color: #0d1542 !important; border-color: #0d1542 !important; }
        .text-primary-custom { color: var(--fe-primary) !important; }
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%); }
        .auth-card { max-width: 420px; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); border-radius: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-6">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold text-primary-custom mb-2">
                        <i class="fas fa-file-invoice me-2"></i>Ociann Facturacion Electronica C.R.
                    </h1>
                    <p class="text-muted small">Sistema de facturación electrónica de Costa Rica</p>
                </div>
                <div class="card auth-card">
                    <div class="card-body p-4">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
