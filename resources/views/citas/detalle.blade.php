@extends('layouts.app_layout')

@section('titulo', 'Detalle de la cita')

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $esMedico = $rolActual === 'medico';
        $puedeExpediente = in_array($rolActual, ['administrador', 'medico']);
        $nombrePaciente = trim(($cita->paciente?->nombres ?? '') . ' ' . ($cita->paciente?->apellidos ?? ''));
        $nombreDoctor = trim(($cita->doctor?->usuario?->nombres ?? '') . ' ' . ($cita->doctor?->usuario?->apellidos ?? ''));
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $claseCita = ['programada' => 'info', 'completada' => 'success', 'cancelada' => 'error', 'no_asistio' => 'warning', 'reprogramada' => 'warning'];
        $dui = $cita->paciente?->numero_identificacion;
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">event</span>
                </div>
                <div>
                    <h1 class="hw-title">Cita del {{ $fmtFecha($cita->fecha_cita) }}</h1>
                    <p class="hw-subtitle">{{ substr($cita->hora_inicio, 0, 5) }} a {{ substr($cita->hora_fin, 0, 5) }} con {{ $nombreDoctor ?: "doctor sin asignar" }}</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('citas') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver a citas
                </a>
                @if($puedeExpediente && $cita->paciente)
                    <a href="{{ route('pacientes.expediente', ['paciente' => $cita->id_paciente]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">folder_shared</span>
                        Expediente
                    </a>
                @endif
                @if($esMedico && $dui)
                    <a href="{{ route('registros_medicos', ['dui' => $dui]) }}" class="btn btn-primary">
                        <span class="material-symbols-outlined">clinical_notes</span>
                        Registrar atención
                    </a>
                @endif
                @if(in_array($rolActual, ['medico', 'recepcionista']) && $dui)
                    <a href="{{ route('ordenes', ['dui' => $dui]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">assignment</span>
                        Nueva orden
                    </a>
                @endif
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Información de la cita</h2>
                    <p class="hw-surface-description">Paciente, doctor y motivo de la consulta.</p>
                </div>
                <span class="hw-status hw-status-{{ $claseCita[$cita->estado] ?? 'neutral' }}">
                    <span class="hw-status-dot"></span>{{ ucfirst(str_replace('_', ' ', $cita->estado)) }}
                </span>
            </div>
            <dl class="hw-detail-grid" style="padding: 0 1.25rem 1.25rem;">
                <div class="hw-detail-item"><dt>Paciente</dt><dd>{{ $nombrePaciente ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>DUI</dt><dd>{{ $dui ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Doctor</dt><dd>{{ $nombreDoctor ?: '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Especialidad</dt><dd>{{ $cita->doctor?->especialidad?->especialidad ?? '—' }}</dd></div>
                <div class="hw-detail-item"><dt>Fecha</dt><dd>{{ $fmtFecha($cita->fecha_cita) }}</dd></div>
                <div class="hw-detail-item"><dt>Horario</dt><dd>{{ substr($cita->hora_inicio, 0, 5) }} - {{ substr($cita->hora_fin, 0, 5) }}</dd></div>
                <div class="hw-detail-item is-wide"><dt>Motivo de la consulta</dt><dd>{{ $cita->motivo ?: '—' }}</dd></div>
                <div class="hw-detail-item is-wide"><dt>Notas</dt><dd>{{ $cita->notas ?: '—' }}</dd></div>
            </dl>
        </section>

        @if($cita->referenciaMedica)
            @php
                $referenciaCita = $cita->referenciaMedica;
                $esMedicoDestino = auth()->id() === ($cita->doctor?->usuario_id);
            @endphp
            <section class="hw-surface">
                <div class="hw-surface-header">
                    <div>
                        <h2 class="hw-surface-title">Origen: referencia médica {{ $referenciaCita->numero_referencia }}</h2>
                        <p class="hw-surface-description">Esta cita se programó a partir de una referencia.</p>
                    </div>
                    <a href="{{ route('referencias') }}" class="btn btn-tonal">Ver referencias</a>
                </div>
                <dl class="hw-detail-grid" style="padding: 0 1.25rem 1.25rem;">
                    <div class="hw-detail-item"><dt>Referido por</dt><dd>{{ trim(($referenciaCita->doctorOrigen?->usuario?->nombres ?? '') . ' ' . ($referenciaCita->doctorOrigen?->usuario?->apellidos ?? '')) ?: '—' }}</dd></div>
                    <div class="hw-detail-item"><dt>Prioridad</dt><dd>{{ ucfirst($referenciaCita->prioridad) }}</dd></div>
                    <div class="hw-detail-item is-wide"><dt>Motivo</dt><dd>{{ $referenciaCita->motivo }}</dd></div>
                    @if($esMedicoDestino)
                        <div class="hw-detail-item is-wide"><dt>Resumen clínico</dt><dd>{{ $referenciaCita->resumen_clinico ?: '—' }}</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Estudios solicitados</dt><dd>{{ $referenciaCita->estudios_solicitados ?: '—' }}</dd></div>
                    @endif
                </dl>
            </section>
        @endif
    </div>
@endsection
