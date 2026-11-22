@extends('layouts.app_layout')

@section('titulo', 'Anuncio')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/anuncios.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? $valor->format($formato) : '—';
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];
        $nombreDe = fn ($u) => $u ? trim($u->nombres . ' ' . $u->apellidos) : 'Usuario eliminado';
        $puedeConfirmar = $esDestinatario && $anuncio->activo && !$anuncio->estaConfirmado();
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">campaign</span>
                </div>
                <div>
                    <h1 class="hw-title">{{ $anuncio->titulo }}</h1>
                    <p class="hw-subtitle">
                        Publicado el {{ $fmt($anuncio->created_at) }}
                        @if($anuncio->creador) por {{ $nombreDe($anuncio->creador) }} @endif
                    </p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('anuncios_globales') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver a anuncios
                </a>
                @if($esAdmin)
                    @unless($anuncio->estaConfirmado())
                        <a href="{{ route('anuncios_globales.editar', ['id' => $anuncio->id]) }}" class="btn btn-tonal">
                            <span class="material-symbols-outlined">edit</span>
                            Editar
                        </a>
                    @endunless
                    <form method="POST" action="{{ route('anuncios_globales.eliminar', ['id' => $anuncio->id]) }}" class="d-inline"
                          data-confirmar="¿Eliminar el anuncio &quot;{{ $anuncio->titulo }}&quot;? Se borrarán también sus adjuntos y no podrá recuperarlo.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <span class="material-symbols-outlined">delete</span>
                            Eliminar
                        </button>
                    </form>
                @endif
            </div>
        </header>

        @if($puedeConfirmar)
            <section class="anuncio-confirmacion is-pendiente" aria-labelledby="tituloConfirmar">
                <span class="material-symbols-outlined">mark_email_unread</span>
                <div>
                    <h2 id="tituloConfirmar">Este anuncio requiere su confirmación</h2>
                    <p>Al confirmar queda registrado que lo recibió y lo leyó (usuario, fecha, hora y dirección IP) y la administración recibe un aviso.</p>
                </div>
                <form method="POST" action="{{ route('anuncios_globales.confirmar', ['id' => $anuncio->id]) }}"
                      data-confirmar="¿Confirmar que recibió y leyó este anuncio? Quedará registrado con su usuario, la fecha y la hora.">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">task_alt</span>
                        Confirmar recepción
                    </button>
                </form>
            </section>
        @elseif($anuncio->esDirigido() && $anuncio->estaConfirmado())
            <section class="anuncio-confirmacion is-confirmado">
                <span class="material-symbols-outlined">task_alt</span>
                <div>
                    <h2>Recepción confirmada</h2>
                    <p>
                        {{ $esDestinatario ? 'Usted' : $nombreDe($anuncio->destinatario) }}
                        confirmó el {{ $fmt($anuncio->confirmado_en) }}@if($esAdmin && $anuncio->confirmado_ip) · IP {{ $anuncio->confirmado_ip }}@endif.
                    </p>
                </div>
            </section>
        @endif

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Detalle del anuncio</h2>
                    <p class="hw-surface-description">
                        <span class="hw-status hw-status-{{ $clasePrioridad[$anuncio->prioridad] ?? 'neutral' }}"><span class="hw-status-dot"></span>Prioridad {{ \Illuminate\Support\Str::lower($anuncio->etiquetaPrioridad()) }}</span>
                        @if(!$anuncio->activo)
                            <span class="hw-status hw-status-neutral"><span class="hw-status-dot"></span>Retirado (los usuarios no lo ven)</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="anuncio-cuerpo">
                <p class="anuncio-texto">{{ $anuncio->mensaje }}</p>

                <dl class="hw-detail-grid">
                    <div class="hw-detail-item">
                        <dt>Evento</dt>
                        <dd>
                            @if($anuncio->tieneEvento())
                                {{ $fmt($anuncio->fecha_evento) }}
                                <span class="hw-muted small d-block">
                                    {{ $anuncio->estadoEvento() === 'proximo' ? 'Próximo · recordatorio 15 minutos antes' : 'Ya realizado' }}
                                </span>
                            @else
                                Informativo, sin fecha de evento
                            @endif
                        </dd>
                    </div>
                    <div class="hw-detail-item">
                        <dt>Lugar</dt>
                        <dd>{{ $anuncio->lugar ?: '—' }}</dd>
                    </div>
                    <div class="hw-detail-item">
                        <dt>Dirigido a</dt>
                        <dd>{{ $anuncio->esDirigido() ? $nombreDe($anuncio->destinatario) : 'Todo el personal' }}</dd>
                    </div>
                    <div class="hw-detail-item">
                        <dt>Confirmación</dt>
                        <dd>
                            @if(!$anuncio->esDirigido())
                                No requiere
                            @elseif($anuncio->estaConfirmado())
                                Confirmado el {{ $fmt($anuncio->confirmado_en) }}
                            @else
                                Pendiente
                            @endif
                        </dd>
                    </div>
                    @if($anuncio->datos_extra)
                        <div class="hw-detail-item is-wide">
                            <dt>Datos adicionales</dt>
                            <dd class="anuncio-texto">{{ $anuncio->datos_extra }}</dd>
                        </div>
                    @endif
                </dl>

                <h3 class="anuncio-subtitulo">Archivos adjuntos</h3>
                @if($anuncio->adjuntos->isEmpty())
                    <p class="hw-muted mb-0">Este anuncio no tiene archivos adjuntos.</p>
                @else
                    <ul class="anuncio-adjuntos">
                        @foreach($anuncio->adjuntos as $adjunto)
                            <li>
                                <span class="material-symbols-outlined">{{ $adjunto->icono() }}</span>
                                <div>
                                    <strong>{{ $adjunto->nombre_original }}</strong>
                                    <span class="hw-muted small">{{ $adjunto->tamanhoLegible() }}</span>
                                </div>
                                <a href="{{ route('anuncios_globales.adjunto', ['id' => $adjunto->id]) }}" class="btn btn-tonal btn-sm">
                                    <span class="material-symbols-outlined">download</span>
                                    Descargar
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/anuncios_globales/anuncios_globales.js') }}"></script>
@endpush
