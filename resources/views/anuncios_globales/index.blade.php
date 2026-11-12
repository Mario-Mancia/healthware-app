{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Anuncios')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['administrador']);
        $puedeEditar = in_array($rolActual, ['administrador']);
        $puedeEliminar = false;
        $lista = $anuncios ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">campaign</span>
                </div>
                <div>
                    <h1 class="hw-title">Anuncios globales</h1>
                    <p class="hw-subtitle">Comunicados visibles para el personal de la clínica.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">add_alert</span>
                    Nuevo anuncio
                </button>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Anuncios publicados</h2>
                    <p class="hw-surface-description">Administra el contenido, la prioridad y la vigencia.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">campaign</span>
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
                <select class="form-select" data-filtro-columna="2" aria-label="Filtrar por prioridad">
                    <option value="">Prioridad: todos</option>
                </select>
                <select class="form-select" data-filtro-columna="4" aria-label="Filtrar por estado">
                    <option value="">Estado: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-anuncios-globales" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Anuncio</th>
                            <th scope="col">Prioridad</th>
                            <th scope="col">Vigencia</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr>
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">campaign</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ $item->titulo }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ($item->mensaje ?? ""), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td><span class="hw-status hw-status-{{ ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error', ][(string) ($item->prioridad)] ?? "neutral" }}" data-filtro-valor><span class="hw-status-dot"></span>{{ ucfirst(str_replace("_", " ", (string) ($item->prioridad))) }}</span></td>
                            <td>{{ $fmtFecha($item->inicia_en, 'd/m/Y H:i') }} → {{ $item->finaliza_en ? $fmtFecha($item->finaliza_en, 'd/m/Y H:i') : 'Sin fin' }}</td>
                            <td><span class="hw-status hw-status-{{ $item->activo ? 'success' : 'neutral' }}" data-filtro-valor><span class="hw-status-dot"></span>{{ $item->activo ? 'Activo' : 'Inactivo' }}</span></td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar anuncio" aria-label="Consultar anuncio">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @if($puedeEditar)
                                        <button type="button" class="hw-action-button"
                                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="{{ $item->id }}"
                                                title="Editar anuncio" aria-label="Editar anuncio">
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
    @if($puedeCrear)
    <div class="modal fade hw-modal" id="modalNuevo" tabindex="-1" aria-labelledby="tituloNuevo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">add_alert</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloNuevo">Registrar anuncio</h2>
                            <p class="hw-modal-subtitle">Completa la información solicitada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formNuevo" method="POST" action="{{ route('anuncios_globales.insertar') }}" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">campaign</span>
                            <div>
                              <h3>Contenido</h3>
                              <p>Título y mensaje del anuncio.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
                              <div class="form-floating">
                                <input type="text" name="titulo" id="nuevo_titulo" class="form-control" placeholder="Título" maxlength="150" required>
                                <label for="nuevo_titulo">Título</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="mensaje" id="nuevo_mensaje" class="form-control" placeholder="Mensaje" required></textarea>
                                <label for="nuevo_mensaje">Mensaje</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">event_repeat</span>
                            <div>
                              <h3>Publicación</h3>
                              <p>Prioridad y periodo de vigencia.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-4">
                              <div class="form-floating">
                                <select name="prioridad" id="nuevo_prioridad" class="form-select" required>
                                  <option value="">Sin especificar</option>
                                  <option value="baja">Baja</option>
                                  <option value="normal" selected>Normal</option>
                                  <option value="alta">Alta</option>
                                  <option value="urgente">Urgente</option>
                                </select>
                                <label for="nuevo_prioridad">Prioridad</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="datetime-local" name="inicia_en" id="nuevo_inicia_en" class="form-control" placeholder="Inicia en">
                                <label for="nuevo_inicia_en">Inicia en</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="datetime-local" name="finaliza_en" id="nuevo_finaliza_en" class="form-control" placeholder="Finaliza en">
                                <label for="nuevo_finaliza_en">Finaliza en</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="hw-switch-container">
                                <div class="hw-switch-information">
                                  <span class="material-symbols-outlined">toggle_on</span>
                                  <div>
                                    <span class="hw-switch-title">Anuncio activo</span>
                                    <span class="hw-switch-description">Los anuncios inactivos no se muestran.</span>
                                  </div>
                                </div>
                                <div class="form-check form-switch hw-switch">
                                  <input type="hidden" name="activo" value="0">
                                  <input class="form-check-input" type="checkbox" role="switch" id="nuevo_activo" name="activo" value="1" checked>
                                  <label class="visually-hidden" for="nuevo_activo">Anuncio activo</label>
                                </div>
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
                            <h2 class="hw-modal-title" id="tituloEditar">Editar anuncio #<span id="editarTituloId">—</span></h2>
                            <p class="hw-modal-subtitle">Actualiza la información y guarda los cambios.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formEditar" method="POST"
                      data-url="{{ route('anuncios_globales.actualizar', ['id' => '__ID__']) }}"
                      data-buscar-url="{{ route('anuncios_globales.buscar', ['id' => '__ID__']) }}"
                      novalidate>
                    @csrf
                    @method('PUT')
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">campaign</span>
                            <div>
                              <h3>Contenido</h3>
                              <p>Título y mensaje del anuncio.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-12">
                              <div class="form-floating">
                                <input type="text" name="titulo" id="editar_titulo" class="form-control" placeholder="Título" maxlength="150" required>
                                <label for="editar_titulo">Título</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="mensaje" id="editar_mensaje" class="form-control" placeholder="Mensaje" required></textarea>
                                <label for="editar_mensaje">Mensaje</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">event_repeat</span>
                            <div>
                              <h3>Publicación</h3>
                              <p>Prioridad y periodo de vigencia.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-4">
                              <div class="form-floating">
                                <select name="prioridad" id="editar_prioridad" class="form-select" required>
                                  <option value="">Sin especificar</option>
                                  <option value="baja">Baja</option>
                                  <option value="normal" selected>Normal</option>
                                  <option value="alta">Alta</option>
                                  <option value="urgente">Urgente</option>
                                </select>
                                <label for="editar_prioridad">Prioridad</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="datetime-local" name="inicia_en" id="editar_inicia_en" class="form-control" placeholder="Inicia en">
                                <label for="editar_inicia_en">Inicia en</label>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-floating">
                                <input type="datetime-local" name="finaliza_en" id="editar_finaliza_en" class="form-control" placeholder="Finaliza en">
                                <label for="editar_finaliza_en">Finaliza en</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="hw-switch-container">
                                <div class="hw-switch-information">
                                  <span class="material-symbols-outlined">toggle_on</span>
                                  <div>
                                    <span class="hw-switch-title">Anuncio activo</span>
                                    <span class="hw-switch-description">Los anuncios inactivos no se muestran.</span>
                                  </div>
                                </div>
                                <div class="form-check form-switch hw-switch">
                                  <input type="hidden" name="activo" value="0">
                                  <input class="form-check-input" type="checkbox" role="switch" id="editar_activo" name="activo" value="1" checked>
                                  <label class="visually-hidden" for="editar_activo">Anuncio activo</label>
                                </div>
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
         data-buscar-url="{{ route('anuncios_globales.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de anuncio</h2>
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
                        <div class="hw-detail-item is-wide"><dt>Título</dt><dd data-ver="titulo">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Mensaje</dt><dd data-ver="mensaje">—</dd></div>
                        <div class="hw-detail-item"><dt>Prioridad</dt><dd data-ver="prioridad">—</dd></div>
                        <div class="hw-detail-item"><dt>Activo</dt><dd data-ver="activo" data-formato="bool">—</dd></div>
                        <div class="hw-detail-item"><dt>Inicia</dt><dd data-ver="inicia_en" data-formato="fechahora">—</dd></div>
                        <div class="hw-detail-item"><dt>Finaliza</dt><dd data-ver="finaliza_en" data-formato="fechahora">—</dd></div>
                        <div class="hw-detail-item"><dt>Creado por</dt><dd data-ver="creador.nombres+creador.apellidos">—</dd></div>
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
    VISTA: GESTIÓN DE ANUNCIOS GLOBALES
    ------------------------------------------------------------
    OBJETIVO:
    Permitir al administrador publicar y gestionar anuncios que
    estarán disponibles para todos los usuarios del sistema.

    CONTENIDO GENERAL:
    1. Encabezado:
       - Título "Anuncios globales".
       - Descripción breve de la sección.
       - Botón para registrar un nuevo anuncio (solo administrador).

    2. Tabla de anuncios:
       - Identificador.
       - Título del anuncio.
       - Contenido (mostrar una versión resumida).
       - Fecha de publicación.
       - Estado del anuncio, si está contemplado en el modelo.
       - Acciones disponibles.

    3. Filtros:
       - Búsqueda por título o contenido.
       - Filtrado por estado, si corresponde.
       - Filtrado por fecha de publicación.

    4. Modal de registro:
       - Campo para el título.
       - Área de texto para el contenido.
       - Estado o visibilidad, si el modelo lo contempla.
       - Botón para publicar o guardar.

    5. Modal de edición:
       - Permitir modificar el título y contenido.
       - Permitir actualizar el estado, si corresponde.
       - Cargar los datos del anuncio seleccionado.

    6. Modal de visualización:
       - Mostrar el contenido completo del anuncio.
       - Mostrar la fecha de publicación y su estado.

    REGLAS DE NEGOCIO:
    - Todos los roles autorizados pueden consultar los anuncios.
    - Solamente el administrador puede registrar o modificar anuncios.
    - No permitir contenido vacío.
    - La visibilidad de los anuncios debe respetar el estado definido
      en el modelo.
    - No implementar eliminación física si no está contemplada.

    INTEGRACIÓN:
    - Utilizar las rutas del módulo anuncios-globales.
    - Los datos deben proceder del controlador correspondiente.
    - Mantener la estructura visual del sistema y utilizar los
      componentes globales de notificaciones.
--}}
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/anuncios_globales/anuncios_globales.js') }}"></script>
@endpush
