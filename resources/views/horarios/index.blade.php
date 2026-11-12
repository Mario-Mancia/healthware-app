{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Horarios Doctores')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['administrador','medico']);
        $puedeEditar = in_array($rolActual, ['administrador','medico']);
        $puedeEliminar = in_array($rolActual, ['administrador']);
        $lista = $horarios ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
                <div>
                    <h1 class="hw-title">Horarios médicos</h1>
                    <p class="hw-subtitle">Disponibilidad laboral de los doctores.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">add_circle</span>
                    Nuevo horario
                </button>
            @endif
        </header>

        {{-- VISTA SEMANAL --}}
        @php
            $diasSemana = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
        @endphp
        <section class="hw-week" aria-label="Vista semanal de horarios">
            @foreach($diasSemana as $numero => $nombreDia)
                <div class="hw-week-day">
                    <h3>{{ $nombreDia }}</h3>
                    @forelse($lista->where('dia_semana', $numero)->sortBy('hora_inicio') as $franja)
                        <div class="hw-week-slot">
                            <strong>{{ substr((string) $franja->hora_inicio, 0, 5) }} – {{ substr((string) $franja->hora_fin, 0, 5) }}</strong>
                            <span>{{ ($franja->doctor?->usuario?->nombres ?? '') . ' ' . ($franja->doctor?->usuario?->apellidos ?? '') }}</span>
                        </div>
                    @empty
                        <span class="hw-week-empty">Sin horarios</span>
                    @endforelse
                </div>
            @endforeach
        </section>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Horarios registrados</h2>
                    <p class="hw-surface-description">Intervalos de atención por doctor y día de la semana.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">schedule</span>
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
                <select class="form-select" data-filtro-columna="1" aria-label="Filtrar por doctor">
                    <option value="">Doctor: todos</option>
                </select>
                <select class="form-select" data-filtro-columna="2" aria-label="Filtrar por día">
                    <option value="">Día: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-horarios" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Día</th>
                            <th scope="col">Inicio</th>
                            <th scope="col">Fin</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr>
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">person</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ ($item->doctor?->usuario?->nombres ?? '') . ' ' . ($item->doctor?->usuario?->apellidos ?? '') }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ($item->doctor?->especialidad?->especialidad ?? ""), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td><span class="hw-chip" data-filtro-valor>{{ [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'][(int) $item->dia_semana] ?? '—' }}</span></td>
                            <td class="hw-mono">{{ substr((string) $item->hora_inicio, 0, 5) ?? "—" }}</td>
                            <td class="hw-mono">{{ substr((string) $item->hora_fin, 0, 5) ?? "—" }}</td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar horario" aria-label="Consultar horario">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @if($puedeEditar)
                                        <button type="button" class="hw-action-button"
                                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="{{ $item->id }}"
                                                title="Editar horario" aria-label="Editar horario">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    @endif
                                    @if($puedeEliminar)
                                        <button type="button" class="hw-action-button hw-action-delete"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                data-id="{{ $item->id }}"
                                                data-nombre="{{ ($item->doctor?->usuario?->nombres ?? "") . " " . ($item->doctor?->usuario?->apellidos ?? "") }}"
                                                data-sub="{{ substr((string) $item->hora_inicio, 0, 5) . " - " . substr((string) $item->hora_fin, 0, 5) }}"
                                                title="Eliminar horario" aria-label="Eliminar horario">
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
    <div class="modal fade hw-modal" id="modalNuevo" tabindex="-1" aria-labelledby="tituloNuevo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">add_circle</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloNuevo">Registrar horario</h2>
                            <p class="hw-modal-subtitle">Completa la información solicitada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formNuevo" method="POST" action="{{ route('horarios.insertar') }}" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">event_available</span>
                            <div>
                              <h3>Disponibilidad</h3>
                              <p>Doctor, día y rango de atención.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
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
                            <div class="col-md-4">
                              <div class="form-floating">
                                <select name="dia_semana" id="nuevo_dia_semana" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  <option value="1">Lunes</option>
                                  <option value="2">Martes</option>
                                  <option value="3">Miércoles</option>
                                  <option value="4">Jueves</option>
                                  <option value="5">Viernes</option>
                                  <option value="6">Sábado</option>
                                  <option value="7">Domingo</option>
                                </select>
                                <label for="nuevo_dia_semana">Día de la semana</label>
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
                            <h2 class="hw-modal-title" id="tituloEditar">Editar horario #<span id="editarTituloId">—</span></h2>
                            <p class="hw-modal-subtitle">Actualiza la información y guarda los cambios.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formEditar" method="POST"
                      data-url="{{ route('horarios.actualizar', ['horario' => '__ID__']) }}"
                      data-buscar-url="{{ route('horarios.buscar', ['id' => '__ID__']) }}"
                      novalidate>
                    @csrf
                    @method('PUT')
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">event_available</span>
                            <div>
                              <h3>Disponibilidad</h3>
                              <p>Doctor, día y rango de atención.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
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
                            <div class="col-md-4">
                              <div class="form-floating">
                                <select name="dia_semana" id="editar_dia_semana" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  <option value="1">Lunes</option>
                                  <option value="2">Martes</option>
                                  <option value="3">Miércoles</option>
                                  <option value="4">Jueves</option>
                                  <option value="5">Viernes</option>
                                  <option value="6">Sábado</option>
                                  <option value="7">Domingo</option>
                                </select>
                                <label for="editar_dia_semana">Día de la semana</label>
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
         data-buscar-url="{{ route('horarios.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de horario</h2>
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
                        <div class="hw-detail-item"><dt>Doctor</dt><dd data-ver="doctor.usuario.nombres+doctor.usuario.apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>Especialidad</dt><dd data-ver="doctor.especialidad.especialidad">—</dd></div>
                        <div class="hw-detail-item"><dt>Hora de inicio</dt><dd data-ver="hora_inicio" data-formato="hora">—</dd></div>
                        <div class="hw-detail-item"><dt>Hora de finalización</dt><dd data-ver="hora_fin" data-formato="hora">—</dd></div>
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
                        <h2 id="tituloEliminar">¿Eliminar este horario?</h2>
                        <p>Esta acción no se puede deshacer desde la interfaz.</p>
                    </div>

                    <form id="formEliminar" method="POST"
                          data-url="{{ route('horarios.eliminar', ['horario' => '__ID__']) }}" novalidate>
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
                            <p>El horario dejará de servir como referencia de disponibilidad para programar citas.</p>
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
        VISTA: Administración de horarios médicos.

        OBJETIVO:
        Gestionar la disponibilidad laboral de los doctores.

        CONTENIDO:
        1. ENCABEZADO:
           - Título "Horarios médicos".
           - Botón para registrar un horario.

        2. FILTROS:
           - Doctor.
           - Día de la semana.
           - Especialidad, si resulta útil.

        3. VISTA DE HORARIOS:
           - Vista semanal organizada por días.
           - Identificación del doctor.
           - Hora de inicio y finalización.
           - Representación visual de los intervalos.

        4. TABLA:
           - Doctor.
           - Día de la semana.
           - Hora de inicio.
           - Hora de finalización.
           - Acciones.

        5. MODALES:
           - Registrar horario.
           - Editar horario.
           - Confirmar eliminación.

        6. FORMULARIO:
           - Seleccionar doctor.
           - Seleccionar día de la semana.
           - Hora de inicio.
           - Hora de finalización.

        INTERACCIÓN:
        - Consultar horarios de un doctor.
        - Crear, editar y eliminar horarios.
        - Mostrar los horarios agrupados por doctor o día.

        CONSIDERACIONES:
        - Validar que la hora de finalización sea posterior a la inicial.
        - Evitar intervalos superpuestos para un mismo doctor.
        - Respetar el esquema de días de semana definido en la base de datos.
        - Los horarios sirven como referencia para la disponibilidad
          al programar citas.
    --}}
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/horarios/horarios.js') }}"></script>
@endpush
