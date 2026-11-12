@extends('layouts.app_layout')

@section('titulo', 'Accesos excepcionales')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $lista = $accesos ?? collect();
        $claseEstado = ['activo' => 'warning', 'expirado' => 'neutral', 'revocado' => 'error'];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">emergency</span>
                </div>
                <div>
                    <h1 class="hw-title">Accesos excepcionales</h1>
                    <p class="hw-subtitle">Acceso de emergencia a un expediente sin relación asistencial: justificado, de 4 horas y de solo lectura.</p>
                </div>
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ $esAdministrador ? 'Accesos concedidos' : 'Mis accesos excepcionales' }}</h2>
                    <p class="hw-surface-description">
                        {{ $esAdministrador ? 'Revise la justificación de cada acceso. Cada solicitud genera una alerta y queda en la auditoría.' : 'Para solicitar uno, abra el expediente de un paciente que no atiende y explique la emergencia.' }}
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">emergency</span>
                    <h3>Sin accesos excepcionales</h3>
                    <p>Aquí aparecerán los accesos de emergencia que se soliciten.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Paciente</th>
                                <th>Justificación</th>
                                <th>Estado</th>
                                <th>Revisión</th>
                                @if($esAdministrador)<th class="text-center">Acciones</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $item)
                                @php
                                    $nombreUsuario = trim(($item->usuario?->nombres ?? '') . ' ' . ($item->usuario?->apellidos ?? ''));
                                    $nombrePaciente = trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? ''));
                                    $estado = $item->estadoVisible();
                                @endphp
                                <tr>
                                    <td>
                                        {{ $fmt($item->fecha_inicio) }}
                                        <span class="hw-muted d-block">hasta {{ $fmt($item->fecha_expiracion) }}</span>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombreUsuario ?: '—' }}</span>
                                            <span class="hw-identity-caption">{{ $item->usuario?->rol?->rol ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombrePaciente ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td title="{{ $item->motivo }}">{{ \Illuminate\Support\Str::limit($item->motivo, 80) }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseEstado[$estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($item->revisado_en)
                                            {{ $fmt($item->revisado_en, 'd/m/Y') }}
                                            <span class="hw-muted d-block">{{ trim(($item->revisor?->nombres ?? '') . ' ' . ($item->revisor?->apellidos ?? '')) }}</span>
                                        @else
                                            <span class="hw-muted">Pendiente</span>
                                        @endif
                                    </td>
                                    @if($esAdministrador)
                                        <td>
                                            <div class="hw-actions">
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalRevisarAcceso"
                                                        title="Registrar la revisión" aria-label="Registrar la revisión"
                                                        data-url="{{ route('accesos_excepcionales.revisar', ['acceso' => $item->id]) }}"
                                                        data-usuario="{{ $nombreUsuario }}"
                                                        data-paciente="{{ $nombrePaciente }} (DUI {{ $item->paciente?->numero_identificacion ?? '—' }})"
                                                        data-motivo="{{ $item->motivo }}"
                                                        data-observacion="{{ $item->observacion_revision }}">
                                                    <span class="material-symbols-outlined">fact_check</span>
                                                </button>
                                                @if($estado === 'activo')
                                                    <form method="POST" action="{{ route('accesos_excepcionales.revocar', ['acceso' => $item->id]) }}" class="d-inline"
                                                          data-confirmar="¿Revocar este acceso excepcional ahora?">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="hw-action-button hw-action-delete" title="Revocar el acceso" aria-label="Revocar el acceso">
                                                            <span class="material-symbols-outlined">block</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    @if($esAdministrador)
    <div class="modal fade hw-modal" id="modalRevisarAcceso" tabindex="-1" aria-labelledby="tmodalRevisarAcceso" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">fact_check</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalRevisarAcceso">Revisar el acceso excepcional</h2>
                            <p class="hw-modal-subtitle">Deje constancia de la revisión posterior.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-revisar-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalRevisarAcceso">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="hw-document-party mb-3">
                            <h3>Acceso excepcional</h3>
                            <p><strong data-revisar-usuario>—</strong></p>
                            <p data-revisar-paciente>—</p>
                            <p data-revisar-motivo style="white-space: pre-line;">—</p>
                        </div>
                        <div class="form-floating">
                            <textarea name="observacion" id="revisar_observacion" class="form-control" placeholder="Observación" maxlength="1000" style="min-height: 6rem;">{{ old('_modal') === 'modalRevisarAcceso' ? old('observacion') : '' }}</textarea>
                            <label for="revisar_observacion">Observación de la revisión (opcional)</label>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">fact_check</span>
                            Registrar revisión
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/acceso/accesos.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
