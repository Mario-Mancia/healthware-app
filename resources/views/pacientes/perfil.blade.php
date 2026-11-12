@extends('layouts.app_layout')

@section('titulo', 'Perfil del paciente')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeExpediente = in_array($rolActual, ['administrador', 'medico']);
        $puedeAgendar = in_array($rolActual, ['administrador', 'recepcionista']);
        $nombrePaciente = trim(($paciente->nombres ?? '') . ' ' . ($paciente->apellidos ?? ''));
        $edad = $paciente->fecha_nacimiento ? \Illuminate\Support\Carbon::parse($paciente->fecha_nacimiento)->age : null;
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $claseCita = ['programada' => 'info', 'completada' => 'success', 'cancelada' => 'error', 'no_asistio' => 'warning', 'reprogramada' => 'warning'];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">badge</span>
                </div>
                <div>
                    <h1 class="hw-title">{{ $nombrePaciente }}</h1>
                    <p class="hw-subtitle">DUI {{ $paciente->numero_identificacion }}{{ $edad !== null ? " · " . $edad . " años" : "" }}</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('pacientes') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver a pacientes
                </a>
                @if($puedeAgendar)
                    <a href="{{ route('citas', ['dui' => $paciente->numero_identificacion]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">event</span>
                        Agendar cita
                    </a>
                @endif
                @if($puedeExpediente)
                    <a href="{{ route('pacientes.expediente', ['paciente' => $paciente->id]) }}" class="btn btn-primary">
                        <span class="material-symbols-outlined">folder_shared</span>
                        Expediente clínico
                    </a>
                @endif
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Datos del paciente</h2>
                    <p class="hw-surface-description">Información administrativa y de contacto.</p>
                </div>
            </div>
            <dl class="hw-detail-grid" style="padding: 0 1.25rem 1.25rem;">
                <div class="hw-detail-item"><dt>Fecha de nacimiento</dt><dd>{{ $fmtFecha($paciente->fecha_nacimiento) }}</dd></div>
                <div class="hw-detail-item"><dt>Género</dt><dd>{{ $paciente->genero }}</dd></div>
                <div class="hw-detail-item"><dt>Tipo sanguíneo</dt><dd>{{ $paciente->tipoSanguineo?->tipo_sanguineo ?? 'Sin registrar' }}</dd></div>
                <div class="hw-detail-item"><dt>Teléfono</dt><dd>{{ $paciente->telefono ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Correo</dt><dd>{{ $paciente->correo ?: '—' }}</dd></div>
                <div class="hw-detail-item is-wide"><dt>Dirección</dt><dd>{{ $paciente->direccion ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Contacto de emergencia</dt><dd>{{ $paciente->nombre_contacto_emergencia ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Teléfono del contacto</dt><dd>{{ $paciente->telefono_contacto_emergencia ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Parentesco</dt><dd>{{ $paciente->parentesco_contacto_emergencia ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Citas recientes</h2>
                    <p class="hw-surface-description">Las últimas {{ $citas->count() }} citas del paciente.</p>
                </div>
            </div>
            @if($citas->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">event_busy</span>
                    <h3>Sin citas registradas</h3>
                    <p>Cuando se agende una cita aparecerá aquí.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Doctor</th>
                                <th>Estado</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($citas as $cita)
                                <tr>
                                    <td>{{ $fmtFecha($cita->fecha_cita) }}</td>
                                    <td>{{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }}</td>
                                    <td>{{ trim(($cita->doctor?->usuario?->nombres ?? '') . ' ' . ($cita->doctor?->usuario?->apellidos ?? '')) ?: '—' }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseCita[$cita->estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst(str_replace('_', ' ', $cita->estado)) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('citas.detalle', ['cita' => $cita->id]) }}" class="hw-action-button" aria-label="Detalle de la cita">
                                            <span class="material-symbols-outlined">open_in_new</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
