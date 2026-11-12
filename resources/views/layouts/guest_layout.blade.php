<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('titulo', 'HealthWare')</title>

    {{-- Inicialización temprana del tema --}}
    <script src="{{ asset('js/global-scripts.js') }}"></script>

    {{-- Framework y sistema de diseño (mismo que el layout interno) --}}
    <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

    {{-- Estilos particulares de cada vista --}}
    @stack('estilos')
</head>

<body class="auth-shell">

    {{-- Páginas de invitado: comparten la composición del layout de acceso --}}
    <header class="auth-topbar">
        <a href="{{ url('/') }}" class="app-brand" aria-label="HealthWare">
            <span class="material-symbols-outlined app-brand-icon">health_and_safety</span>
            <span class="app-brand-name">HealthWare</span>
        </a>

        <div class="auth-topbar-end">
            <button
                type="button"
                class="btn btn-icon"
                id="themeToggleBtn"
                title="Cambiar tema"
                aria-label="Cambiar tema"
                aria-pressed="false">
                <span class="material-symbols-outlined" id="themeIcon">dark_mode</span>
            </button>
        </div>
    </header>

    <main class="auth-main">
        @yield('contenido')
    </main>

    <footer class="auth-footer">
        &copy; {{ date('Y') }} HealthWare · Sistema de Gestión Clínica
    </footer>

    @include('components.toast')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>

    @stack('scripts')

</body>
</html>
