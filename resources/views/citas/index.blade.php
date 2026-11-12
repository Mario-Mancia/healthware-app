{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Facturas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['administrador','recepcionista']);
        $puedeEditar = in_array($rolActual, ['administrador','recepcionista']);
        $puedeEliminar = in_array($rolActual, ['administrador','recepcionista']);
        $lista = $citas ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">event</span>
                </div>
                <div>
                    <h1 class="hw-title">Citas médicas</h1>
                    <p class="hw-subtitle">Programación de citas entre pacientes y doctores.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">edit_calendar</span>
                    Nueva cita
                </button>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Agenda de citas</h2>
                    <p class="hw-surface-description">Consulta y administra las citas programadas.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">event</span>
                    <h3>Aún no hay registros</h3>
                    <p>Cuando existan datos aparecerán aquí.</p>
                </div>
            @else
            <div class="hw-toolbar">
                <div class="hw-search">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" class="form-control" data-hw-buscar placeholder="Buscar en la tabla…" aria-label="Buscar en la tabla" autocomplete="off">
                    <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <select class="form-select" data-filtro-columna="5" aria-label="Filtrar por estado">
                    <option value="">Estado: todos</option>
                </select>
                <select class="form-select" data-filtro-columna="4" aria-label="Filtrar por doctor">
                    <option value="">Doctor: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-citas" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Horario</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr>
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>{{ $fmtFecha($item->fecha_cita, 'd/m/Y') }}</td>
                            <td>{{ substr((string) $item->hora_inicio, 0, 5) }} – {{ substr((string) $item->hora_fin, 0, 5) }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">person</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ ($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? '') }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ($item->motivo ?? ""), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td>{{ ($item->doctor?->usuario?->nombres ?? '') . ' ' . ($item->doctor?->usuario?->apellidos ?? '') ?? "—" }}</td>
                            <td><span class="hw-status hw-status-{{ ['programada' => 'info', 'completada' => 'success', 'cancelada' => 'error', 'no_asistio' => 'warning', 'reprogramada' => 'neutral', ][(string) ($item->estado)] ?? "neutral" }}" data-filtro-valor><span class="hw-status-dot"></span>{{ ucfirst(str_replace("_", " ", (string) ($item->estado))) }}</span></td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar cita" aria-label="Consultar cita">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    <a href="{{ route('citas.detalle', ['cita' => $item->id]) }}" class="hw-action-button"
                                       title="Detalle de la cita" aria-label="Detalle de la cita">
                                        <span class="material-symbols-outlined">open_in_new</span>
                                    </a>
                                    @if($puedeEditar)
                                        <button type="button" class="hw-action-button"
                                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="{{ $item->id }}"
                                                title="Editar cita" aria-label="Editar cita">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    @endif
                                    @if($puedeEliminar)
                                        <button type="button" class="hw-action-button hw-action-delete"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                data-id="{{ $item->id }}"
                                                data-nombre="{{ ($item->paciente?->nombres ?? "") . " " . ($item->paciente?->apellidos ?? "") }}"
                                                data-sub="{{ $fmtFecha($item->fecha_cita) . " · " . substr((string) $item->hora_inicio, 0, 5) }}"
                                                title="Eliminar cita" aria-label="Eliminar cita">
                                            <span class="material-symbols-outlined">delete</span>
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

    {{-- MODAL: NUEVO --}}
    @if($puedeCrear)
    <div class="modal fade hw-modal" id="modalNuevo" data-abrir-con-dui tabindex="-1" aria-labelledby="tituloNuevo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit_calendar</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloNuevo">Registrar cita</h2>
                            <p class="hw-modal-subtitle">Completa la información solicitada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formNuevo" method="POST" action="{{ route('citas.insertar') }}" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">group</span>
                            <div>
                              <h3>Participantes</h3>
                              <p>Paciente y doctor de la cita.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              @include('components.campo_dui', ['id' => 'nuevo_dui_paciente'])
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="id_doctor" id="nuevo_id_doctor" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  @foreach(($doctores ?? collect()) as $o)
                                    <option value="{{ $o->id }}">{{ ($o->usuario?->nombres ?? '') . ' ' . ($o->usuario?->apellidos ?? '') . ' — ' . ($o->especialidad?->especialidad ?? '') }}</option>
                                  @endforeach
                                </select>
                                <label for="nuevo_id_doctor">Doctor</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">schedule</span>
                            <div>
                              <h3>Fecha y horario</h3>
                              <p>Cuándo se realizará la atención.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="date" name="fecha_cita" id="nuevo_fecha_cita" class="form-control" placeholder="Fecha" required>
                                <label for="nuevo_fecha_cita">Fecha</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="time" name="hora_inicio" id="nuevo_hora_inicio" class="form-control" placeholder="Hora de inicio" required>
                                <label for="nuevo_hora_inicio">Hora de inicio</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="time" name="hora_fin" id="nuevo_hora_fin" class="form-control" placeholder="Hora de finalización" required>
                                <label for="nuevo_hora_fin">Hora de finalización</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="estado" id="nuevo_estado" class="form-select" required>
                                  <option value="">Sin especificar</option>
                                  <option value="programada" selected>Programada</option>
                                  <option value="completada">Completada</option>
                                  <option value="cancelada">Cancelada</option>
                                  <option value="no_asistio">No asistió</option>
                                  <option value="reprogramada">Reprogramada</option>
                                </select>
                                <label for="nuevo_estado">Estado</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">notes</span>
                            <div>
                              <h3>Detalles</h3>
                              <p>Motivo y observaciones.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="motivo" id="nuevo_motivo" class="form-control" placeholder="Motivo de la consulta"></textarea>
                                <label for="nuevo_motivo">Motivo de la consulta</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="notas" id="nuevo_notas" class="form-control" placeholder="Notas"></textarea>
                                <label for="nuevo_notas">Notas</label>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>

                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: EDITAR --}}
    @if($puedeEditar)
    <div class="modal fade hw-modal" id="modalEditar" tabindex="-1" aria-labelledby="tituloEditar" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloEditar">Editar cita #<span id="editarTituloId">—</span></h2>
                            <p class="hw-modal-subtitle">Actualiza la información y guarda los cambios.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formEditar" method="POST"
                      data-url="{{ route('citas.actualizar', ['cita' => '__ID__']) }}"
                      data-buscar-url="{{ route('citas.buscar', ['id' => '__ID__']) }}"
                      novalidate>
                    @csrf
                    @method('PUT')
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">group</span>
                            <div>
                              <h3>Participantes</h3>
                              <p>Paciente y doctor de la cita.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              @include('components.campo_dui', ['id' => 'editar_dui_paciente'])
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="id_doctor" id="editar_id_doctor" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  @foreach(($doctores ?? collect()) as $o)
                                    <option value="{{ $o->id }}">{{ ($o->usuario?->nombres ?? '') . ' ' . ($o->usuario?->apellidos ?? '') . ' — ' . ($o->especialidad?->especialidad ?? '') }}</option>
                                  @endforeach
                                </select>
                                <label for="editar_id_doctor">Doctor</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">schedule</span>
                            <div>
                              <h3>Fecha y horario</h3>
                              <p>Cuándo se realizará la atención.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="date" name="fecha_cita" id="editar_fecha_cita" class="form-control" placeholder="Fecha" required>
                                <label for="editar_fecha_cita">Fecha</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="time" name="hora_inicio" id="editar_hora_inicio" class="form-control" placeholder="Hora de inicio" required>
                                <label for="editar_hora_inicio">Hora de inicio</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="time" name="hora_fin" id="editar_hora_fin" class="form-control" placeholder="Hora de finalización" required>
                                <label for="editar_hora_fin">Hora de finalización</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="estado" id="editar_estado" class="form-select" required>
                                  <option value="">Sin especificar</option>
                                  <option value="programada" selected>Programada</option>
                                  <option value="completada">Completada</option>
                                  <option value="cancelada">Cancelada</option>
                                  <option value="no_asistio">No asistió</option>
                                  <option value="reprogramada">Reprogramada</option>
                                </select>
                                <label for="editar_estado">Estado</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">notes</span>
                            <div>
                              <h3>Detalles</h3>
                              <p>Motivo y observaciones.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="motivo" id="editar_motivo" class="form-control" placeholder="Motivo de la consulta"></textarea>
                                <label for="editar_motivo">Motivo de la consulta</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="notas" id="editar_notas" class="form-control" placeholder="Notas"></textarea>
                                <label for="editar_notas">Notas</label>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>

                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: CONSULTA --}}
    <div class="modal fade hw-modal" id="modalVer" tabindex="-1" aria-labelledby="tituloVer" aria-hidden="true"
         data-buscar-url="{{ route('citas.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de cita</h2>
                            <p class="hw-modal-subtitle">Información completa del registro.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                <div class="modal-body hw-modal-body">
                    <dl class="hw-detail-grid">
                        <div class="hw-detail-item"><dt>Paciente</dt><dd data-ver="paciente.nombres+paciente.apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>Doctor</dt><dd data-ver="doctor.usuario.nombres+doctor.usuario.apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>Especialidad</dt><dd data-ver="doctor.especialidad.especialidad">—</dd></div>
                        <div class="hw-detail-item"><dt>Fecha</dt><dd data-ver="fecha_cita" data-formato="fecha">—</dd></div>
                        <div class="hw-detail-item"><dt>Hora de inicio</dt><dd data-ver="hora_inicio" data-formato="hora">—</dd></div>
                        <div class="hw-detail-item"><dt>Hora de finalización</dt><dd data-ver="hora_fin" data-formato="hora">—</dd></div>
                        <div class="hw-detail-item"><dt>Estado</dt><dd data-ver="estado">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Motivo</dt><dd data-ver="motivo">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Notas</dt><dd data-ver="notas">—</dd></div>
                    </dl>
                </div>

                <div class="hw-modal-footer">
                    <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: ELIMINAR --}}
    @if($puedeEliminar)
    <div class="modal fade hw-modal hw-delete-modal" id="modalEliminar" tabindex="-1" aria-labelledby="tituloEliminar" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="hw-delete-content">
                    <div class="hw-delete-icon"><span class="material-symbols-outlined">delete</span></div>

                    <button type="button" class="hw-modal-close hw-delete-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>

                    <div class="hw-delete-heading">
                        <span class="hw-delete-overline">Confirmación requerida</span>
                        <h2 id="tituloEliminar">¿Eliminar esta cita?</h2>
                        <p>Esta acción no se puede deshacer desde la interfaz.</p>
                    </div>

                    <form id="formEliminar" method="POST"
                          data-url="{{ route('citas.eliminar', ['cita' => '__ID__']) }}" novalidate>
                        @csrf
                        @method('DELETE')
                        <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                        <div class="hw-delete-identity">
                            <div class="hw-delete-avatar"><span class="material-symbols-outlined">person</span></div>
                            <div class="hw-delete-data">
                                <span class="hw-delete-name" id="eliminar_nombre">—</span>
                                <span class="hw-delete-sub" id="eliminar_sub">—</span>
                                <span class="hw-delete-id">Identificador: #<span id="eliminar_id">—</span></span>
                            </div>
                        </div>

                        <div class="hw-delete-notice">
                            <span class="material-symbols-outlined">info</span>
                            <p>La cita se eliminará de la agenda. Considera cambiar su estado a cancelada para conservar el historial.</p>
                        </div>

                        <div class="hw-delete-actions">
                            <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Conservar</button>
                            <button type="submit" class="btn hw-delete-confirm">
                                <span class="material-symbols-outlined">delete</span>
                                Eliminar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== ESPECIFICACIÓN ORIGINAL (conservada por generar-frontend.sh) ===== --}}
    {{--
        VISTA: Gestión y programación de citas médicas.

        OBJETIVO:
        Permitir consultar, programar y administrar las citas de la clínica.

        CONTENIDO:
        1. ENCABEZADO:
           - Título "Agenda de citas".
           - Botón para programar una cita.

        2. FILTROS:
           - Fecha o rango de fechas.
           - Paciente.
           - Doctor.
           - Estado de la cita.
           - Tipo de vista: calendario o listado.

        3. CALENDARIO:
           - Vista mensual, semanal o diaria.
           - Mostrar citas según fecha y hora.
           - Identificar citas mediante colores según su estado.
           - Diferenciar horarios disponibles y ocupados.

        4. TABLA ALTERNATIVA:
           - Fecha.
           - Hora.
           - Paciente.
           - Doctor.
           - Motivo de consulta.
           - Estado.
           - Acciones.

        5. MODALES:
           - Programar cita.
           - Editar cita.
           - Consultar detalles.
           - Confirmar cancelación o eliminación según la lógica del sistema.

        6. FORMULARIO DE CITA:
           - Seleccionar paciente.
           - Seleccionar doctor.
           - Seleccionar fecha.
           - Seleccionar horario disponible.
           - Motivo de consulta.
           - Observaciones, si corresponde.

        INTERACCIÓN:
        - Consultar disponibilidad de horarios.
        - Evitar conflictos de agenda.
        - Actualizar el calendario después de cada operación.
        - Acceder al detalle de una cita.

        CONSIDERACIONES:
        - El estado de una cita debe respetar los valores definidos en el modelo.
        - Validar que el doctor tenga disponibilidad.
        - Validar que no existan citas incompatibles en el mismo horario.
        - La recepción administra las citas; el médico consulta su agenda
          según los permisos establecidos.
    --}}
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-dui.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/cita/citas.js') }}"></script>
@endpush
