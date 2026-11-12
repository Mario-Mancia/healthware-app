{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Medicamentos')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['administrador']);
        $puedeEditar = in_array($rolActual, ['administrador']);
        $puedeEliminar = in_array($rolActual, ['administrador']);
        $lista = $medicamentos ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">medication</span>
                </div>
                <div>
                    <h1 class="hw-title">Catálogo de medicamentos</h1>
                    <p class="hw-subtitle">Medicamentos disponibles al emitir recetas médicas.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">add_circle</span>
                    Nuevo medicamento
                </button>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Medicamentos registrados</h2>
                    <p class="hw-surface-description">Busca y administra el catálogo de medicamentos.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">medication</span>
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
                <select class="form-select" data-filtro-columna="3" aria-label="Filtrar por presentación">
                    <option value="">Presentación: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-medicamentos" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Principio activo</th>
                            <th scope="col">Presentación</th>
                            <th scope="col">Concentración</th>
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
                                <div class="hw-avatar"><span class="material-symbols-outlined">medication</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ $item->nombre }}</span>
                                </div>
                              </div>
                            </td>
                            <td>{{ $item->principio_activo ?? "—" }}</td>
                            <td><span class="hw-chip" data-filtro-valor>{{ $item->presentacion ?? "N/A" }}</span></td>
                            <td class="hw-mono">{{ $item->concentracion ?? "—" }}</td>
                            <td class="hw-muted" title="{{ $item->descripcion }}">{{ \Illuminate\Support\Str::limit((string) ($item->descripcion ?? ""), 70) ?: "—" }}</td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar medicamento" aria-label="Consultar medicamento">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @if($puedeEditar)
                                        <button type="button" class="hw-action-button"
                                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="{{ $item->id }}"
                                                title="Editar medicamento" aria-label="Editar medicamento">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    @endif
                                    @if($puedeEliminar)
                                        <button type="button" class="hw-action-button hw-action-delete"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                data-id="{{ $item->id }}"
                                                data-nombre="{{ $item->nombre }}"
                                                data-sub="{{ ($item->principio_activo ?? "") . " " . ($item->concentracion ?? "") }}"
                                                title="Eliminar medicamento" aria-label="Eliminar medicamento">
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
                            <h2 class="hw-modal-title" id="tituloNuevo">Registrar medicamento</h2>
                            <p class="hw-modal-subtitle">Completa la información solicitada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formNuevo" method="POST" action="{{ route('medicamentos.insertar') }}" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">medication</span>
                            <div>
                              <h3>Identificación</h3>
                              <p>Nombre y principio activo del medicamento.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="nombre" id="nuevo_nombre" class="form-control" placeholder="Nombre" maxlength="50" required>
                                <label for="nuevo_nombre">Nombre</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="principio_activo" id="nuevo_principio_activo" class="form-control" placeholder="Principio activo" maxlength="150">
                                <label for="nuevo_principio_activo">Principio activo</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">pill</span>
                            <div>
                              <h3>Presentación</h3>
                              <p>Forma farmacéutica y concentración.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="presentacion" id="nuevo_presentacion" class="form-control" placeholder="Presentación" maxlength="100">
                                <label for="nuevo_presentacion">Presentación</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="concentracion" id="nuevo_concentracion" class="form-control" placeholder="Concentración" maxlength="100">
                                <label for="nuevo_concentracion">Concentración</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="descripcion" id="nuevo_descripcion" class="form-control" placeholder="Descripción" maxlength="150"></textarea>
                                <label for="nuevo_descripcion">Descripción</label>
                              </div>
                              <div class="hw-field-hint">Máximo 150 caracteres.</div>
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
                            <h2 class="hw-modal-title" id="tituloEditar">Editar medicamento #<span id="editarTituloId">—</span></h2>
                            <p class="hw-modal-subtitle">Actualiza la información y guarda los cambios.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formEditar" method="POST"
                      data-url="{{ route('medicamentos.actualizar', ['medicamento' => '__ID__']) }}"
                      data-buscar-url="{{ route('medicamentos.buscar', ['id' => '__ID__']) }}"
                      novalidate>
                    @csrf
                    @method('PUT')
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">medication</span>
                            <div>
                              <h3>Identificación</h3>
                              <p>Nombre y principio activo del medicamento.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="nombre" id="editar_nombre" class="form-control" placeholder="Nombre" maxlength="50" required>
                                <label for="editar_nombre">Nombre</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="principio_activo" id="editar_principio_activo" class="form-control" placeholder="Principio activo" maxlength="150">
                                <label for="editar_principio_activo">Principio activo</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">pill</span>
                            <div>
                              <h3>Presentación</h3>
                              <p>Forma farmacéutica y concentración.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="presentacion" id="editar_presentacion" class="form-control" placeholder="Presentación" maxlength="100">
                                <label for="editar_presentacion">Presentación</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="concentracion" id="editar_concentracion" class="form-control" placeholder="Concentración" maxlength="100">
                                <label for="editar_concentracion">Concentración</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="descripcion" id="editar_descripcion" class="form-control" placeholder="Descripción" maxlength="150"></textarea>
                                <label for="editar_descripcion">Descripción</label>
                              </div>
                              <div class="hw-field-hint">Máximo 150 caracteres.</div>
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
         data-buscar-url="{{ route('medicamentos.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de medicamento</h2>
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
                        <div class="hw-detail-item"><dt>Nombre</dt><dd data-ver="nombre">—</dd></div>
                        <div class="hw-detail-item"><dt>Principio activo</dt><dd data-ver="principio_activo">—</dd></div>
                        <div class="hw-detail-item"><dt>Presentación</dt><dd data-ver="presentacion">—</dd></div>
                        <div class="hw-detail-item"><dt>Concentración</dt><dd data-ver="concentracion">—</dd></div>
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
                        <h2 id="tituloEliminar">¿Eliminar este medicamento?</h2>
                        <p>Esta acción no se puede deshacer desde la interfaz.</p>
                    </div>

                    <form id="formEliminar" method="POST"
                          data-url="{{ route('medicamentos.eliminar', ['medicamento' => '__ID__']) }}" novalidate>
                        @csrf
                        @method('DELETE')
                        <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                        <div class="hw-delete-identity">
                            <div class="hw-delete-avatar"><span class="material-symbols-outlined">medication</span></div>
                            <div class="hw-delete-data">
                                <span class="hw-delete-name" id="eliminar_nombre">—</span>
                                <span class="hw-delete-sub" id="eliminar_sub">—</span>
                                <span class="hw-delete-id">Identificador: #<span id="eliminar_id">—</span></span>
                            </div>
                        </div>

                        <div class="hw-delete-notice">
                            <span class="material-symbols-outlined">info</span>
                            <p>Es un catálogo, no un inventario. No elimines medicamentos usados en recetas.</p>
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
        VISTA: Catálogo de medicamentos.

        OBJETIVO:
        Administrar los medicamentos disponibles para su selección
        al emitir recetas médicas.

        CONTENIDO:
        - Encabezado.
        - Botón para registrar medicamento.
        - Buscador por nombre o principio activo.
        - Tabla con:
          * Identificador.
          * Nombre.
          * Principio activo.
          * Presentación.
          * Concentración.
          * Descripción.
        - Modal de creación.
        - Modal de edición.
        - Modal de consulta detallada.
        - Confirmación de eliminación.

        FORMULARIO:
        - Nombre.
        - Principio activo.
        - Presentación.
        - Concentración.
        - Descripción.

        INTERACCIÓN:
        - Crear, consultar, actualizar y eliminar medicamentos.
        - Permitir su selección al elaborar recetas.

        CONSIDERACIONES:
        - Los medicamentos se relacionan con recetas mediante recetas_items.
        - No confundir este catálogo con un módulo de inventario.
        - No implementar control de existencias si no está contemplado
          en el modelo de datos.
        - Consulta para administrador y médico.
        - Administración del catálogo exclusiva del administrador.
    --}}
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/medicamento/medicamentos.js') }}"></script>
@endpush
