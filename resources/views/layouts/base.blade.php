<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ data_get($branding ?? [], 'app_name', config('app.name')) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('vendor/caronte/css/custom.css') }}" rel="stylesheet">
    <style>
        :root {
            --caronte-accent: {{ data_get($branding ?? [], 'accent', '#0f766e') }};
            --caronte-accent-dark: #115e59;
            --caronte-surface: #f8fafc;
            --caronte-panel: #ffffff;
            --caronte-border: #d9e2ec;
            --caronte-text: #102a43;
            --caronte-muted: #486581;
        }

        body {
            margin: 0;
            font-family: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, rgba(15, 118, 110, 0.14), transparent 32%),
                linear-gradient(180deg, #eef5f3 0%, #f8fafc 45%, #f4f7fb 100%);
            color: var(--caronte-text);
        }

        .caronte-shell,
        .caronte-shell__content {
            min-height: 100vh;
        }

        .caronte-auth-shell .caronte-shell__content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .caronte-auth {
            width: 100%;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem 1rem;
        }

        .caronte-auth-shell .caronte-flash + .caronte-auth {
            min-height: calc(100vh - 8rem);
        }

        .caronte-auth__panel,
        .caronte-flash {
            width: min(100% - 2rem, 540px);
        }

        .caronte-flash {
            margin: 1rem auto 0;
        }

        .caronte-card {
            background: var(--caronte-panel);
            border: 1px solid rgba(217, 226, 236, 0.9);
            border-radius: 8px;
            box-shadow: 0 24px 55px rgba(15, 23, 42, 0.08);
            padding: 1.5rem;
        }

        .caronte-kicker {
            display: inline-flex;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.1);
            color: var(--caronte-accent-dark);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .caronte-title {
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.05;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }

        .caronte-copy {
            color: var(--caronte-muted);
            max-width: 64ch;
        }

        .caronte-form {
            display: grid;
            gap: 1rem;
        }

        .caronte-form .form-label {
            font-weight: 700;
            margin-bottom: 0.45rem;
        }

        .caronte-form .form-control,
        .caronte-form .form-select,
        .caronte-form textarea {
            border-radius: 8px;
            border-color: var(--caronte-border);
            background: #fbfdff;
            padding: 0.85rem 1rem;
        }

        .caronte-btn-primary,
        .caronte-btn-secondary {
            border-radius: 8px;
            padding: 0.85rem 1rem;
            font-weight: 700;
        }

        .caronte-btn-primary {
            background: var(--caronte-accent);
            color: #fff;
            border: 1px solid var(--caronte-accent);
        }

        .caronte-card__footer {
            margin-top: 1rem;
            text-align: center;
        }
    </style>
</head>

<body class="@yield('body_class', 'caronte-auth-shell')">
    <div class="caronte-shell">
        <main class="caronte-shell__content">
            @include('caronte::partials.messages')
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
