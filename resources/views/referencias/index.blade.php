@extends('layouts.app_layout')

@section('titulo', 'Referencias médicas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $esMedico = $rolActual === 'medico';
        $puedeProgramar = in_array($rolActual, ['recepcionista', 'administrador']);
        $puedeGestionar = in_array($rolActual, ['recepcionista', 'administrador']);
        $lista = $referencias ?? collect();
        $claseEstado = ['pendiente' => 'warning', 'programada' => 'info', 'atendida' => 'success', 'rechazada' => 'error', 'cancelada' => 'neutral', 'vencida' => 'error'];
        $clasePrioridad = ['normal' => 'neutral', 'prioritaria' => 'warning', 'urgente' => 'error'];
        $nombreDoctor = fn ($doctor) => $doctor ? trim(($doctor->usuario?->nombres ?? '') . ' ' . ($doctor->usuario?->apellidos ?? '')) : '';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">swap_horiz</span>
                </div>
                <div>
                    <h1 class="hw-title">Referencias médicas</h1>
                    <p class="hw-subtitle">Un médico solicita la atención de otro especialista; recepción programa la cita.</p>
                </div>
            </div>
            @if($esMedico)
                <button type="button" class="btn btn-primary hw-create-button" data-bs-toggle="modal" data-bs-target="#modalNuevaReferencia">
                    <span class="material-symbols-outlined">add</span>
                    Nueva referencia
                </button>
            @endif
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">swap_horiz</span>
                <div><strong>{{ $lista->count() }}</strong><span>Referencias</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $lista->where('estado', 'pendiente')->count() }}</strong><span>Pendientes</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">event_available</span>
                <div><strong>{{ $lista->where('estado', 'programada')->count() }}</strong><span>Programadas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">task_alt</span>
                <div><strong>{{ $lista->where('estado', 'atendida')->count() }}</strong><span>Atendidas</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Referencias</h2>
                    <p class="hw-surface-description">
                        {{ $esMedico ? 'Las que usted emitió y las que se le asignaron.' : 'Las urgentes y prioritarias aparecen primero. El contenido clínico lo ven solo los médicos involucrados.' }}
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">swap_horiz</span>
                    <h3>Aún no hay referencias</h3>
                    <p>Cuando un médico refiera a un paciente aparecerá aquí.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar por referencia, paciente o DUI…" aria-label="Buscar en la tabla" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="3" aria-label="Filtrar por prioridad">
                        <option value="">Prioridad: todas</option>
                    </select>
                    <select class="form-select" data-hw-filtro-columna="5" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-referencias" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Referencia</th>
                                <th scope="col">Paciente</th>
                                <th scope="col">Origen → destino</th>
                                <th scope="col">Prioridad</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $item)
                                @php
                                    $vencida = $item->estaVencida();
                                    $estadoMostrado = $vencida ? 'vencida' : $item->estado;
                                    $esDestino = $esMedico && $item->id_doctor_destino && (int) $item->id_doctor_destino === (int) $idDoctorActual;
                                    $esOrigen = $esMedico && (int) $item->id_doctor_origen === (int) $idDoctorActual;
                                    $nombrePaciente = trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? ''));
                                    $destinoTexto = $item->doctorDestino
                                        ? $nombreDoctor($item->doctorDestino) . ' (' . ($item->doctorDestino->especialidad?->especialidad ?? 'sin especialidad') . ')'
                                        : ($item->especialidadDestino?->especialidad ?? '—') . ' (por asignar)';
                                @endphp
                                <tr>
                                    <td class="hw-mono">{{ $item->numero_referencia }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombrePaciente ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombreDoctor($item->doctorOrigen) ?: '—' }}</span>
                                            <span class="hw-identity-caption">→ {{ $destinoTexto }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $clasePrioridad[$item->prioridad] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($item->prioridad) }}
                                        </span>
                                    </td>
                                    <td>{{ $fmt($item->fecha_referencia, 'd/m/Y') }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseEstado[$estadoMostrado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($estadoMostrado) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="hw-actions">
                                            <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalVerReferencia"
                                                    title="Consultar referencia" aria-label="Consultar referencia"
                                                    data-numero="{{ $item->numero_referencia }}"
                                                    data-estado="{{ ucfirst($estadoMostrado) }}"
                                                    data-paciente="{{ $nombrePaciente }} (DUI {{ $item->paciente?->numero_identificacion ?? '—' }})"
                                                    data-prioridad="{{ ucfirst($item->prioridad) }}"
                                                    data-origen="{{ $nombreDoctor($item->doctorOrigen) }}"
                                                    data-destino="{{ $destinoTexto }}"
                                                    data-fecha="{{ $fmt($item->fecha_referencia) }}"
                                                    data-cita="{{ $item->cita ? $fmt($item->cita->fecha_cita, 'd/m/Y') . ' ' . substr($item->cita->hora_inicio, 0, 5) : 'Sin cita' }}"
                                                    data-motivo="{{ $item->motivo }}"
                                                    data-resumen="{{ $item->resumen_clinico }}"
                                                    data-estudios="{{ $item->estudios_solicitados }}"
                                                    data-observaciones="{{ $item->observaciones }}"
                                                    data-clinico="{{ ($esOrigen || $esDestino) ? '1' : '0' }}">
                                                <span class="material-symbols-outlined">visibility</span>
                                            </button>

                                            @if($item->cita)
                                                <a href="{{ route('citas.detalle', ['cita' => $item->cita->id]) }}" class="hw-action-button" title="Ver la cita" aria-label="Ver la cita">
                                                    <span class="material-symbols-outlined">event</span>
                                                </a>
                                            @endif

                                            @if($puedeProgramar && $item->estado === 'pendiente' && !$vencida)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalProgramar"
                                                        title="Programar la cita" aria-label="Programar la cita"
                                                        data-url="{{ route('referencias.programar', ['referencia' => $item->id]) }}"
                                                        data-numero="{{ $item->numero_referencia }}"
                                                        data-paciente="{{ $nombrePaciente }} (DUI {{ $item->paciente?->numero_identificacion ?? '—' }})"
                                                        data-destino="{{ $destinoTexto }}"
                                                        data-doctor-destino="{{ $item->id_doctor_destino }}"
                                                        data-doctor-origen="{{ $item->id_doctor_origen }}"
                                                        data-especialidad-destino="{{ $item->id_especialidad_destino }}">
                                                    <span class="material-symbols-outlined">event_available</span>
                                                </button>
                                            @endif

                                            @if($item->estado === 'pendiente' && ($puedeGestionar || $esDestino))
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Rechazar la referencia" aria-label="Rechazar la referencia"
                                                        data-url="{{ route('referencias.rechazar', ['referencia' => $item->id]) }}"
                                                        data-titulo="Rechazar referencia {{ $item->numero_referencia }}"
                                                        data-descripcion="El destino no puede aceptar la referencia. El médico que la emitió verá el motivo."
                                                        data-boton="Rechazar">
                                                    <span class="material-symbols-outlined">thumb_down</span>
                                                </button>
                                            @endif

                                            @if(in_array($item->estado, ['pendiente', 'programada'], true) && ($puedeGestionar || $esOrigen))
                                                <button type="button" class="hw-action-button hw-action-delete" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Cancelar la referencia" aria-label="Cancelar la referencia"
                                                        data-url="{{ route('referencias.cancelar', ['referencia' => $item->id]) }}"
                                                        data-titulo="Cancelar referencia {{ $item->numero_referencia }}"
                                                        data-descripcion="{{ $item->estado === 'programada' ? 'La cita programada también se cancelará.' : 'La referencia dejará de gestionarse.' }}"
                                                        data-boton="Cancelar referencia">
                                                    <span class="material-symbols-outlined">cancel</span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="hw-no-results">
                    <span class="material-symbols-outlined">search_off</span>
                    No se encontraron coincidencias con la búsqueda.
                </div>
            @endif
        </section>
    </div>

    {{-- MODAL: CONSULTA --}}
    <div class="modal fade hw-modal" id="modalVerReferencia" tabindex="-1" aria-labelledby="tmodalVerReferencia" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalVerReferencia">Detalle de la referencia</h2>
                            <p class="hw-modal-subtitle">Motivo, destino y estado de la referencia.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                    <div class="modal-body hw-modal-body">
                        <dl class="hw-detail-grid">
                            <div class="hw-detail-item"><dt>Referencia</dt><dd data-ver="numero">—</dd></div>
                            <div class="hw-detail-item"><dt>Estado</dt><dd data-ver="estado">—</dd></div>
                            <div class="hw-detail-item"><dt>Paciente</dt><dd data-ver="paciente">—</dd></div>
                            <div class="hw-detail-item"><dt>Prioridad</dt><dd data-ver="prioridad">—</dd></div>
                            <div class="hw-detail-item"><dt>Referido por</dt><dd data-ver="origen">—</dd></div>
                            <div class="hw-detail-item"><dt>Destino</dt><dd data-ver="destino">—</dd></div>
                            <div class="hw-detail-item"><dt>Fecha</dt><dd data-ver="fecha">—</dd></div>
                            <div class="hw-detail-item"><dt>Cita</dt><dd data-ver="cita">—</dd></div>
                            <div class="hw-detail-item is-wide"><dt>Motivo</dt><dd data-ver="motivo">—</dd></div>
                            <div class="hw-detail-item is-wide" data-ver-clinico><dt>Resumen clínico</dt><dd data-ver="resumen" style="white-space: pre-line;">—</dd></div>
                            <div class="hw-detail-item is-wide" data-ver-clinico><dt>Estudios solicitados</dt><dd data-ver="estudios" style="white-space: pre-line;">—</dd></div>
                            <div class="hw-detail-item is-wide"><dt>Observaciones</dt><dd data-ver="observaciones" style="white-space: pre-line;">—</dd></div>
                        </dl>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                    </div>
            </div>
        </div>
    </div>

    {{-- MODAL: NUEVA REFERENCIA --}}
    @if($esMedico)
    <div class="modal fade hw-modal" id="modalNuevaReferencia" tabindex="-1" aria-labelledby="tmodalNuevaReferencia" aria-hidden="true" data-abrir-con-dui>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">swap_horiz</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaReferencia">Nueva referencia médica</h2>
                            <p class="hw-modal-subtitle">Solo puede referir a pacientes que usted atiende.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('referencias.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaReferencia">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @include('components.campo_dui', ['id' => 'referencia_dui_paciente'])
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_registro_medico" id="referencia_registro" class="form-select"
                                            data-dui-registros data-valor-inicial="{{ old('id_registro_medico') }}">
                                        <option value="">Sin registro médico asociado</option>
                                    </select>
                                    <label for="referencia_registro">Registro médico de origen (opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_doctor_destino" id="referencia_doctor" class="form-select">
                                        <option value="">Cualquier médico de la especialidad</option>
                                        @foreach($doctores as $doctor)
                                            @continue((int) $doctor->id === (int) $idDoctorActual)
                                            <option value="{{ $doctor->id }}" @selected((string) old('id_doctor_destino') === (string) $doctor->id)>
                                                {{ trim(($doctor->usuario?->nombres ?? '') . ' ' . ($doctor->usuario?->apellidos ?? '')) }} · {{ $doctor->especialidad?->especialidad ?? 'Sin especialidad' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="referencia_doctor">Médico solicitado</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_especialidad_destino" id="referencia_especialidad" class="form-select">
                                        <option value="">Sin especialidad específica</option>
                                        @foreach($especialidades as $especialidad)
                                            <option value="{{ $especialidad->id }}" @selected((string) old('id_especialidad_destino') === (string) $especialidad->id)>{{ $especialidad->especialidad }}</option>
                                        @endforeach
                                    </select>
                                    <label for="referencia_especialidad">Especialidad solicitada</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">Indique un médico, una especialidad o ambos. Si solo indica la especialidad, recepción asigna al médico disponible.</p>
                            </div>
                            <div class="col-md-8">
                                <div class="form-floating">
                                    <input type="text" name="motivo" id="referencia_motivo" class="form-control" placeholder="Motivo" maxlength="255" value="{{ old('motivo') }}" required>
                                    <label for="referencia_motivo">Motivo de la referencia</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="prioridad" id="referencia_prioridad" class="form-select" required>
                                        @foreach(['normal' => 'Normal', 'prioritaria' => 'Prioritaria', 'urgente' => 'Urgente'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(old('prioridad', 'normal') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="referencia_prioridad">Prioridad</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="resumen_clinico" id="referencia_resumen" class="form-control" placeholder="Resumen clínico" style="min-height: 7rem;">{{ old('resumen_clinico') }}</textarea>
                                    <label for="referencia_resumen">Resumen clínico para el especialista</label>
                                </div>
                                <div class="hw-field-hint">Solo lo verán usted y el médico de destino.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="estudios_solicitados" id="referencia_estudios" class="form-control" placeholder="Estudios" style="min-height: 5rem;">{{ old('estudios_solicitados') }}</textarea>
                                    <label for="referencia_estudios">Estudios o procedimientos solicitados</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="observaciones" id="referencia_observaciones" class="form-control" placeholder="Observaciones" style="min-height: 4rem;">{{ old('observaciones') }}</textarea>
                                    <label for="referencia_observaciones">Observaciones (opcional)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">send</span>
                            Emitir referencia
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: PROGRAMAR --}}
    @if($puedeProgramar)
    <div class="modal fade hw-modal" id="modalProgramar" tabindex="-1" aria-labelledby="tmodalProgramar" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">event_available</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalProgramar">Programar la cita</h2>
                            <p class="hw-modal-subtitle">Recepción confirma el horario con el médico.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-programar-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalProgramar">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="hw-document-party mb-3">
                            <h3>Referencia</h3>
                            <p><strong data-programar-numero>—</strong> · <span data-programar-paciente>—</span></p>
                            <p data-programar-destino>—</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <select name="id_doctor" id="programar_doctor" class="form-select" required>
                                        <option value="">Seleccione el médico</option>
                                        @foreach($doctores as $doctor)
                                            <option value="{{ $doctor->id }}"
                                                    data-especialidad="{{ $doctor->especialidad_id }}"
                                                    @selected((string) old('id_doctor') === (string) $doctor->id)>
                                                {{ trim(($doctor->usuario?->nombres ?? '') . ' ' . ($doctor->usuario?->apellidos ?? '')) }} · {{ $doctor->especialidad?->especialidad ?? 'Sin especialidad' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="programar_doctor">Médico que atenderá</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="date" name="fecha_cita" id="programar_fecha" class="form-control" placeholder="Fecha" min="{{ now()->toDateString() }}" value="{{ old('_modal') === 'modalProgramar' ? old('fecha_cita') : '' }}" required>
                                    <label for="programar_fecha">Fecha</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="time" name="hora_inicio" id="programar_inicio" class="form-control" placeholder="Inicio" value="{{ old('_modal') === 'modalProgramar' ? old('hora_inicio') : '' }}" required>
                                    <label for="programar_inicio">Hora de inicio</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="time" name="hora_fin" id="programar_fin" class="form-control" placeholder="Fin" value="{{ old('_modal') === 'modalProgramar' ? old('hora_fin') : '' }}" required>
                                    <label for="programar_fin">Hora de fin</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="programar_notas" class="form-control" placeholder="Notas" maxlength="500" value="{{ old('_modal') === 'modalProgramar' ? old('notas') : '' }}">
                                    <label for="programar_notas">Notas de la cita (opcional)</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">El sistema valida el horario registrado del médico y que no tenga otra cita en ese momento. La cita queda vinculada a la referencia y el médico obtiene acceso asistencial al expediente.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">event_available</span>
                            Programar cita
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: RECHAZAR / CANCELAR --}}
    <div class="modal fade hw-modal" id="modalMotivo" tabindex="-1" aria-labelledby="tmodalMotivo" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit_note</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalMotivo">Confirmar acción</h2>
                            <p class="hw-modal-subtitle">Indique el motivo: quedará registrado.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-motivo-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalMotivo">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <p class="mb-3" data-motivo-descripcion></p>
                        <div class="form-floating">
                            <textarea name="motivo" id="accion_motivo" class="form-control" placeholder="Motivo" maxlength="500" required>{{ old('_modal') === 'modalMotivo' ? old('motivo') : '' }}</textarea>
                            <label for="accion_motivo">Motivo</label>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" data-motivo-boton>
                            <span class="material-symbols-outlined">check</span>
                            <span data-motivo-boton-texto>Confirmar</span>
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-dui.js') }}"></script>
    <script src="{{ asset('js/hw/hw-accion-motivo.js') }}"></script>
    <script src="{{ asset('js/referencia/referencias.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
