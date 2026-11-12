{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Alergias')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['administrador']);
        $puedeEditar = in_array($rolActual, ['administrador']);
        $puedeEliminar = in_array($rolActual, ['administrador']);
        $lista = $alergias ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">allergy</span>
                </div>
                <div>
                    <h1 class="hw-title">Catálogo de alergias</h1>
                    <p class="hw-subtitle">Alergias que pueden asociarse a los pacientes.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">add_circle</span>
                    Nueva alergia
                </button>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Alergias registradas</h2>
                    <p class="hw-surface-description">Consulta y administra el catálogo de alergias.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">allergy</span>
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
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-alergias" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Alergia</th>
                            <th scope="col">Descripción</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr>
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">allergy</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ $item->alergia }}</span>
                                </div>
                              </div>
                            </td>
                            <td class="hw-muted" title="{{ $item->descripcion }}">{{ \Illuminate\Support\Str::limit((string) ($item->descripcion ?? ""), 70) ?: "—" }}</td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar alergia" aria-label="Consultar alergia">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @if($puedeEditar)
                                        <button type="button" class="hw-action-button"
                                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="{{ $item->id }}"
                                                title="Editar alergia" aria-label="Editar alergia">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    @endif
                                    @if($puedeEliminar)
                                        <button type="button" class="hw-action-button hw-action-delete"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                data-id="{{ $item->id }}"
                                                data-nombre="{{ $item->alergia }}"
                                                data-sub="{{ $item->descripcion ?? "" }}"
                                                title="Eliminar alergia" aria-label="Eliminar alergia">
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
                            <h2 class="hw-modal-title" id="tituloNuevo">Registrar alergia</h2>
                            <p class="hw-modal-subtitle">Completa la información solicitada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formNuevo" method="POST" action="{{ route('alergias.insertar') }}" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">allergy</span>
                            <div>
                              <h3>Información del registro</h3>
                              <p>Datos que identifican este elemento del catálogo.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
                              <div class="form-floating">
                                <input type="text" name="alergia" id="nuevo_alergia" class="form-control" placeholder="Alergia" maxlength="50" required>
                                <label for="nuevo_alergia">Alergia</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="descripcion" id="nuevo_descripcion" class="form-control" placeholder="Descripción" maxlength="250"></textarea>
                                <label for="nuevo_descripcion">Descripción</label>
                              </div>
                              <div class="hw-field-hint">Máximo 250 caracteres.</div>
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
                            <h2 class="hw-modal-title" id="tituloEditar">Editar alergia #<span id="editarTituloId">—</span></h2>
                            <p class="hw-modal-subtitle">Actualiza la información y guarda los cambios.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formEditar" method="POST"
                      data-url="{{ route('alergias.actualizar', ['alergia' => '__ID__']) }}"
                      data-buscar-url="{{ route('alergias.buscar', ['id' => '__ID__']) }}"
                      novalidate>
                    @csrf
                    @method('PUT')
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">allergy</span>
                            <div>
                              <h3>Información del registro</h3>
                              <p>Datos que identifican este elemento del catálogo.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
                              <div class="form-floating">
                                <input type="text" name="alergia" id="editar_alergia" class="form-control" placeholder="Alergia" maxlength="50" required>
                                <label for="editar_alergia">Alergia</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="descripcion" id="editar_descripcion" class="form-control" placeholder="Descripción" maxlength="250"></textarea>
                                <label for="editar_descripcion">Descripción</label>
                              </div>
                              <div class="hw-field-hint">Máximo 250 caracteres.</div>
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
         data-buscar-url="{{ route('alergias.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de alergia</h2>
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
                        <div class="hw-detail-item"><dt>Identificador</dt><dd data-ver="id">—</dd></div>
                        <div class="hw-detail-item"><dt>Alergia</dt><dd data-ver="alergia">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Descripción</dt><dd data-ver="descripcion">—</dd></div>
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
                        <h2 id="tituloEliminar">¿Eliminar esta alergia?</h2>
                        <p>Esta acción no se puede deshacer desde la interfaz.</p>
                    </div>

                    <form id="formEliminar" method="POST"
                          data-url="{{ route('alergias.eliminar', ['alergia' => '__ID__']) }}" novalidate>
                        @csrf
                        @method('DELETE')
                        <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                        <div class="hw-delete-identity">
                            <div class="hw-delete-avatar"><span class="material-symbols-outlined">allergy</span></div>
                            <div class="hw-delete-data">
                                <span class="hw-delete-name" id="eliminar_nombre">—</span>
                                <span class="hw-delete-sub" id="eliminar_sub">—</span>
                                <span class="hw-delete-id">Identificador: #<span id="eliminar_id">—</span></span>
                            </div>
                        </div>

                        <div class="hw-delete-notice">
                            <span class="material-symbols-outlined">info</span>
                            <p>Se quitará la alergia del catálogo. Revisa que no esté asociada a pacientes.</p>
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
        VISTA: Catálogo de alergias.

        OBJETIVO:
        Mantener el listado de alergias que pueden registrarse
        en los expedientes clínicos de los pacientes.

        CONTENIDO:
        - Encabezado.
        - Botón para agregar alergia.
        - Tabla con identificador, nombre y descripción.
        - Búsqueda por nombre.
        - Modal de creación.
        - Modal de edición.
        - Confirmación de eliminación.

        FORMULARIO:
        - Nombre de la alergia.
        - Descripción opcional.

        INTERACCIÓN:
        - Crear, consultar, actualizar y eliminar registros.
        - Presentar errores de validación.

        CONSIDERACIONES:
        - La alergia del catálogo se vincula a pacientes mediante pacientes_alergias.
        - La asociación de una alergia con un paciente se administra
          desde su expediente.
        - La consulta está permitida para administrador y médico.
        - La administración del catálogo corresponde al administrador.
    --}}
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/alergia/alergias.js') }}"></script>
@endpush
