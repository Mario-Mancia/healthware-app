{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Notificaciones')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = false;
        $puedeEditar = false;
        $puedeEliminar = false;
        $lista = ($rolActual === "administrador" ? ($notificaciones ?? collect()) : collect($notificaciones ?? [])->where("id_usuario", auth()->id())) ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">notifications</span>
                </div>
                <div>
                    <h1 class="hw-title">Notificaciones</h1>
                    <p class="hw-subtitle">Avisos recibidos dentro del sistema.</p>
                </div>
            </div>
        </header>

        <div class="hw-stats">
          <div class="hw-stat">
            <span class="material-symbols-outlined">notifications</span>
            <div><strong>{{ $lista->count() }}</strong><span>Total</span></div>
          </div>
          <div class="hw-stat">
            <span class="material-symbols-outlined">mark_email_read</span>
            <div><strong>{{ $lista->whereNotNull("leido_en")->count() }}</strong><span>Leídas</span></div>
          </div>
          <div class="hw-stat">
            <span class="material-symbols-outlined">mark_email_unread</span>
            <div><strong>{{ $lista->whereNull("leido_en")->count() }}</strong><span>Pendientes</span></div>
          </div>
        </div>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Bandeja de notificaciones</h2>
                    <p class="hw-surface-description">Consulta el contenido y el estado de lectura.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">notifications</span>
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
                <select class="form-select" data-filtro-columna="2" aria-label="Filtrar por tipo">
                    <option value="">Tipo: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-notificaciones" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Notificación</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Destinatario</th>
                            <th scope="col">Recibida</th>
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
                                <div class="hw-avatar"><span class="material-symbols-outlined">mail</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ $item->titulo }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ($item->mensaje ?? ""), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td><span class="hw-chip" data-filtro-valor>{{ $item->tipo ?? "N/A" }}</span></td>
                            <td>{{ ($item->usuario?->nombres ?? '') . ' ' . ($item->usuario?->apellidos ?? '') ?? "—" }}</td>
                            <td>{{ $fmtFecha($item->created_at, 'd/m/Y H:i') }}</td>
                            <td><span class="hw-status hw-status-{{ $item->leido_en ? 'success' : 'warning' }}" data-filtro-valor><span class="hw-status-dot"></span>{{ $item->leido_en ? 'Leída' : 'Pendiente' }}</span></td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar notificación" aria-label="Consultar notificación">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @if(! $item->leido_en && (int) $item->id_usuario === (int) auth()->id())
                                        <form method="POST" action="{{ route('notificaciones.leer', ['id' => $item->id]) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="hw-action-button"
                                                    title="Marcar como leída" aria-label="Marcar como leída">
                                                <span class="material-symbols-outlined">mark_email_read</span>
                                            </button>
                                        </form>
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
    <div class="modal fade hw-modal" id="modalVer" tabindex="-1" aria-labelledby="tituloVer" aria-hidden="true"
         data-buscar-url="{{ route('notificaciones.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de notificación</h2>
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
                        <div class="hw-detail-item"><dt>Título</dt><dd data-ver="titulo">—</dd></div>
                        <div class="hw-detail-item"><dt>Tipo</dt><dd data-ver="tipo">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Mensaje</dt><dd data-ver="mensaje">—</dd></div>
                        <div class="hw-detail-item"><dt>Destinatario</dt><dd data-ver="usuario.nombres+usuario.apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>Recibida</dt><dd data-ver="created_at" data-formato="fechahora">—</dd></div>
                        <div class="hw-detail-item"><dt>Leída</dt><dd data-ver="leido_en" data-formato="fechahora">—</dd></div>
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
    VISTA: CENTRO DE NOTIFICACIONES
    ------------------------------------------------------------
    OBJETIVO:
    Permitir que cada usuario consulte las notificaciones que ha
    recibido dentro del sistema y gestione su estado de lectura.

    CONTENIDO GENERAL:
    1. Encabezado:
       - Título "Notificaciones".
       - Descripción de la sección.
       - Indicador de notificaciones pendientes de lectura.

    2. Resumen:
       - Cantidad total de notificaciones.
       - Cantidad de notificaciones leídas.
       - Cantidad de notificaciones pendientes.
       - Los indicadores deben calcularse en el backend.

    3. Filtros:
       - Todas las notificaciones.
       - Notificaciones leídas.
       - Notificaciones no leídas.
       - Búsqueda por título o contenido, si esos campos existen.
       - Filtro por fecha de recepción.

    4. Listado de notificaciones:
       - Título.
       - Resumen del contenido.
       - Fecha de recepción.
       - Indicador de lectura.
       - Acción para consultar el contenido completo.
       - Acción para marcar como leída, si está implementada.

    5. Modal de visualización:
       - Título completo.
       - Contenido completo.
       - Fecha de recepción.
       - Estado de lectura.
       - Acción para marcar como leída, si corresponde.

    6. Estados visuales:
       - Diferenciar notificaciones leídas y no leídas.
       - Destacar las notificaciones pendientes sin utilizar
         elementos visuales excesivamente llamativos.
       - Mostrar un mensaje cuando no existan notificaciones.

    REGLAS DE NEGOCIO:
    - Cada usuario solamente puede consultar sus propias
      notificaciones.
    - No permitir que un usuario consulte o modifique notificaciones
      pertenecientes a otra cuenta.
    - Marcar como leída una notificación no debe eliminarla.
    - El estado de lectura debe persistirse en la base de datos
      cuando esta funcionalidad esté implementada.
    - Las notificaciones deben conservar su historial.
    - Si se permite generar notificaciones desde el panel, restringir
      esa operación a los roles autorizados.

    INTEGRACIÓN:
    - Utilizar NotificacionController.
    - Aprovechar las rutas existentes:
        - index
        - todos
        - insertar
        - buscar
        - actualizar
    - Las operaciones de lectura y actualización deben validar
      la propiedad de la notificación.
    - El indicador de notificaciones pendientes puede integrarse
      posteriormente con el encabezado global del sistema.
--}}
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/notificacion/notificaciones.js') }}"></script>
@endpush
