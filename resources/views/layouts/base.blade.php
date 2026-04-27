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
