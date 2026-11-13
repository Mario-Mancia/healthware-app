<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('titulo', 'HealthWare')</title>

    {{-- Tema y estado del menú lateral, antes de pintar (evita parpadeos) --}}
    <script src="{{ asset('js/global-scripts.js') }}"></script>
    <script>
        (function () {
            try {
                if (window.matchMedia('(min-width: 992px)').matches && localStorage.getItem('hw-nav-collapsed') === '1') {
                    document.documentElement.classList.add('hw-nav-collapsed');
                }
            } catch (e) { /* almacenamiento no disponible */ }
        })();
    </script>

    {{-- Framework y sistema de diseño --}}
    <link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">

    {{-- Material Symbols --}}
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">

    {{-- Estilos de librerías y particulares de cada vista --}}
    @stack('estilos')

    {{-- Componentes de vista: se cargan al final para prevalecer sobre las librerías --}}
    <link rel="stylesheet" href="{{ asset('css/hw-components.css') }}">
</head>

<body class="app-body">

@php
    /*
    |--------------------------------------------------------------------------
    | Usuario autenticado
    |--------------------------------------------------------------------------
    */
    $usuario = Auth::user();

    $nombreUsuario = trim(($usuario->nombres ?? '') . ' ' . ($usuario->apellidos ?? ''));
    $nombreUsuario = $nombreUsuario ?: 'Usuario';

    $iniciales = mb_strtoupper(
        mb_substr($usuario->nombres ?? 'U', 0, 1) .
        mb_substr($usuario->apellidos ?? '', 0, 1)
    );

    $rolUsuario = mb_strtolower(trim($usuario->rol->rol ?? ''));

    // Foto de perfil (null si el usuario no tiene una)
    $fotoUsuario = $usuario?->fotoUrl();

    /*
    |--------------------------------------------------------------------------
    | Menú (única fuente: layouts/partials/menu_config.php)
    |--------------------------------------------------------------------------
    */
    $configuracionMenu = require resource_path('views/layouts/partials/menu_config.php');
    $menuModulos = $configuracionMenu['modulos'];

    $puedeVer = function (array $enlace) use ($rolUsuario) {
        return \Illuminate\Support\Facades\Route::has($enlace['ruta'])
            && in_array($rolUsuario, $enlace['roles'], true);
    };

    $accesosRapidos = array_values(array_filter($configuracionMenu['accesos_rapidos'], $puedeVer));

    /*
    |--------------------------------------------------------------------------
    | Notificaciones del usuario (no rompe la página si el modelo no existe)
    |--------------------------------------------------------------------------
    */
    $notifPendientes = 0;
    $notifRecientes = collect();

    $claseNotificacion = collect(['\\App\\Models\\Notificacion', '\\App\\Models\\Notificaciones'])
        ->first(fn ($clase) => class_exists($clase));

    if ($claseNotificacion && $usuario) {
        try {
            $consultaNotificaciones = $claseNotificacion::query()->where('id_usuario', $usuario->id);
            $notifPendientes = (clone $consultaNotificaciones)->whereNull('leido_en')->count();
            $notifRecientes = (clone $consultaNotificaciones)->orderByDesc('created_at')->limit(5)->get();
        } catch (\Throwable $e) {
            $notifPendientes = 0;
            $notifRecientes = collect();
        }
    }

    $iconoNotificacion = function ($notificacion) {
        $tipo = mb_strtolower((string) ($notificacion->tipo ?? ''));
        return match (true) {
            str_contains($tipo, 'referencia') => 'swap_horiz',
            str_contains($tipo, 'tarea') => 'task_alt',
            str_contains($tipo, 'clinica') => 'clinical_notes',
            str_contains($tipo, 'cita') => 'event',
            str_contains($tipo, 'factur') || str_contains($tipo, 'pago') => 'receipt_long',
            str_contains($tipo, 'receta') => 'medication',
            str_contains($tipo, 'anuncio') => 'campaign',
            str_contains($tipo, 'alerta') || str_contains($tipo, 'urgente') => 'warning',
            default => 'notifications',
        };
    };
@endphp

