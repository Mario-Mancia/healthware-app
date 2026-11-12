@extends('layouts.app_layout')

@section('titulo', 'Perfil del doctor')

@section('contenido')
    @php
        $nombreDoctor = trim(($doctor->usuario->nombres ?? '') . ' ' . ($doctor->usuario->apellidos ?? ''));
        $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
        $sexos = ['M' => 'Masculino', 'F' => 'Femenino'];
    @endphp

    <div class="hw-page">

        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">badge</span>
                </div>
                <div>
                    <h1 class="hw-title">{{ $nombreDoctor ?: 'Doctor' }}</h1>
                    <p class="hw-subtitle">{{ $doctor->especialidad->especialidad ?? 'Sin especialidad' }} · Licencia {{ $doctor->numero_licencia }}</p>
                </div>
            </div>
            <a href="{{ route('doctores') }}" class="btn btn-tonal">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver a doctores
            </a>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Información profesional</h2>
                    <p class="hw-surface-description">Datos del registro profesional del doctor.</p>
                </div>
            </div>

            <dl class="hw-detail-grid" style="padding: 0 1.25rem 1.25rem;">
                <div class="hw-detail-item"><dt>Especialidad</dt><dd>{{ $doctor->especialidad->especialidad ?? '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Número de licencia</dt><dd>{{ $doctor->numero_licencia }}</dd></div>
                <div class="hw-detail-item"><dt>DUI</dt><dd>{{ $doctor->dui }}</dd></div>
                <div class="hw-detail-item"><dt>Fecha de nacimiento</dt><dd>{{ $doctor->fecha_nacimiento ? \Illuminate\Support\Carbon::parse($doctor->fecha_nacimiento)->format('d/m/Y') : '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Sexo</dt><dd>{{ $sexos[$doctor->sexo] ?? '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Correo institucional</dt><dd>{{ $doctor->usuario->correo ?? '—' }}</dd></div>
                <div class="hw-detail-item is-wide"><dt>Descripción profesional</dt><dd>{{ $doctor->descripcion_profesional ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Horario semanal</h2>
                    <p class="hw-surface-description">Franjas en las que el doctor atiende.</p>
                </div>
                <a href="{{ route('horarios') }}" class="btn btn-tonal">Gestionar horarios</a>
            </div>

            <div class="hw-week">
                @foreach($dias as $numero => $nombreDia)
                    <div class="hw-week-day">
                        <h3>{{ $nombreDia }}</h3>
                        @forelse($horarios->where('dia_semana', $numero)->sortBy('hora_inicio') as $franja)
                            <div class="hw-week-slot">
                                <strong>{{ substr($franja->hora_inicio, 0, 5) }} - {{ substr($franja->hora_fin, 0, 5) }}</strong>
                            </div>
                        @empty
                            <span class="hw-week-empty">Sin horario</span>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
