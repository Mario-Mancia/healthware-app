@extends('layouts.app_layout')

@section('titulo', 'Acceso al expediente')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">lock</span>
                </div>
                <div>
                    <h1 class="hw-title">Expediente protegido</h1>
                    <p class="hw-subtitle">Usted no tiene una relación asistencial activa con este paciente.</p>
                </div>
            </div>
            <a href="{{ route('pacientes') }}" class="btn btn-tonal">
                <span class="material-symbols-outlined">arrow_back</span>
                Volver a pacientes
            </a>
        </header>

        <section class="hw-surface" style="max-width: 46rem;">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ trim($paciente->nombres . ' ' . $paciente->apellidos) }}</h2>
                    <p class="hw-surface-description">DUI {{ $paciente->numero_identificacion }}</p>
                </div>
            </div>

            <div style="padding: 0 1.25rem 1.25rem;">
                <div class="exp-alerta mb-3" role="alert">
                    <span class="material-symbols-outlined">emergency</span>
                    <div>
                        <strong>Acceso excepcional (emergencia).</strong>
                        Solo úselo si existe una emergencia real. Obtendrá {{ $horas }} horas de acceso de <strong>solo lectura</strong>;
                        la solicitud, la justificación y cada consulta quedan auditadas y se alerta a los administradores.
                    </div>
                </div>

                <form method="POST" action="{{ route('pacientes.acceso_excepcional', ['paciente' => $paciente->id]) }}" novalidate>
                    @csrf
                    <div class="form-floating mb-3">
                        <textarea name="motivo" id="acceso_motivo" class="form-control" placeholder="Justificación"
                                  maxlength="500" style="min-height: 8rem;" required>{{ old('motivo') }}</textarea>
                        <label for="acceso_motivo">Justificación (mínimo {{ $minimo }} caracteres)</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirmo" value="1" id="acceso_confirmo" @checked(old('confirmo')) required>
                        <label class="form-check-label" for="acceso_confirmo">
                            Confirmo que se trata de una emergencia real y entiendo que el acceso será revisado.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">lock_open</span>
                        Solicitar acceso excepcional
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
