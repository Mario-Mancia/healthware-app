@extends('layouts.app_layout')

@section('titulo', 'Notificaciones')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('css/notificaciones.css') }}">
@endpush

@section('contenido')

    @php
        $lista = $notificaciones ?? collect();
        $total = $lista->count();
        $leidas = $lista->whereNotNull('leido_en')->count();
        $pendientes = $total - $leidas;
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $nombreTipo = fn ($tipo) => ucfirst(str_replace('_', ' ', (string) ($tipo ?: 'general')));
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
                    <p class="hw-subtitle">Avisos personales del sistema. Se eliminan solos a los {{ $diasRetencion }} días, leídos o no.</p>
                </div>
            </div>

            <div class="hw-document-actions">
                <form method="POST" action="{{ route('notificaciones.leer_todas') }}" class="d-inline">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-tonal" data-leer-todas @disabled($pendientes === 0)>
                        <span class="material-symbols-outlined">done_all</span>
                        Marcar todas como leídas
                    </button>
                </form>

                @if($total > 0)
                    <form method="POST" action="{{ route('notificaciones.vaciar') }}" class="d-inline"
                          data-confirmar="¿Purgar todas sus notificaciones? Se eliminarán {{ $total }} {{ $total === 1 ? 'notificación' : 'notificaciones' }} y no podrá recuperarlas.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <span class="material-symbols-outlined">delete_sweep</span>
                            Purgar todas
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">notifications</span>
                <div><strong data-stat="total">{{ $total }}</strong><span>Total</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">mark_email_read</span>
                <div><strong data-stat="leidas">{{ $leidas }}</strong><span>Leídas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">mark_email_unread</span>
                <div><strong data-stat="pendientes">{{ $pendientes }}</strong><span>Pendientes</span></div>
            </div>
        </div>

        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Bandeja de notificaciones</h2>
                    <p class="hw-surface-description">Consulte, marque como leídas o elimine sus notificaciones.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $total }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">notifications_off</span>
                    <h3>No tiene notificaciones</h3>
                    <p>Los avisos del sistema aparecerán aquí.</p>
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
                            <th scope="col">Recibida</th>
                            <th scope="col">Se elimina</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr class="{{ $item->leido_en ? '' : 'hw-row-unread' }}" data-notificacion="{{ $item->id }}">
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">mail</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname">{{ $item->titulo }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ($item->mensaje ?? ''), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td><span class="hw-chip" data-filtro-valor>{{ $nombreTipo($item->tipo) }}</span></td>
                            <td>{{ $fmtFecha($item->created_at, 'd/m/Y H:i') }}</td>
                            <td class="hw-notif-vence">{{ $fmtFecha($item->fechaDePurga()) }}</td>
                            <td data-celda-estado><span class="hw-status hw-status-{{ $item->leido_en ? 'success' : 'warning' }}" data-filtro-valor><span class="hw-status-dot"></span>{{ $item->leido_en ? 'Leída' : 'Pendiente' }}</span></td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar notificación" aria-label="Consultar notificación">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    @if($item->enlace)
                                        <a href="{{ route('notificaciones.abrir', ['id' => $item->id]) }}" class="hw-action-button"
                                           title="Ir a lo relacionado" aria-label="Ir a lo relacionado">
                                            <span class="material-symbols-outlined">open_in_new</span>
                                        </a>
                                    @endif
                                    @unless($item->leido_en)
                                        <form method="POST" action="{{ route('notificaciones.leer', ['id' => $item->id]) }}" class="d-inline" data-form-leer>
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="hw-action-button"
                                                    title="Marcar como leída" aria-label="Marcar como leída">
                                                <span class="material-symbols-outlined">mark_email_read</span>
                                            </button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('notificaciones.eliminar', ['id' => $item->id]) }}" class="d-inline"
                                          data-confirmar="¿Eliminar esta notificación?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="hw-action-button hw-action-delete"
                                                title="Eliminar notificación" aria-label="Eliminar notificación">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
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

    {{-- MODAL: CONSULTA (al abrirla, la notificación se marca como leída) --}}
    <div class="modal fade hw-modal" id="modalVer" tabindex="-1" aria-labelledby="tituloVer" aria-hidden="true"
         data-buscar-url="{{ route('notificaciones.buscar', ['id' => '__ID__']) }}"
         data-leer-url="{{ route('notificaciones.leer', ['id' => '__ID__']) }}"
         data-abrir-url="{{ route('notificaciones.abrir', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de notificación</h2>
                            <p class="hw-modal-subtitle">Información completa del aviso.</p>
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
                        <div class="hw-detail-item"><dt>Recibida</dt><dd data-ver="created_at" data-formato="fechahora">—</dd></div>
                        <div class="hw-detail-item"><dt>Leída</dt><dd data-ver="leido_en" data-formato="fechahora">—</dd></div>
                    </dl>
                </div>

                <div class="hw-modal-footer">
                    <a href="#" class="btn btn-primary d-none" data-ver-abrir>
                        <span class="material-symbols-outlined">open_in_new</span>
                        Ir a lo relacionado
                    </a>
                    <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/notificacion/notificaciones.js') }}"></script>
@endpush