{{-- =====================================================
     BARRA SUPERIOR: menú · marca centrada · acciones
====================================================== --}}
<header class="app-topbar">

    {{-- Izquierda: botón del menú lateral + menús extra --}}
    <div class="app-topbar-start">

        <button
            type="button"
            class="btn btn-icon app-menu-toggle"
            id="sidebarToggle"
            aria-controls="appSidebar"
            aria-expanded="true"
            aria-label="Mostrar u ocultar el menú de navegación">
            <span class="material-symbols-outlined">menu</span>
        </button>

        <nav class="app-topnav" aria-label="Accesos de la barra superior">

            <a href="{{ route('home') }}"
               class="app-topnav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <span class="material-symbols-outlined">home</span>
                Inicio
            </a>
        </nav>
    </div>

    {{-- Centro: nombre del sistema --}}
    <div class="app-topbar-center">
        <a href="{{ route('home') }}" class="app-brand" aria-label="HealthWare, ir al inicio">
            <span class="material-symbols-outlined app-brand-icon">health_and_safety</span>
            <span class="app-brand-name">HealthWare</span>
        </a>
    </div>

    {{-- Derecha: notificaciones · tema · usuario --}}
    <div class="app-topbar-end">

        @if(\Illuminate\Support\Facades\Route::has('notificaciones'))
            <div class="dropdown app-notif">
                <button type="button"
                        class="btn btn-icon app-notif-button"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false"
                        aria-label="Notificaciones{{ $notifPendientes ? ': ' . $notifPendientes . ' sin leer' : '' }}"
                        title="Notificaciones">
                    <span class="material-symbols-outlined">notifications</span>
                    @if($notifPendientes > 0)
                        <span class="app-notif-badge">{{ $notifPendientes > 99 ? '99+' : $notifPendientes }}</span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-end app-notif-menu">

                    <div class="app-notif-header">
                        <h2>Notificaciones</h2>
                        <span>{{ $notifPendientes }} sin leer</span>
                    </div>

                    <div class="app-notif-list">
                        @forelse($notifRecientes as $notificacion)
                            @php $sinLeer = empty($notificacion->leido_en); @endphp
                            <a href="{{ $notificacion->enlace ?: route('notificaciones') }}"
                               class="app-notif-item {{ $sinLeer ? 'is-unread' : '' }}">
                                <span class="app-notif-icon">
                                    <span class="material-symbols-outlined">{{ $iconoNotificacion($notificacion) }}</span>
                                </span>
                                <span class="app-notif-body">
                                    <span class="app-notif-title">{{ $notificacion->titulo ?? 'Notificación' }}</span>
                                    <span class="app-notif-text">{{ $notificacion->mensaje ?? '' }}</span>
                                    <span class="app-notif-time">
                                        {{ $notificacion->created_at ? \Illuminate\Support\Carbon::parse($notificacion->created_at)->locale('es')->diffForHumans() : '' }}
                                    </span>
                                </span>
                                @if($sinLeer)
                                    <span class="app-notif-dot" aria-hidden="true"></span>
                                @endif
                            </a>
                        @empty
                            <div class="app-notif-empty">
                                <span class="material-symbols-outlined">notifications_off</span>
                                No tienes notificaciones recientes.
                            </div>
                        @endforelse
                    </div>

                    <a href="{{ route('notificaciones') }}" class="app-notif-footer">Ver todas las notificaciones</a>
                </div>
            </div>
        @endif

        {{-- Cambio de tema --}}
        <button
            type="button"
            class="btn btn-icon app-theme-button"
            id="themeToggleBtn"
            aria-label="Cambiar tema"
            aria-pressed="false"
            title="Cambiar tema">
            <span class="material-symbols-outlined" id="themeIcon">dark_mode</span>
        </button>

        {{-- Menú de usuario --}}
        <div class="dropdown app-user-dropdown">

            <button
                type="button"
                class="app-user-trigger"
                data-bs-toggle="dropdown"
                data-bs-auto-close="true"
                aria-expanded="false"
                aria-label="Abrir menú de usuario">

                <span class="app-user-avatar">@if($fotoUsuario)<img src="{{ $fotoUsuario }}" alt="">@else{{ $iniciales ?: 'U' }}@endif</span>

                <span class="app-user-summary d-none d-md-flex">
                    <span class="app-user-name">{{ $nombreUsuario }}</span>
                    <span class="app-user-role">{{ $usuario->rol->rol ?? 'Usuario' }}</span>
                </span>

                <span class="material-symbols-outlined app-user-chevron d-none d-md-inline">expand_more</span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end app-user-menu">

                <li>
                    <div class="app-user-menu-header">
                        <span class="app-user-avatar app-user-avatar-large">@if($fotoUsuario)<img src="{{ $fotoUsuario }}" alt="">@else{{ $iniciales ?: 'U' }}@endif</span>
                        <div class="app-user-menu-identity">
                            <span class="app-user-menu-name">{{ $nombreUsuario }}</span>
                            <span class="app-user-menu-email">{{ $usuario->correo ?? '' }}</span>
                        </div>
                    </div>
                </li>

                <li>
                    <div class="app-user-role-badge">
                        <span class="material-symbols-outlined">verified_user</span>
                        {{ $usuario->rol->rol ?? 'Usuario' }}
                    </div>
                </li>

                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('perfil') }}">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        Mi perfil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('contrasenha.cambiar') }}">
                        <span class="material-symbols-outlined">lock_reset</span>
                        Cambiar contraseña
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>

                <li>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item app-logout-button">
                            <span class="material-symbols-outlined">logout</span>
                            Cerrar sesión
                        </button>
                    </form>
                </li>

            </ul>
        </div>

    </div>

