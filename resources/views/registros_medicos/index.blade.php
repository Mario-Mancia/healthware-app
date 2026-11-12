{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Registros Médicos')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['medico']);
        $puedeEditar = false;
        $puedeEliminar = in_array($rolActual, ['administrador']);
        $lista = $registrosMedicos ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">clinical_notes</span>
                </div>
                <div>
                    <h1 class="hw-title">Registros médicos</h1>
                    <p class="hw-subtitle">Consulta clínica de las atenciones realizadas.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">note_add</span>
                    Nuevo registro médico
                </button>
            @endif
            @if($rolActual === 'administrador')
                <a href="{{ route('registros_medicos.papelera') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    Papelera
                </a>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Historial de atenciones</h2>
                    <p class="hw-surface-description">Registros clínicos, ordenados por fecha.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">clinical_notes</span>
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
                <select class="form-select" data-filtro-columna="3" aria-label="Filtrar por doctor">
                    <option value="">Doctor: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-registros-medicos" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Motivo</th>
                            <th scope="col">Observaciones</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr>
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>{{ $fmtFecha($item->fecha_registro, 'd/m/Y H:i') }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">person</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ ($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? '') }}</span>
                                </div>
                              </div>
                            </td>
                            <td>{{ ($item->doctor?->usuario?->nombres ?? '') . ' ' . ($item->doctor?->usuario?->apellidos ?? '') ?? "—" }}</td>
                            <td class="hw-muted" title="{{ $item->motivo_consulta }}">{{ \Illuminate\Support\Str::limit((string) ($item->motivo_consulta ?? ""), 70) ?: "—" }}</td>
                            <td class="hw-muted" title="{{ $item->observaciones }}">{{ \Illuminate\Support\Str::limit((string) ($item->observaciones ?? ""), 70) ?: "—" }}</td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar registro médico" aria-label="Consultar registro médico">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    <a href="{{ route('pacientes.expediente', ['paciente' => $item->id_paciente]) }}" class="hw-action-button"
                                       title="Expediente del paciente" aria-label="Expediente del paciente">
                                        <span class="material-symbols-outlined">folder_shared</span>
                                    </a>
                                    @if($puedeEliminar)
                                        <button type="button" class="hw-action-button hw-action-delete"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                data-id="{{ $item->id }}"
                                                data-nombre="{{ ($item->paciente?->nombres ?? "") . " " . ($item->paciente?->apellidos ?? "") }}"
                                                data-sub="{{ $fmtFecha($item->fecha_registro, "d/m/Y H:i") }}"
                                                title="Eliminar registro médico" aria-label="Eliminar registro médico">
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
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">note_add</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloNuevo">Registrar registro médico</h2>
                            <p class="hw-modal-subtitle">Completa la información solicitada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formNuevo" method="POST" action="{{ route('registros_medicos.insertar') }}" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">event_note</span>
                            <div>
                              <h3>Atención</h3>
                              <p>Paciente, doctor y cita asociada.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              @include('components.campo_dui', ['id' => 'nuevo_dui_paciente'])
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="id_cita" id="nuevo_id_cita" class="form-select" data-dui-citas>
                                  <option value="" selected>Sin especificar</option>
                                </select>
                                <label for="nuevo_id_cita">Cita asociada</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="datetime-local" name="fecha_registro" id="nuevo_fecha_registro" class="form-control" placeholder="Fecha del registro">
                                <label for="nuevo_fecha_registro">Fecha del registro</label>
                              </div>
                              <div class="hw-field-hint">Déjalo en blanco para usar la fecha actual.</div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">clinical_notes</span>
                            <div>
                              <h3>Consulta</h3>
                              <p>Motivo, notas clínicas y observaciones.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="motivo_consulta" id="nuevo_motivo_consulta" class="form-control" placeholder="Motivo de consulta"></textarea>
                                <label for="nuevo_motivo_consulta">Motivo de consulta</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="notas_clinicas" id="nuevo_notas_clinicas" class="form-control" placeholder="Notas clínicas"></textarea>
                                <label for="nuevo_notas_clinicas">Notas clínicas</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="observaciones" id="nuevo_observaciones" class="form-control" placeholder="Observaciones"></textarea>
                                <label for="nuevo_observaciones">Observaciones</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">monitor_heart</span>
                            <div>
                              <h3>Signos vitales</h3>
                              <p>Opcional. Se guardan junto con el registro.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[peso]" id="nuevo_signos_vitales_peso_" class="form-control" placeholder="Peso">
                                <label for="nuevo_signos_vitales_peso_">Peso</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[altura]" id="nuevo_signos_vitales_altura_" class="form-control" placeholder="Altura">
                                <label for="nuevo_signos_vitales_altura_">Altura</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[temperatura]" id="nuevo_signos_vitales_temperatura_" class="form-control" placeholder="Temperatura">
                                <label for="nuevo_signos_vitales_temperatura_">Temperatura</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[saturacion_oxigeno]" id="nuevo_signos_vitales_saturacion_oxigeno_" class="form-control" placeholder="Saturación O₂">
                                <label for="nuevo_signos_vitales_saturacion_oxigeno_">Saturación O₂</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[frecuencia_cardiaca]" id="nuevo_signos_vitales_frecuencia_cardiaca_" class="form-control" placeholder="Frec. cardíaca">
                                <label for="nuevo_signos_vitales_frecuencia_cardiaca_">Frec. cardíaca</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[frecuencia_respiratoria]" id="nuevo_signos_vitales_frecuencia_respiratoria_" class="form-control" placeholder="Frec. respiratoria">
                                <label for="nuevo_signos_vitales_frecuencia_respiratoria_">Frec. respiratoria</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[presion_sistolica]" id="nuevo_signos_vitales_presion_sistolica_" class="form-control" placeholder="Presión sistólica">
                                <label for="nuevo_signos_vitales_presion_sistolica_">Presión sistólica</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="number" name="signos_vitales[presion_diastolica]" id="nuevo_signos_vitales_presion_diastolica_" class="form-control" placeholder="Presión diastólica">
                                <label for="nuevo_signos_vitales_presion_diastolica_">Presión diastólica</label>
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

    {{-- MODAL: CONSULTA --}}
    <div class="modal fade hw-modal" id="modalVer" tabindex="-1" aria-labelledby="tituloVer" aria-hidden="true"
         data-buscar-url="{{ route('registros_medicos.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de registro médico</h2>
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
                        <div class="hw-detail-item"><dt>Fecha del registro</dt><dd data-ver="fecha_registro" data-formato="fechahora">—</dd></div>
                        <div class="hw-detail-item"><dt>Cita asociada</dt><dd data-ver="cita.fecha_cita" data-formato="fecha">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Motivo de consulta</dt><dd data-ver="motivo_consulta">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Notas clínicas</dt><dd data-ver="notas_clinicas">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Observaciones</dt><dd data-ver="observaciones">—</dd></div>
                        <div class="hw-detail-item"><dt>Peso</dt><dd data-ver="signos_vitales.peso">—</dd></div>
                        <div class="hw-detail-item"><dt>Altura</dt><dd data-ver="signos_vitales.altura">—</dd></div>
                        <div class="hw-detail-item"><dt>Temperatura</dt><dd data-ver="signos_vitales.temperatura">—</dd></div>
                        <div class="hw-detail-item"><dt>Saturación O₂</dt><dd data-ver="signos_vitales.saturacion_oxigeno">—</dd></div>
                        <div class="hw-detail-item"><dt>Frecuencia cardíaca</dt><dd data-ver="signos_vitales.frecuencia_cardiaca">—</dd></div>
                        <div class="hw-detail-item"><dt>Frecuencia respiratoria</dt><dd data-ver="signos_vitales.frecuencia_respiratoria">—</dd></div>
                        <div class="hw-detail-item"><dt>Presión</dt><dd data-ver="signos_vitales.presion_sistolica+signos_vitales.presion_diastolica">—</dd></div>
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
                        <h2 id="tituloEliminar">¿Eliminar este registro médico?</h2>
                        <p>Esta acción no se puede deshacer desde la interfaz.</p>
                    </div>

                    <form id="formEliminar" method="POST"
                          data-url="{{ route('registros_medicos.eliminar', ['registroMedico' => '__ID__']) }}" novalidate>
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
                            <p>Los datos clínicos requieren trazabilidad. Confirma que la eliminación es correcta.</p>
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
        VISTA: Administración y consulta de registros médicos.

        OBJETIVO:
        Proporcionar una interfaz para consultar los registros clínicos
        y acceder al expediente correspondiente.

        CONTENIDO:
        1. ENCABEZADO:
           - Título "Registros médicos".
           - Descripción del módulo.

        2. FILTROS:
           - Paciente.
           - Doctor responsable.
           - Fecha o rango de fechas.
           - Diagnóstico asociado, si resulta útil.

        3. TABLA:
           - Identificador del registro.
           - Paciente.
           - Doctor responsable.
           - Fecha de atención.
           - Motivo de consulta.
           - Resumen u observación.
           - Acciones.

        4. FORMULARIO / MODAL:
           - Seleccionar paciente.
           - Seleccionar doctor, cuando corresponda.
           - Asociar cita, si existe.
           - Registrar motivo de consulta.
           - Registrar observaciones y evolución.
           - Guardar el registro.

        5. ACCIONES:
           - Consultar registro.
           - Acceder al expediente clínico.
           - Consultar diagnósticos asociados.
           - Eliminar o anular según las reglas del sistema.

        CONSIDERACIONES:
        - Los diagnósticos asociados se administran mediante la relación
          registro_medico_diagnosticos.
        - No duplicar la gestión de diagnósticos en esta pantalla.
        - El expediente del paciente será la interfaz principal para
          consultar su historial clínico.
        - Proteger los datos clínicos y mantener trazabilidad de operaciones.
        - No asumir que todos los usuarios pueden crear o eliminar registros.
    --}}
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-dui.js') }}"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/registro_medico/registroMedico.js') }}"></script>
@endpush
