<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Docs — Ociann FE C.R.</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #fafafa; }
        .topbar-wrapper { display: flex; align-items: center; }
        .swagger-ui .topbar { background: #1a237e; padding: 10px 20px; }
        .swagger-ui .topbar .download-url-wrapper { display: none; }
        .swagger-ui .info .title { font-size: 2rem; }
        .custom-header {
            background: #1a237e;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .custom-header a { color: #fff; text-decoration: none; }
        .custom-header .brand { font-size: 1.2rem; font-weight: 700; }
        .custom-header .links a { margin-left: 20px; opacity: 0.85; font-size: 0.9rem; }
        .custom-header .links a:hover { opacity: 1; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <div class="custom-header">
        <div>
            <a href="{{ url('/') }}" class="brand">
                <svg style="width:20px;height:20px;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                </svg>
                Ociann FE C.R. — API
            </a>
        </div>
        <div class="links">
            <a href="{{ url('/api/openapi.json') }}" target="_blank">OpenAPI JSON</a>
            @auth
                <a href="{{ url('/dashboard') }}">Dashboard</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ url('/admin/dashboard') }}">Admin</a>
                @endif
            @else
                <a href="{{ url('/login') }}">Iniciar sesión</a>
            @endauth
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: "{{ url('/api/openapi.json') }}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset
            ],
            layout: "BaseLayout",
            defaultModelsExpandDepth: 2,
            defaultModelExpandDepth: 2,
            docExpansion: "list",
            filter: true,
            showExtensions: true,
            showCommonExtensions: true,
            tryItOutEnabled: true,
            persistAuthorization: true,
        });
    </script>
</body>
</html>
