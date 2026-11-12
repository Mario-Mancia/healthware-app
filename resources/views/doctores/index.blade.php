{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Doctores')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        // Los doctores se crean junto con su usuario (módulo Usuarios, rol Médico).
        $puedeCrear = false;
        $puedeEditar = in_array($rolActual, ['administrador']);
        $puedeEliminar = false;
        $lista = $doctores ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">medical_information</span>
                </div>
                <div>
                    <h1 class="hw-title">Doctores</h1>
                    <p class="hw-subtitle">Personal médico, especialidades y datos profesionales.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">add_circle</span>
                    Nuevo doctor
                </button>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Directorio médico</h2>
                    <p class="hw-surface-description">Consulta los doctores registrados y sus especialidades.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">medical_information</span>
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
                <select class="form-select" data-filtro-columna="2" aria-label="Filtrar por especialidad">
                    <option value="">Especialidad: todos</option>
                </select>
                <select class="form-select" data-filtro-columna="5" aria-label="Filtrar por sexo">
                    <option value="">Sexo: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-doctores" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Especialidad</th>
                            <th scope="col">DUI</th>
                            <th scope="col">Licencia</th>
                            <th scope="col">Sexo</th>
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
                                  <span class="hw-fullname" data-filtro-valor>{{ ($item->usuario?->nombres ?? '') . ' ' . ($item->usuario?->apellidos ?? '') }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ($item->usuario?->correo ?? ""), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td><span class="hw-chip" data-filtro-valor>{{ $item->especialidad?->especialidad ?? "N/A" }}</span></td>
                            <td class="hw-mono">{{ $item->dui ?? "—" }}</td>
                            <td class="hw-mono">{{ $item->numero_licencia ?? "—" }}</td>
                            <td><span data-filtro-valor>{{ $item->sexo === 'M' ? 'Masculino' : 'Femenino' }}</span></td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar doctor" aria-label="Consultar doctor">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @if($puedeEditar)
                                        <button type="button" class="hw-action-button"
                                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="{{ $item->id }}"
                                                title="Editar doctor" aria-label="Editar doctor">
                                            <span class="material-symbols-outlined">edit</span>
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

    {{-- MODAL: EDITAR --}}
    @if($puedeEditar)
    <div class="modal fade hw-modal" id="modalEditar" tabindex="-1" aria-labelledby="tituloEditar" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloEditar">Editar doctor #<span id="editarTituloId">—</span></h2>
                            <p class="hw-modal-subtitle">Actualiza la información y guarda los cambios.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formEditar" method="POST"
                      data-url="{{ route('doctores.actualizar', ['doctor' => '__ID__']) }}"
                      data-buscar-url="{{ route('doctores.buscar', ['id' => '__ID__']) }}"
                      novalidate>
                    @csrf
                    @method('PUT')
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">manage_accounts</span>
                            <div>
                              <h3>Cuenta y especialidad</h3>
                              <p>Usuario del sistema asociado y área de práctica.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="usuario_id" id="editar_usuario_id" class="form-select" required disabled>
                                  <option value="" disabled selected></option>
                                  @foreach(($usuarios ?? collect()) as $o)
                                    <option value="{{ $o->id }}">{{ ($o->nombres ?? '') . ' ' . ($o->apellidos ?? '') . ' · ' . ($o->correo ?? '') }}</option>
                                  @endforeach
                                </select>
                                <label for="editar_usuario_id">Cuenta de usuario</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="especialidad_id" id="editar_especialidad_id" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  @foreach(($especialidades ?? collect()) as $o)
                                    <option value="{{ $o->id }}">{{ $o->especialidad }}</option>
                                  @endforeach
                                </select>
                                <label for="editar_especialidad_id">Especialidad</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">badge</span>
                            <div>
                              <h3>Datos profesionales</h3>
                              <p>Identificación, licencia y perfil.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="numero_licencia" id="editar_numero_licencia" class="form-control" placeholder="Número de licencia" maxlength="50" required>
                                <label for="editar_numero_licencia">Número de licencia</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="dui" id="editar_dui" class="form-control" placeholder="DUI" maxlength="10" required>
                                <label for="editar_dui">DUI</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="date" name="fecha_nacimiento" id="editar_fecha_nacimiento" class="form-control" placeholder="Fecha de nacimiento" required>
                                <label for="editar_fecha_nacimiento">Fecha de nacimiento</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="sexo" id="editar_sexo" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  <option value="M">Masculino</option>
                                  <option value="F">Femenino</option>
                                </select>
                                <label for="editar_sexo">Sexo</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="descripcion_profesional" id="editar_descripcion_profesional" class="form-control" placeholder="Descripción profesional"></textarea>
                                <label for="editar_descripcion_profesional">Descripción profesional</label>
                              </div>
                              <div class="hw-field-hint">Experiencia, enfoque o información relevante.</div>
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
         data-buscar-url="{{ route('doctores.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de doctor</h2>
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
                        <div class="hw-detail-item"><dt>Doctor</dt><dd data-ver="usuario.nombres+usuario.apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>Correo</dt><dd data-ver="usuario.correo">—</dd></div>
                        <div class="hw-detail-item"><dt>Especialidad</dt><dd data-ver="especialidad.especialidad">—</dd></div>
                        <div class="hw-detail-item"><dt>DUI</dt><dd data-ver="dui">—</dd></div>
                        <div class="hw-detail-item"><dt>Licencia</dt><dd data-ver="numero_licencia">—</dd></div>
                        <div class="hw-detail-item"><dt>Fecha de nacimiento</dt><dd data-ver="fecha_nacimiento" data-formato="fecha">—</dd></div>
                        <div class="hw-detail-item"><dt>Sexo</dt><dd data-ver="sexo" data-formato="sexo">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Descripción profesional</dt><dd data-ver="descripcion_profesional">—</dd></div>
                    </dl>
                </div>

                <div class="hw-modal-footer">
                    <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ESPECIFICACIÓN ORIGINAL (conservada por generar-frontend.sh) ===== --}}
    {{--
        VISTA: Gestión y consulta de doctores.

        OBJETIVO:
        Mostrar el personal médico registrado y permitir administrar
        su información profesional.

        CONTENIDO:
        1. ENCABEZADO:
           - Título "Doctores".
           - Botón para registrar un doctor, únicamente para administradores.

        2. FILTROS:
           - Nombre del doctor.
           - Especialidad.
           - Estado de su cuenta o registro.

        3. TABLA:
           - Identificador del doctor.
           - Nombre completo.
           - DUI.
           - Número de licencia profesional.
           - Especialidad.
           - Teléfono o correo asociado.
           - Acciones.

        4. MODALES:
           - Registrar doctor.
           - Editar información profesional.
           - Consultar información resumida.

        5. REGISTRO DEL DOCTOR:
           - Seleccionar o crear el usuario relacionado.
           - Asociar especialidad.
           - DUI.
           - Número de licencia.
           - Fecha de nacimiento.
           - Sexo.
           - Descripción profesional.

        6. ACCIONES:
           - Consultar perfil.
           - Editar datos.
           - Consultar horarios.
           - Consultar agenda.

        CONSIDERACIONES:
        - El doctor está relacionado con un usuario del sistema.
        - No confundir el registro profesional con la cuenta de acceso.
        - La creación del doctor debe contemplar la relación con su usuario.
        - No eliminar físicamente doctores.
        - Las operaciones de creación y edición corresponden al administrador.
    --}}
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/doctor/doctores.js') }}"></script>
@endpush