</header>


{{-- =====================================================
     ESPACIO DE TRABAJO
====================================================== --}}
<div class="app-workspace">

    {{-- MENÚ LATERAL (se oculta y despliega con el botón de la barra superior) --}}
    <aside class="app-sidebar" id="appSidebar" aria-label="Navegación principal">

        <div class="app-sidebar-body">

            <div class="sidebar-heading">
                <span class="sidebar-section-label">Menú principal</span>
            </div>

            <nav class="app-navigation">

                <a href="{{ route('home') }}"
                   class="sidebar-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    <span class="material-symbols-outlined sidebar-icon">home</span>
                    <span>Inicio</span>
                </a>

                @if(\Illuminate\Support\Facades\Route::has('dashboard'))
                    <a href="{{ route('dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="material-symbols-outlined sidebar-icon">dashboard</span>
                        <span>Dashboard</span>
                    </a>
                @endif

                @foreach ($menuModulos as $menuModulo)

                    @php
                        // Solo enlaces con ruta existente y permitidos para el rol
                        $menuEnlaces = array_values(array_filter($menuModulo['enlaces'], $puedeVer));

                        $menuActivo = false;

                        foreach ($menuEnlaces as $menuEnlace) {
                            $patrones = [$menuEnlace['ruta'], $menuEnlace['ruta'] . '.*'];

                            foreach ($menuEnlace['rutas_activas'] ?? [] as $rutaActiva) {
                                $patrones[] = $rutaActiva;
                                $patrones[] = $rutaActiva . '.*';
                            }

                            if (request()->routeIs(...$patrones)) {
                                $menuActivo = true;
                                break;
                            }
                        }
                    @endphp

                    @continue($menuEnlaces === [])

                    <div class="sidebar-module">

                        <button
                            type="button"
                            class="sidebar-link sidebar-group-toggle {{ $menuActivo ? 'has-active' : 'collapsed' }}"
                            data-bs-toggle="collapse"
                            data-bs-target="#modulo-{{ $menuModulo['id'] }}"
                            aria-expanded="{{ $menuActivo ? 'true' : 'false' }}"
                            aria-controls="modulo-{{ $menuModulo['id'] }}">

                            <span class="material-symbols-outlined sidebar-icon">{{ $menuModulo['icono'] }}</span>
                            <span class="sidebar-link-label">{{ $menuModulo['titulo'] }}</span>
                            <span class="material-symbols-outlined sidebar-chevron">expand_more</span>
                        </button>

                        <div class="collapse sidebar-submenu {{ $menuActivo ? 'show' : '' }}"
                             id="modulo-{{ $menuModulo['id'] }}">

                            @foreach ($menuEnlaces as $menuEnlace)

                                @php
                                    $patronesEnlace = [$menuEnlace['ruta'], $menuEnlace['ruta'] . '.*'];

                                    foreach ($menuEnlace['rutas_activas'] ?? [] as $rutaActiva) {
                                        $patronesEnlace[] = $rutaActiva;
                                        $patronesEnlace[] = $rutaActiva . '.*';
                                    }

                                    $enlaceActivo = request()->routeIs(...$patronesEnlace);
                                @endphp

                                <a href="{{ route($menuEnlace['ruta']) }}"
                                   class="sidebar-link sidebar-sublink {{ $enlaceActivo ? 'active' : '' }}">
                                    <span class="material-symbols-outlined sidebar-icon">{{ $menuEnlace['icono'] }}</span>
                                    <span>{{ $menuEnlace['etiqueta'] }}</span>
                                </a>

                            @endforeach

                        </div>

                    </div>

                @endforeach

            </nav>

            <footer class="app-sidebar-footer">
                <span class="app-sidebar-footer-brand">HealthWare</span>
                <span class="app-sidebar-footer-version">Sistema de Gestión Clínica · v1.0.0</span>
                <span class="app-sidebar-copyright">&copy; {{ date('Y') }} HealthWare</span>
            </footer>

        </div>

    </aside>

    <div class="app-sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    {{-- PANTALLA INTERNA --}}
    <main class="app-screen">
        <div class="app-screen-content">
            @yield('contenido')
        </div>
    </main>

</div>


{{-- COMPONENTES REUTILIZABLES --}}
@include('components.toast')


{{-- SCRIPTS --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/toast.js') }}"></script>

@stack('scripts')

{{-- Comportamientos compartidos: se cargan después de los scripts de cada módulo --}}
<script src="{{ asset('js/hw/hw-shell.js') }}"></script>
<script src="{{ asset('js/hw/hw-ui.js') }}"></script>

</body>
</html>
