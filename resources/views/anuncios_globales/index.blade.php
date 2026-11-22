@extends('layouts.app_layout')

@section('titulo', 'Anuncios')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/anuncios.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? $valor->format($formato) : null;
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];

        $total = $anuncios->count();
        $proximos = $anuncios->filter(fn ($a) => $a->estadoEvento() === 'proximo' && $a->activo)->count();
        $porConfirmar = $anuncios->filter(fn ($a) => $a->esDirigido() && !$a->estaConfirmado() && $a->activo)->count();

        // Texto único de la columna «Estado» (también alimenta su filtro).
        $estadoDe = function ($a) {
            if (!$a->activo) return ['Retirado', 'neutral'];
            if (!$a->esDirigido()) return ['General', 'info'];
            return $a->estaConfirmado() ? ['Confirmado', 'success'] : ['Por confirmar', 'warning'];
        };
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">campaign</span>
                </div>
                <div>
                    <h1 class="hw-title">Anuncios</h1>
                    <p class="hw-subtitle">
                        @if($esAdmin)
                            Comunicados internos: usted los publica, el personal los consulta y los confirma.
                        @else
                            Comunicados de la administración dirigidos al personal o a usted en particular.
                        @endif
                    </p>
                </div>
            </div>
            @if($esAdmin)
                <div class="hw-document-actions">
                    <a href="{{ route('anuncios_globales.crear') }}" class="btn btn-primary hw-create-button">
                        <span class="material-symbols-outlined">add_alert</span>
                        Nuevo anuncio
                    </a>
                </div>
            @endif
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">campaign</span>
                <div><strong>{{ $total }}</strong><span>{{ $total === 1 ? 'Anuncio' : 'Anuncios' }}</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">event_upcoming</span>
                <div><strong>{{ $proximos }}</strong><span>{{ $proximos === 1 ? 'Evento próximo' : 'Eventos próximos' }}</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">mark_email_unread</span>
                <div><strong>{{ $porConfirmar }}</strong><span>{{ $esAdmin ? 'Sin confirmar' : 'Por confirmar' }}</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ $esAdmin ? 'Anuncios publicados' : 'Anuncios para usted' }}</h2>
                    <p class="hw-surface-description">Ordenados por prioridad: primero los urgentes.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $total }} registros</span>
                </div>
            </div>

            @if($anuncios->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">campaign</span>
                    <h3>No hay anuncios</h3>
                    <p>{{ $esAdmin ? 'Publique el primero con «Nuevo anuncio».' : 'Cuando la administración publique uno, aparecerá aquí.' }}</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar anuncios…" aria-label="Buscar anuncios" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="2" aria-label="Filtrar por prioridad">
                        <option value="">Prioridad: todas</option>
                    </select>
                    <select class="form-select" data-hw-filtro-columna="4" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-anuncios" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="hw-column-id">ID</th>
                                <th scope="col">Anuncio</th>
                                <th scope="col">Prioridad</th>
                                <th scope="col">Evento</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($anuncios as $item)
                                @php [$textoEstado, $claseEstado] = $estadoDe($item); @endphp
                                <tr>
                                    <td class="hw-id">#{{ $item->id }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <a href="{{ route('anuncios_globales.ver', ['id' => $item->id]) }}" class="hw-fullname anuncio-enlace">{{ $item->titulo }}</a>
                                            <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit($item->mensaje, 70) }}</span>
                                            <span class="anuncio-etiquetas">
                                                @if($item->esDirigido())
                                                    <span class="hw-chip" title="Solo lo ven el destinatario y la administración">
                                                        Para: {{ $item->destinatario ? trim($item->destinatario->nombres . ' ' . $item->destinatario->apellidos) : 'usuario eliminado' }}
                                                    </span>
                                                @endif
                                                @if($item->adjuntos_count > 0)
                                                    <span class="anuncio-adjunto-marca" title="Archivos adjuntos">
                                                        <span class="material-symbols-outlined">attach_file</span>{{ $item->adjuntos_count }}
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $clasePrioridad[$item->prioridad] ?? 'neutral' }}"><span class="hw-status-dot"></span>{{ $item->etiquetaPrioridad() }}</span>
                                    </td>
                                    <td>
                                        @if($item->tieneEvento())
                                            {{ $fmt($item->fecha_evento) }}
                                            <span class="hw-muted small d-block">{{ $item->estadoEvento() === 'proximo' ? 'Próximo' : 'Realizado' }}</span>
                                        @else
                                            <span class="hw-muted">Informativo</span>
                                        @endif
                                    </td>
                                    <td><span class="hw-status hw-status-{{ $claseEstado }}"><span class="hw-status-dot"></span>{{ $textoEstado }}</span></td>
                                    <td>
                                        <div class="hw-actions">
                                            <a href="{{ route('anuncios_globales.ver', ['id' => $item->id]) }}" class="hw-action-button" title="Ver anuncio" aria-label="Ver anuncio">
                                                <span class="material-symbols-outlined">visibility</span>
                                            </a>
                                            @if($esAdmin)
                                                @if($item->estaConfirmado())
                                                    <span class="hw-action-button anuncio-accion-bloqueada" title="Confirmado: ya no puede editarse" aria-hidden="true">
                                                        <span class="material-symbols-outlined">lock</span>
                                                    </span>
                                                @else
                                                    <a href="{{ route('anuncios_globales.editar', ['id' => $item->id]) }}" class="hw-action-button" title="Editar" aria-label="Editar anuncio">
                                                        <span class="material-symbols-outlined">edit</span>
                                                    </a>
                                                @endif
                                                <form method="POST" action="{{ route('anuncios_globales.eliminar', ['id' => $item->id]) }}" class="d-inline"
                                                      data-confirmar="¿Eliminar el anuncio &quot;{{ $item->titulo }}&quot;? Se borrarán también sus adjuntos y no podrá recuperarlo.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="hw-action-button hw-action-delete" title="Eliminar" aria-label="Eliminar anuncio">
                                                        <span class="material-symbols-outlined">delete</span>
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
    <script src="{{ asset('js/anuncios_globales/anuncios_globales.js') }}"></script>
@endpush
