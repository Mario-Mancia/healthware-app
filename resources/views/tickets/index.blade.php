@extends('layouts.app_layout')

@section('titulo', 'Soporte y reportes')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/tickets.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor) => $valor ? $valor->format('d/m/Y H:i') : '—';
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];
        $claseEstado = ['abierto' => 'info', 'en_proceso' => 'warning', 'completado' => 'success', 'descartado' => 'neutral', 'cancelado' => 'neutral'];
        $nombreDe = fn ($u) => $u ? trim($u->nombres . ' ' . $u->apellidos) : '—';

        $total = $tickets->count();
        $abiertos = $tickets->where('estado', 'abierto')->count();
        $enProceso = $tickets->where('estado', 'en_proceso')->count();
        $cerrados = $total - $abiertos - $enProceso;
        $esBandeja = $esAdmin && $vista === 'bandeja';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">support_agent</span>
                </div>
                <div>
                    <h1 class="hw-title">Soporte y reportes</h1>
                    <p class="hw-subtitle">
                        @if($esBandeja)
                            Reportes por tomar y los que usted atiende. Un reporte tomado deja de verlo cualquier otro administrador.
                        @else
                            Reporte problemas de su trabajo y siga su estado: cada cambio le llega como notificación.
                        @endif
                    </p>
                </div>
            </div>
            <div class="hw-document-actions">
                @if($esAdmin)
                    <a href="{{ route('tickets', ['vista' => 'bandeja']) }}" class="btn {{ $esBandeja ? 'btn-primary' : 'btn-tonal' }}">
                        <span class="material-symbols-outlined">inbox</span>
                        Bandeja de atención
                    </a>
                    <a href="{{ route('tickets', ['vista' => 'mis']) }}" class="btn {{ !$esBandeja ? 'btn-primary' : 'btn-tonal' }}">
                        <span class="material-symbols-outlined">person</span>
                        Mis reportes
                    </a>
                @endif
                <a href="{{ route('tickets.crear') }}" class="btn btn-primary hw-create-button">
                    <span class="material-symbols-outlined">add_circle</span>
                    Nuevo reporte
                </a>
            </div>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">mark_email_unread</span>
                <div><strong>{{ $abiertos }}</strong><span>{{ $esBandeja ? 'Por tomar' : 'Abiertos' }}</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $enProceso }}</strong><span>En proceso</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">task_alt</span>
                <div><strong>{{ $cerrados }}</strong><span>Finalizados</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ $esBandeja ? 'Bandeja de atención' : 'Mis reportes' }}</h2>
                    <p class="hw-surface-description">Primero los activos y, dentro de ellos, los más urgentes. Se muestran los {{ \App\Models\Ticket::LIMITE_LISTADO }} más recientes.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $total }} registros</span>
                </div>
            </div>

            @if($tickets->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">support_agent</span>
                    <h3>{{ $esBandeja ? 'No hay reportes por atender' : 'Aún no ha abierto reportes' }}</h3>
                    <p>{{ $esBandeja ? 'Cuando alguien abra un reporte aparecerá aquí.' : 'Si algo no funciona, use «Nuevo reporte».' }}</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar reportes…" aria-label="Buscar reportes" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="2" aria-label="Filtrar por prioridad">
                        <option value="">Prioridad: todas</option>
                    </select>
                    <select class="form-select" data-hw-filtro-columna="3" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-tickets" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="hw-column-id">Código</th>
                                <th scope="col">Reporte</th>
                                <th scope="col">Prioridad</th>
                                <th scope="col">Estado</th>
                                <th scope="col">{{ $esBandeja ? 'Emisor' : 'Atiende' }}</th>
                                <th scope="col">Abierto</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $item)
                                <tr>
                                    <td class="hw-id">{{ $item->codigo() }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <a href="{{ route('tickets.ver', ['id' => $item->id]) }}" class="hw-fullname ticket-enlace">{{ $item->titulo }}</a>
                                            <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit($item->descripcion, 70) }}</span>
                                            <span class="ticket-etiquetas">
                                                <span class="hw-chip">{{ \Illuminate\Support\Str::before($item->etiquetaTipo(), ' (') }}</span>
                                                @if($item->adjuntos_count > 0)
                                                    <span class="ticket-adjunto-marca" title="Archivos adjuntos">
                                                        <span class="material-symbols-outlined">attach_file</span>{{ $item->adjuntos_count }}
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="hw-status hw-status-{{ $clasePrioridad[$item->prioridad] ?? 'neutral' }}"><span class="hw-status-dot"></span>{{ $item->etiquetaPrioridad() }}</span></td>
                                    <td><span class="hw-status hw-status-{{ $claseEstado[$item->estado] ?? 'neutral' }}"><span class="hw-status-dot"></span>{{ $item->etiquetaEstado() }}</span></td>
                                    <td>
                                        @if($esBandeja)
                                            {{ $nombreDe($item->usuario) }}
                                        @elseif($item->administrador)
                                            {{ $nombreDe($item->administrador) }}
                                        @else
                                            <span class="hw-muted">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>{{ $fmt($item->created_at) }}</td>
                                    <td>
                                        <div class="hw-actions">
                                            <a href="{{ route('tickets.ver', ['id' => $item->id]) }}" class="hw-action-button" title="Ver reporte" aria-label="Ver reporte">
                                                <span class="material-symbols-outlined">visibility</span>
                                            </a>
                                            @if($esBandeja && $item->estaDisponible() && (int) $item->id_usuario !== (int) $idUsuario)
                                                <form method="POST" action="{{ route('tickets.tomar', ['id' => $item->id]) }}" class="d-inline"
                                                      data-confirmar="¿Tomar el reporte {{ $item->codigo() }}? Será el responsable y ningún otro administrador podrá verlo.">
                                                    @csrf
                                                    <button type="submit" class="hw-action-button" title="Tomar reporte" aria-label="Tomar reporte">
                                                        <span class="material-symbols-outlined">assignment_ind</span>
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
@endsection

@push('scripts')
    <script src="{{ asset('js/ticket/tickets.js') }}"></script>
@endpush
