@extends('layouts.app_layout')

@section('titulo', 'Reporte ' . $ticket->codigo())

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/tickets.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor) => $valor ? $valor->format('d/m/Y H:i') : '—';
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];
        $claseEstado = ['abierto' => 'info', 'en_proceso' => 'warning', 'completado' => 'success', 'descartado' => 'neutral', 'cancelado' => 'neutral'];
        $nombreDe = fn ($u) => $u ? trim($u->nombres . ' ' . $u->apellidos) : 'Usuario eliminado';

        $activo = $ticket->estaActivo();
        $puedeTomar = $esAdmin && !$esEmisor && $ticket->estaDisponible();
        $puedeGestionar = $esResponsable && $ticket->estado === 'en_proceso';
        $puedeCancelar = $esEmisor && $activo;
        $puedeNotas = $activo && ($esEmisor || $esResponsable);

        $etiquetaResolucion = match ($ticket->estado) {
            'completado' => 'Resolución',
            'descartado' => 'Motivo del descarte',
            'cancelado' => 'Motivo de la cancelación',
            default => 'Resolución',
        };

        $iconoEvento = ['creado' => 'add_circle', 'tomado' => 'assignment_ind', 'liberado' => 'undo', 'completado' => 'task_alt', 'descartado' => 'block', 'cancelado' => 'cancel', 'nota' => 'chat'];
        $textoEvento = ['creado' => 'abrió el reporte', 'tomado' => 'tomó el reporte', 'liberado' => 'liberó el reporte (volvió a la cola)', 'completado' => 'completó el reporte', 'descartado' => 'descartó el reporte', 'cancelado' => 'canceló el reporte', 'nota' => 'agregó una nota'];

        // Errores de validación del texto, solo en el formulario que se envió.
        $errorDe = fn ($formulario) => old('_modal') === $formulario ? $errors->first('texto') : null;
        $viejo = fn ($formulario) => old('_modal') === $formulario ? old('texto') : '';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">support_agent</span>
                </div>
                <div>
                    <h1 class="hw-title">{{ $ticket->titulo }}</h1>
                    <p class="hw-subtitle">{{ $ticket->codigo() }} · abierto el {{ $fmt($ticket->created_at) }} por {{ $esEmisor ? 'usted' : $nombreDe($ticket->usuario) }}</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('tickets') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver a reportes
                </a>
                @if($puedeTomar)
                    <form method="POST" action="{{ route('tickets.tomar', ['id' => $ticket->id]) }}" class="d-inline"
                          data-confirmar="¿Tomar el reporte {{ $ticket->codigo() }}? Será el responsable y ningún otro administrador podrá verlo.">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">assignment_ind</span>
                            Tomar reporte
                        </button>
                    </form>
                @endif
                @if($puedeGestionar)
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCompletarTicket">
                        <span class="material-symbols-outlined">task_alt</span>
                        Completar
                    </button>
                    <button type="button" class="btn btn-tonal" data-bs-toggle="modal" data-bs-target="#modalDescartarTicket">
                        <span class="material-symbols-outlined">block</span>
                        Descartar
                    </button>
                    <form method="POST" action="{{ route('tickets.liberar', ['id' => $ticket->id]) }}" class="d-inline"
                          data-confirmar="¿Liberar el reporte {{ $ticket->codigo() }}? Volverá a la cola y otro administrador podrá tomarlo.">
                        @csrf
                        <button type="submit" class="btn btn-tonal">
                            <span class="material-symbols-outlined">undo</span>
                            Liberar
                        </button>
                    </form>
                @endif
                @if($puedeCancelar)
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarTicket">
                        <span class="material-symbols-outlined">cancel</span>
                        Cancelar reporte
                    </button>
                @endif
            </div>
        </header>

        {{-- ESTADO --}}
        <section class="ticket-estado is-{{ $ticket->estado }}" aria-label="Estado del reporte">
            <span class="material-symbols-outlined">
                {{ ['abierto' => 'mark_email_unread', 'en_proceso' => 'pending_actions', 'completado' => 'task_alt', 'descartado' => 'block', 'cancelado' => 'cancel'][$ticket->estado] ?? 'info' }}
            </span>
            <div>
                <h2>{{ $ticket->etiquetaEstado() }}</h2>
                <p>
                    @switch($ticket->estado)
                        @case('abierto')
                            @if($esAdmin && !$esEmisor)
                                Está en la cola. Si lo toma, será el único administrador que lo vea y se notificará al emisor.
                            @elseif($esAdmin && $esEmisor)
                                Está en la cola. No puede tomar su propio reporte: debe atenderlo otro administrador.
                            @else
                                Esperando a que un administrador lo tome. Le avisaremos por notificación.
                            @endif
                            @break
                        @case('en_proceso')
                            {{ $esResponsable ? 'Usted lo atiende' : ($nombreDe($ticket->administrador) . ' lo atiende') }} desde el {{ $fmt($ticket->tomado_en) }}.
                            @break
                        @case('completado')
                            Completado el {{ $fmt($ticket->finalizado_en) }}.
                            @break
                        @case('descartado')
                            Descartado el {{ $fmt($ticket->finalizado_en) }}.
                            @break
                        @case('cancelado')
                            Cancelado el {{ $fmt($ticket->finalizado_en) }}.
                            @break
                    @endswitch
                </p>
            </div>
        </section>

        {{-- DETALLE --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Detalle del reporte</h2>
                    <p class="hw-surface-description">
                        <span class="hw-status hw-status-{{ $clasePrioridad[$ticket->prioridad] ?? 'neutral' }}"><span class="hw-status-dot"></span>Prioridad {{ \Illuminate\Support\Str::lower($ticket->etiquetaPrioridad()) }}</span>
                        <span class="hw-status hw-status-{{ $claseEstado[$ticket->estado] ?? 'neutral' }}"><span class="hw-status-dot"></span>{{ $ticket->etiquetaEstado() }}</span>
                    </p>
                </div>
            </div>

            <div class="ticket-cuerpo">
                <p class="ticket-texto">{{ $ticket->descripcion }}</p>

                <dl class="hw-detail-grid">
                    <div class="hw-detail-item">
                        <dt>Tipo</dt>
                        <dd>{{ $ticket->etiquetaTipo() }}</dd>
                    </div>
                    <div class="hw-detail-item">
                        <dt>Módulo o pantalla</dt>
                        <dd>{{ $ticket->modulo_afectado ?: '—' }}</dd>
                    </div>
                    <div class="hw-detail-item">
                        <dt>Emisor</dt>
                        <dd>{{ $esEmisor ? 'Usted' : $nombreDe($ticket->usuario) }}</dd>
                    </div>
                    <div class="hw-detail-item">
                        <dt>Atiende</dt>
                        <dd>{{ $ticket->administrador ? ($esResponsable ? 'Usted' : $nombreDe($ticket->administrador)) : 'Sin asignar' }}</dd>
                    </div>
                    @if($ticket->resolucion)
                        <div class="hw-detail-item is-wide">
                            <dt>{{ $etiquetaResolucion }}</dt>
                            <dd class="ticket-texto">{{ $ticket->resolucion }}</dd>
                        </div>
                    @endif
                </dl>

                <h3 class="ticket-subtitulo">Archivos adjuntos</h3>
                @if($ticket->adjuntos->isEmpty())
                    <p class="hw-muted mb-0">Este reporte no tiene archivos adjuntos.</p>
                @else
                    <ul class="ticket-adjuntos">
                        @foreach($ticket->adjuntos as $adjunto)
                            <li>
                                <span class="material-symbols-outlined">{{ $adjunto->icono() }}</span>
                                <div>
                                    <strong>{{ $adjunto->nombre_original }}</strong>
                                    <span class="hw-muted small">{{ $adjunto->tamanhoLegible() }}</span>
                                </div>
                                @if($adjunto->existeArchivo())
                                    <a href="{{ route('tickets.adjunto', ['id' => $adjunto->id]) }}" class="btn btn-tonal btn-sm">
                                        <span class="material-symbols-outlined">download</span>
                                        Descargar
                                    </a>
                                @else
                                    <span class="hw-status hw-status-neutral"><span class="hw-status-dot"></span>Archivo no disponible</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- SEGUIMIENTO --}}
        <section class="hw-surface" id="seguimiento">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Seguimiento</h2>
                    <p class="hw-surface-description">Cada cambio de estado y cada nota quedan registrados.</p>
                </div>
            </div>

            <div class="ticket-cuerpo">
                <ol class="ticket-linea">
                    @foreach($ticket->seguimientos as $paso)
                        <li class="ticket-paso is-{{ $paso->evento }}">
                            <span class="material-symbols-outlined">{{ $iconoEvento[$paso->evento] ?? 'info' }}</span>
                            <div>
                                <p class="ticket-paso-titulo">
                                    <strong>{{ (int) $paso->id_usuario === (int) auth()->id() ? 'Usted' : $nombreDe($paso->usuario) }}</strong>
                                    {{ $textoEvento[$paso->evento] ?? $paso->evento }}
                                </p>
                                @if($paso->comentario)
                                    <p class="ticket-texto ticket-paso-texto">{{ $paso->comentario }}</p>
                                @endif
                                <span class="hw-muted small">{{ $fmt($paso->created_at) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ol>

                @if($puedeNotas)
                    <form method="POST" action="{{ route('tickets.comentar', ['id' => $ticket->id]) }}" novalidate class="ticket-nota">
                        @csrf
                        <input type="hidden" name="_modal" value="nota">
                        <div class="form-floating">
                            <textarea name="texto" id="ticket_nota" class="form-control ticket-textarea-sm @if($errorDe('nota')) is-invalid @endif" placeholder="Nota" maxlength="1000" required>{{ $viejo('nota') }}</textarea>
                            <label for="ticket_nota">Agregar una nota</label>
                        </div>
                        @if($errorDe('nota'))<div class="invalid-feedback d-block">{{ $errorDe('nota') }}</div>@endif
                        <div class="hw-field-hint">
                            {{ $esEmisor ? 'Aporte más datos al administrador que lo atiende.' : 'La nota le llega al emisor como notificación.' }}
                        </div>
                        <div class="ticket-nota-pie">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <span class="material-symbols-outlined">send</span>
                                Enviar nota
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>

    {{-- MODALES DE ACCIÓN --}}
    @php
        $modales = [];
        if ($puedeGestionar) {
            $modales[] = ['id' => 'modalCompletarTicket', 'ruta' => 'tickets.completar', 'icono' => 'task_alt', 'titulo' => 'Completar reporte',
                'ayuda' => 'Explique cómo se resolvió: el emisor recibe este texto en su notificación.', 'etiqueta' => 'Resolución', 'requerido' => true,
                'boton' => 'Completar reporte', 'clase' => 'btn-primary'];
            $modales[] = ['id' => 'modalDescartarTicket', 'ruta' => 'tickets.descartar', 'icono' => 'block', 'titulo' => 'Descartar reporte',
                'ayuda' => 'Indique por qué no procede (duplicado, fuera de alcance…). El emisor recibe este texto.', 'etiqueta' => 'Motivo', 'requerido' => true,
                'boton' => 'Descartar reporte', 'clase' => 'btn-danger'];
        }
        if ($puedeCancelar) {
            $modales[] = ['id' => 'modalCancelarTicket', 'ruta' => 'tickets.cancelar', 'icono' => 'cancel', 'titulo' => 'Cancelar reporte',
                'ayuda' => 'El reporte se cierra y, si ya lo atendía un administrador, se le avisa.', 'etiqueta' => 'Motivo (opcional)', 'requerido' => false,
                'boton' => 'Cancelar reporte', 'clase' => 'btn-danger'];
        }
    @endphp

    @foreach($modales as $m)
        <div class="modal fade hw-modal" id="{{ $m['id'] }}" tabindex="-1" aria-labelledby="t{{ $m['id'] }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="hw-modal-header">
                        <div class="hw-modal-heading">
                            <div class="hw-modal-icon"><span class="material-symbols-outlined">{{ $m['icono'] }}</span></div>
                            <div>
                                <h2 class="hw-modal-title" id="t{{ $m['id'] }}">{{ $m['titulo'] }}</h2>
                                <p class="hw-modal-subtitle">{{ $ticket->codigo() }} · {{ \Illuminate\Support\Str::limit($ticket->titulo, 50) }}</p>
                            </div>
                        </div>
                        <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route($m['ruta'], ['id' => $ticket->id]) }}" novalidate>
                        @csrf
                        <div class="modal-body hw-modal-body">
                            <input type="hidden" name="_modal" value="{{ $m['id'] }}">
                            <div class="form-floating">
                                <textarea name="texto" id="texto_{{ $m['id'] }}" class="form-control ticket-textarea-sm @if($errorDe($m['id'])) is-invalid @endif" placeholder="{{ $m['etiqueta'] }}" maxlength="1000" @required($m['requerido'])>{{ $viejo($m['id']) }}</textarea>
                                <label for="texto_{{ $m['id'] }}">{{ $m['etiqueta'] }}</label>
                            </div>
                            @if($errorDe($m['id']))<div class="invalid-feedback d-block">{{ $errorDe($m['id']) }}</div>@endif
                            <div class="hw-field-hint">{{ $m['ayuda'] }}</div>
                        </div>
                        <div class="hw-modal-footer">
                            <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Volver</button>
                            <button type="submit" class="btn {{ $m['clase'] }}">
                                <span class="material-symbols-outlined">{{ $m['icono'] }}</span>
                                {{ $m['boton'] }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script src="{{ asset('js/ticket/tickets.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
