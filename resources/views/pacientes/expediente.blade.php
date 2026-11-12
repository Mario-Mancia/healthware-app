@extends('layouts.app_layout')

@section('titulo', 'Expediente clínico')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $nombrePaciente = trim(($paciente->nombres ?? '') . ' ' . ($paciente->apellidos ?? ''));
        $edad = $paciente->fecha_nacimiento ? \Illuminate\Support\Carbon::parse($paciente->fecha_nacimiento)->age : null;
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $alergiasGraves = $paciente->alergias->where('gravedad', 'grave');
        $claseGravedad = ['grave' => 'error', 'moderada' => 'warning', 'leve' => 'info', 'desconocida' => 'neutral'];
        $dui = $paciente->numero_identificacion;
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">folder_shared</span>
                </div>
                <div>
                    <h1 class="hw-title">Expediente de {{ $nombrePaciente }}</h1>
                    <p class="hw-subtitle">DUI {{ $dui }}{{ $edad !== null ? ' · ' . $edad . ' años' : '' }} · {{ $paciente->genero }}</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('pacientes') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver a pacientes
                </a>
                @if($puedeRegistrar)
                    <a href="{{ route('registros_medicos', ['dui' => $dui]) }}" class="btn btn-primary">
                        <span class="material-symbols-outlined">clinical_notes</span>
                        Registrar atención
                    </a>
                    <a href="{{ route('recetas', ['dui' => $dui]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">prescriptions</span>
                        Nueva receta
                    </a>
                    <a href="{{ route('ordenes', ['dui' => $dui]) }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">assignment</span>
                        Nueva orden
                    </a>
                @endif
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <span class="material-symbols-outlined">print</span>
                    Imprimir
                </button>
            </div>
        </header>

        @if($alergiasGraves->isNotEmpty())
            <div class="exp-alerta" role="alert">
                <span class="material-symbols-outlined">warning</span>
                <div>
                    <strong>Alergias graves:</strong>
                    {{ $alergiasGraves->map(fn ($a) => $a->alergia->alergia ?? '—')->implode(', ') }}
                </div>
            </div>
        @endif

        {{-- RESUMEN --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Resumen del paciente</h2>
                    <p class="hw-surface-description">Datos básicos y contacto de emergencia.</p>
                </div>
            </div>
            <dl class="exp-resumen">
                <div class="exp-dato"><dt>Nacimiento</dt><dd>{{ $fmtFecha($paciente->fecha_nacimiento) }}</dd></div>
                <div class="exp-dato"><dt>Tipo sanguíneo</dt><dd>{{ $paciente->tipoSanguineo?->tipo_sanguineo ?? 'Sin registrar' }}</dd></div>
                <div class="exp-dato"><dt>Teléfono</dt><dd>{{ $paciente->telefono ?: '—' }}</dd></div>
                <div class="exp-dato"><dt>Contacto de emergencia</dt><dd>{{ $paciente->nombre_contacto_emergencia ?: '—' }}{{ $paciente->parentesco_contacto_emergencia ? ' (' . $paciente->parentesco_contacto_emergencia . ')' : '' }}</dd></div>
                <div class="exp-dato"><dt>Teléfono del contacto</dt><dd>{{ $paciente->telefono_contacto_emergencia ?: '—' }}</dd></div>
            </dl>
        </section>

        {{-- ALERGIAS --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Alergias</h2>
                    <p class="hw-surface-description">{{ $paciente->alergias->count() }} registradas.</p>
                </div>
                @if($puedeRegistrar)
                    <button type="button" class="btn btn-tonal" data-bs-toggle="modal" data-bs-target="#modalNuevaAlergia">
                        <span class="material-symbols-outlined">add</span>
                        Agregar alergia
                    </button>
                @endif
            </div>
            <div class="exp-seccion-cuerpo">
                @forelse($paciente->alergias as $registro)
                    <div class="exp-registro" style="margin: 0 0 0.6rem;">
                        <div class="exp-registro-cabecera">
                            <h3>{{ $registro->alergia->alergia ?? '—' }}</h3>
                            <span class="hw-status hw-status-{{ $claseGravedad[$registro->gravedad] ?? 'neutral' }}">
                                <span class="hw-status-dot"></span>{{ ucfirst($registro->gravedad) }}
                            </span>
                        </div>
                        @if($registro->reaccion)<p class="mb-1"><strong>Reacción:</strong> {{ $registro->reaccion }}</p>@endif
                        @if($registro->notas)<p class="mb-1 hw-muted">{{ $registro->notas }}</p>@endif
                        <form method="POST" action="{{ route('pacientes_alergias.eliminar', ['pacienteAlergia' => $registro->id]) }}"
                              data-confirmar="¿Eliminar esta alergia del expediente?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-text btn-sm">Quitar</button>
                        </form>
                    </div>
                @empty
                    <p class="hw-muted mb-0">No hay alergias registradas.</p>
                @endforelse
            </div>
        </section>

        {{-- CONDICIONES MÉDICAS --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Condiciones médicas</h2>
                    <p class="hw-surface-description">{{ $paciente->condiciones->count() }} registradas.</p>
                </div>
                @if($puedeRegistrar)
                    <button type="button" class="btn btn-tonal" data-bs-toggle="modal" data-bs-target="#modalNuevaCondicion">
                        <span class="material-symbols-outlined">add</span>
                        Agregar condición
                    </button>
                @endif
            </div>
            <div class="exp-seccion-cuerpo">
                @forelse($paciente->condiciones as $registro)
                    <div class="exp-registro" style="margin: 0 0 0.6rem;">
                        <div class="exp-registro-cabecera">
                            <h3>{{ $registro->condicionMedica->condicion_medica ?? '—' }}</h3>
                            <span class="hw-muted">{{ $registro->fecha_diagnostico ? 'Diagnosticada el ' . $fmtFecha($registro->fecha_diagnostico) : 'Sin fecha de diagnóstico' }}</span>
                        </div>
                        @if($registro->notas)<p class="mb-1 hw-muted">{{ $registro->notas }}</p>@endif
                        <form method="POST" action="{{ route('pacientes_condiciones.eliminar', ['pacienteCondicion' => $registro->id]) }}"
                              data-confirmar="¿Eliminar esta condición del expediente?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-text btn-sm">Quitar</button>
                        </form>
                    </div>
                @empty
                    <p class="hw-muted mb-0">No hay condiciones médicas registradas.</p>
                @endforelse
            </div>
        </section>

        {{-- HISTORIAL CLÍNICO --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Historial clínico</h2>
                    <p class="hw-surface-description">{{ $paciente->registrosMedicos->count() }} atenciones, de la más reciente a la más antigua.</p>
                </div>
            </div>

            @forelse($paciente->registrosMedicos as $registro)
                @php
                    $signos = $registro->signosVitales;
                    $doctorRegistro = trim(($registro->doctor?->usuario?->nombres ?? '') . ' ' . ($registro->doctor?->usuario?->apellidos ?? ''));
                @endphp
                <article class="exp-registro">
                    <div class="exp-registro-cabecera">
                        <h3>{{ $fmtFecha($registro->fecha_registro, 'd/m/Y H:i') }} · {{ $registro->motivo_consulta ?: 'Consulta' }}</h3>
                        <span class="hw-muted">{{ $doctorRegistro ?: 'Doctor' }}{{ $registro->doctor?->especialidad?->especialidad ? ' · ' . $registro->doctor->especialidad->especialidad : '' }}</span>
                    </div>

                    @if($signos)
                        <div class="exp-signos">
                            @if($signos->peso !== null)<span class="exp-signo">Peso <strong>{{ $signos->peso }}</strong></span>@endif
                            @if($signos->altura !== null)<span class="exp-signo">Altura <strong>{{ $signos->altura }}</strong></span>@endif
                            @if($signos->temperatura !== null)<span class="exp-signo">Temp. <strong>{{ $signos->temperatura }}</strong></span>@endif
                            @if($signos->presion_sistolica !== null)<span class="exp-signo">Presión <strong>{{ $signos->presion_sistolica }}/{{ $signos->presion_diastolica }}</strong></span>@endif
                            @if($signos->frecuencia_cardiaca !== null)<span class="exp-signo">FC <strong>{{ $signos->frecuencia_cardiaca }}</strong></span>@endif
                            @if($signos->frecuencia_respiratoria !== null)<span class="exp-signo">FR <strong>{{ $signos->frecuencia_respiratoria }}</strong></span>@endif
                            @if($signos->saturacion_oxigeno !== null)<span class="exp-signo">SatO₂ <strong>{{ $signos->saturacion_oxigeno }}</strong></span>@endif
                        </div>
                    @endif

                    @if($registro->notas_clinicas)
                        <div class="exp-bloque"><h4>Notas clínicas</h4><p class="mb-0">{{ $registro->notas_clinicas }}</p></div>
                    @endif
                    @if($registro->observaciones)
                        <div class="exp-bloque"><h4>Observaciones</h4><p class="mb-0">{{ $registro->observaciones }}</p></div>
                    @endif

                    <div class="exp-bloque">
                        <h4>Diagnósticos</h4>
                        <div class="exp-chips">
                            @forelse($registro->diagnosticos as $asociado)
                                <span class="hw-chip" title="{{ $asociado->notas }}">{{ $asociado->diagnostico->diagnostico ?? '—' }}</span>
                                @if($puedeRegistrar)
                                    <form method="POST" action="{{ route('registro_medico_diagnosticos.eliminar', ['registroMedicoDiagnostico' => $asociado->id]) }}"
                                          data-confirmar="¿Quitar este diagnóstico del registro?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-text btn-sm" aria-label="Quitar diagnóstico">Quitar</button>
                                    </form>
                                @endif
                            @empty
                                <span class="hw-muted">Sin diagnósticos asociados.</span>
                            @endforelse
                            @if($puedeRegistrar)
                                <button type="button" class="btn btn-tonal btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalNuevoDiagnostico"
                                        data-registro-id="{{ $registro->id }}"
                                        data-registro-texto="{{ $fmtFecha($registro->fecha_registro, 'd/m/Y H:i') }}">
                                    <span class="material-symbols-outlined">add</span>
                                    Diagnóstico
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($registro->recetas->isNotEmpty())
                        <div class="exp-bloque">
                            <h4>Recetas</h4>
                            @foreach($registro->recetas as $receta)
                                <p class="mb-1 hw-muted">Receta del {{ $fmtFecha($receta->fecha_prescripcion) }}{{ $receta->notas ? ' · ' . $receta->notas : '' }}</p>
                                <table class="table hw-subtable">
                                    <thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th></tr></thead>
                                    <tbody>
                                        @foreach($receta->detalles as $detalle)
                                            <tr>
                                                <td>{{ $detalle->medicamento->nombre ?? '—' }}</td>
                                                <td>{{ $detalle->dosis }}</td>
                                                <td>{{ $detalle->frecuencia }}</td>
                                                <td>{{ $detalle->duracion ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="hw-empty">
                    <span class="material-symbols-outlined">clinical_notes</span>
                    <h3>Sin atenciones registradas</h3>
                    <p>Cuando se registre una atención aparecerá aquí.</p>
                </div>
            @endforelse
        </section>
    </div>

    @if($puedeRegistrar)
    <div class="modal fade hw-modal" id="modalNuevaAlergia" tabindex="-1" aria-labelledby="tmodalNuevaAlergia" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">warning</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaAlergia">Registrar alergia</h2>
                            <p class="hw-modal-subtitle">Se agrega al expediente del paciente.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('pacientes_alergias.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaAlergia">
                        <input type="hidden" name="dui_paciente" value="{{ $paciente->numero_identificacion }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_alergia" id="exp_id_alergia" class="form-select" required>
                                        <option value="">Seleccione</option>
                                        @foreach($alergias as $alergia)
                                            <option value="{{ $alergia->id }}" @selected((string) old('id_alergia') === (string) $alergia->id)>{{ $alergia->alergia }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_id_alergia">Alergia</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="gravedad" id="exp_gravedad" class="form-select" required>
                                        @foreach(['desconocida' => 'Desconocida', 'leve' => 'Leve', 'moderada' => 'Moderada', 'grave' => 'Grave'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(old('gravedad', 'desconocida') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_gravedad">Gravedad</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="reaccion" id="exp_reaccion" class="form-control" placeholder="Reacción" maxlength="250" value="{{ old('reaccion') }}">
                                    <label for="exp_reaccion">Reacción (opcional)</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="exp_notas_alergia" class="form-control" placeholder="Notas" maxlength="250" value="{{ old('notas') }}">
                                    <label for="exp_notas_alergia">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar alergia
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    <div class="modal fade hw-modal" id="modalNuevaCondicion" tabindex="-1" aria-labelledby="tmodalNuevaCondicion" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">medical_information</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaCondicion">Registrar condición médica</h2>
                            <p class="hw-modal-subtitle">Se agrega al expediente del paciente.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('pacientes_condiciones.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaCondicion">
                        <input type="hidden" name="dui_paciente" value="{{ $paciente->numero_identificacion }}">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="form-floating">
                                    <select name="id_condicion_medica" id="exp_id_condicion" class="form-select" required>
                                        <option value="">Seleccione</option>
                                        @foreach($condicionesMedicas as $condicion)
                                            <option value="{{ $condicion->id }}" @selected((string) old('id_condicion_medica') === (string) $condicion->id)>{{ $condicion->condicion_medica }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_id_condicion">Condición médica</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="date" name="fecha_diagnostico" id="exp_fecha_diagnostico" class="form-control" placeholder="Fecha" value="{{ old('fecha_diagnostico') }}">
                                    <label for="exp_fecha_diagnostico">Diagnosticada el</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="exp_notas_condicion" class="form-control" placeholder="Notas" maxlength="250" value="{{ old('notas') }}">
                                    <label for="exp_notas_condicion">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar condición
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    <div class="modal fade hw-modal" id="modalNuevoDiagnostico" tabindex="-1" aria-labelledby="tmodalNuevoDiagnostico" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">diagnosis</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevoDiagnostico">Asociar diagnóstico</h2>
                            <p class="hw-modal-subtitle">Se vincula al registro médico seleccionado.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('registro_medico_diagnosticos.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevoDiagnostico">
                        <input type="hidden" name="id_registro_medico" id="exp_diag_registro" value="{{ old('id_registro_medico') }}">
                        <p class="hw-field-hint mb-3">Registro médico <strong id="exp_diag_registro_texto">—</strong></p>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <select name="id_diagnostico" id="exp_id_diagnostico" class="form-select" required>
                                        <option value="">Seleccione</option>
                                        @foreach($diagnosticos as $diagnostico)
                                            <option value="{{ $diagnostico->id }}" @selected((string) old('id_diagnostico') === (string) $diagnostico->id)>{{ $diagnostico->diagnostico }}</option>
                                        @endforeach
                                    </select>
                                    <label for="exp_id_diagnostico">Diagnóstico</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="exp_notas_diagnostico" class="form-control" placeholder="Notas" maxlength="250" value="{{ old('notas') }}">
                                    <label for="exp_notas_diagnostico">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar diagnóstico
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    @include('components.reabrir_modal')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* Confirmación antes de quitar alergias, condiciones y diagnósticos */
            document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
                formulario.addEventListener('submit', function (evento) {
                    if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
                });
            });

            /* El modal de diagnóstico recibe el registro médico elegido */
            var modalDiagnostico = document.getElementById('modalNuevoDiagnostico');
            if (modalDiagnostico) {
                modalDiagnostico.addEventListener('show.bs.modal', function (evento) {
                    var boton = evento.relatedTarget;
                    if (!boton || !boton.dataset.registroId) return;
                    document.getElementById('exp_diag_registro').value = boton.dataset.registroId;
                    document.getElementById('exp_diag_registro_texto').textContent = boton.dataset.registroTexto;
                });
            }
        });
    </script>
@endpush
