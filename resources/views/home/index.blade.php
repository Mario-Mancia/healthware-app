@extends('layouts.app_layout')

@section('titulo', 'Inicio')

@section('contenido')

    @php
        $usuarioActual = auth()->user();
        $rolInicio = mb_strtolower(trim($usuarioActual->rol->rol ?? ''));
        $configuracionInicio = require resource_path('views/layouts/partials/menu_config.php');

        // Solo los módulos con ruta existente y permitidos para el rol del usuario
        $gruposInicio = [];
        foreach ($configuracionInicio['modulos'] as $moduloInicio) {
            $enlacesInicio = array_values(array_filter(
                $moduloInicio['enlaces'],
                fn ($enlace) => \Illuminate\Support\Facades\Route::has($enlace['ruta'])
                    && in_array($rolInicio, $enlace['roles'], true)
            ));

            if ($enlacesInicio !== []) {
                $gruposInicio[] = [
                    'titulo' => $moduloInicio['titulo'],
                    'icono' => $moduloInicio['icono'],
                    'enlaces' => $enlacesInicio,
                ];
            }
        }

        $horaInicio = (int) now()->format('H');
        $saludoInicio = $horaInicio < 12 ? 'Buenos días' : ($horaInicio < 19 ? 'Buenas tardes' : 'Buenas noches');
    @endphp

    <div class="hw-page">

        <section class="hw-hero">
            <div>
                <h1>{{ $saludoInicio }}, {{ $usuarioActual->nombres ?? 'bienvenido' }}</h1>
                <p>
                    Bienvenido a HealthWare, el sistema de gestión clínica. Desde aquí puedes
                    ir directamente a los módulos disponibles para tu rol.
                </p>
            </div>

            <span class="hw-hero-badge">
                <span class="material-symbols-outlined">verified_user</span>
                {{ $usuarioActual->rol->rol ?? 'Usuario' }}
            </span>
        </section>

        @forelse($gruposInicio as $grupoInicio)
            <section class="hw-quick-section">
                <h2>
                    <span class="material-symbols-outlined">{{ $grupoInicio['icono'] }}</span>
                    {{ $grupoInicio['titulo'] }}
                </h2>

                <div class="hw-quick-grid">
                    @foreach($grupoInicio['enlaces'] as $enlaceInicio)
                        <a href="{{ route($enlaceInicio['ruta']) }}" class="hw-quick-card">
                            <span class="material-symbols-outlined">{{ $enlaceInicio['icono'] }}</span>
                            <span class="hw-quick-text">{{ $enlaceInicio['etiqueta'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="hw-surface">
                <div class="hw-empty">
                    <span class="material-symbols-outlined">lock</span>
                    <h3>No hay módulos disponibles</h3>
                    <p>Tu rol todavía no tiene accesos asignados. Contacta con el administrador.</p>
                </div>
            </section>
        @endforelse

    </div>

@endsection
