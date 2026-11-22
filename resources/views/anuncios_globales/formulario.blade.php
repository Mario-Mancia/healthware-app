@extends('layouts.app_layout')

@php
    $editando = $anuncio !== null;
@endphp

@section('titulo', $editando ? 'Editar anuncio' : 'Nuevo anuncio')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/anuncios.css') }}">
@endpush

@section('contenido')
    @php
        $valor = fn ($campo, $porDefecto = null) => old($campo, $porDefecto);
        $tieneEvento = filter_var(old('tiene_evento', $editando ? $anuncio->tieneEvento() : false), FILTER_VALIDATE_BOOLEAN);
        $fechaEvento = old('fecha_evento', $editando ? $anuncio->fecha_evento?->format('Y-m-d\TH:i') : null);
        $activo = filter_var(old('activo', $editando ? $anuncio->activo : true), FILTER_VALIDATE_BOOLEAN);
        $minimo = $editando ? null : now()->format('Y-m-d\TH:i');
        $maximo = now()->addYear()->format('Y-m-d\TH:i');
        $adjuntosActuales = $editando ? $anuncio->adjuntos : collect();
        $quitar = collect((array) old('quitar_adjuntos', []))->map(fn ($id) => (int) $id);
        $maxArchivos = \App\Models\AnuncioAdjunto::MAX_ARCHIVOS;
        $maxMb = \App\Models\AnuncioAdjunto::MAX_KB / 1024;
        $nombreDe = fn ($u) => $u ? trim($u->nombres . ' ' . $u->apellidos) : 'Usuario eliminado';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">{{ $editando ? 'edit_note' : 'add_alert' }}</span>
                </div>
                <div>
                    <h1 class="hw-title">{{ $editando ? 'Editar anuncio' : 'Nuevo anuncio' }}</h1>
                    <p class="hw-subtitle">
                        {{ $editando ? 'El destinatario no puede cambiarse una vez publicado.' : 'Al publicar se notifica a los usuarios activos o solo al destinatario elegido.' }}
                    </p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ $editando ? route('anuncios_globales.ver', ['id' => $anuncio->id]) : route('anuncios_globales') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Cancelar
                </a>
            </div>
        </header>

        <form method="POST" enctype="multipart/form-data" novalidate data-anuncio-formulario
              action="{{ $editando ? route('anuncios_globales.actualizar', ['id' => $anuncio->id]) : route('anuncios_globales.insertar') }}">
            @csrf
            @if($editando)
                @method('PUT')
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">Hay campos que necesitan su atención. Revise los mensajes indicados.</div>
            @endif

            <div class="anuncio-secciones">
                {{-- CONTENIDO --}}
                <section class="hw-form-section">
                    <div class="hw-form-section-heading">
                        <span class="material-symbols-outlined">campaign</span>
                        <div>
                            <h3>Contenido</h3>
                            <p>Título, mensaje y prioridad del anuncio.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" name="titulo" id="anuncio_titulo" class="form-control @error('titulo') is-invalid @enderror" placeholder="Título" maxlength="150" value="{{ $valor('titulo', $anuncio?->titulo) }}" required>
                                <label for="anuncio_titulo">Título</label>
                            </div>
                            @error('titulo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select name="prioridad" id="anuncio_prioridad" class="form-select @error('prioridad') is-invalid @enderror" required>
                                    @foreach(\App\Models\AnuncioGlobal::PRIORIDADES as $clave => $nombre)
                                        <option value="{{ $clave }}" @selected($valor('prioridad', $anuncio?->prioridad ?? 'normal') === $clave)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <label for="anuncio_prioridad">Prioridad</label>
                            </div>
                            @error('prioridad')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea name="mensaje" id="anuncio_mensaje" class="form-control anuncio-textarea @error('mensaje') is-invalid @enderror" placeholder="Mensaje" maxlength="5000" required>{{ $valor('mensaje', $anuncio?->mensaje) }}</textarea>
                                <label for="anuncio_mensaje">Mensaje</label>
                            </div>
                            @error('mensaje')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea name="datos_extra" id="anuncio_datos_extra" class="form-control anuncio-textarea-sm @error('datos_extra') is-invalid @enderror" placeholder="Datos adicionales" maxlength="2000">{{ $valor('datos_extra', $anuncio?->datos_extra) }}</textarea>
                                <label for="anuncio_datos_extra">Datos adicionales (opcional)</label>
                            </div>
                            <div class="hw-field-hint">Indicaciones, enlaces, requisitos u otra información útil.</div>
                            @error('datos_extra')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                {{-- EVENTO --}}
                <section class="hw-form-section">
                    <div class="hw-form-section-heading">
                        <span class="material-symbols-outlined">event</span>
                        <div>
                            <h3>Evento y recordatorio</h3>
                            <p>Opcional. Sin evento, el anuncio es solo informativo y genera únicamente la notificación inicial.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="hw-switch-container">
                                <div class="hw-switch-information">
                                    <span class="material-symbols-outlined">event_available</span>
                                    <div>
                                        <span class="hw-switch-title">El anuncio tiene un evento con fecha y hora</span>
                                        <span class="hw-switch-description">Cada usuario recibirá un recordatorio {{ \App\Models\AnuncioGlobal::MINUTOS_RECORDATORIO }} minutos antes.</span>
                                    </div>
                                </div>
                                <div class="form-check form-switch hw-switch">
                                    <input type="hidden" name="tiene_evento" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="tiene_evento" id="anuncio_tiene_evento" value="1" data-evento-interruptor @checked($tieneEvento)>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="datetime-local" name="fecha_evento" id="anuncio_fecha_evento" class="form-control @error('fecha_evento') is-invalid @enderror" placeholder="Fecha y hora del evento"
                                       @if($minimo) min="{{ $minimo }}" @endif max="{{ $maximo }}" value="{{ $fechaEvento }}" data-evento-campo @disabled(!$tieneEvento)>
                                <label for="anuncio_fecha_evento">Fecha y hora del evento</label>
                            </div>
                            @error('fecha_evento')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="lugar" id="anuncio_lugar" class="form-control @error('lugar') is-invalid @enderror" placeholder="Lugar" maxlength="150" value="{{ $valor('lugar', $anuncio?->lugar) }}">
                                <label for="anuncio_lugar">Lugar o enlace de la reunión (opcional)</label>
                            </div>
                            @error('lugar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="hw-field-hint">La fecha debe ser desde ahora y hasta un año. Si el evento comienza en menos de {{ \App\Models\AnuncioGlobal::MINUTOS_RECORDATORIO }} minutos, la notificación inicial ya lo informa y no se envía recordatorio.</div>
                        </div>
                    </div>
                </section>

                {{-- DESTINATARIO --}}
                <section class="hw-form-section">
                    <div class="hw-form-section-heading">
                        <span class="material-symbols-outlined">person_pin</span>
                        <div>
                            <h3>Destinatario</h3>
                            <p>Opcional. Un anuncio dirigido solo lo ven el destinatario y los administradores, y el destinatario debe confirmarlo.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            @if($editando)
                                <dl class="mb-0">
                                    <div class="hw-detail-item">
                                        <dt>Dirigido a</dt>
                                        <dd>{{ $anuncio->esDirigido() ? $nombreDe($anuncio->destinatario) : 'Todo el personal (anuncio general)' }}</dd>
                                    </div>
                                </dl>
                            @else
                                <div class="form-floating">
                                    <select name="id_destinatario" id="anuncio_destinatario" class="form-select @error('id_destinatario') is-invalid @enderror">
                                        <option value="">Todo el personal (anuncio general)</option>
                                        @foreach($usuarios as $usuario)
                                            <option value="{{ $usuario->id }}" @selected((string) $valor('id_destinatario') === (string) $usuario->id)>
                                                {{ $nombreDe($usuario) }}{{ $usuario->rol ? ' · ' . $usuario->rol->rol : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="anuncio_destinatario">Destinatario</label>
                                </div>
                                @error('id_destinatario')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        @if($editando)
                            <div class="col-md-4">
                                <div class="hw-switch-container">
                                    <div class="hw-switch-information">
                                        <span class="material-symbols-outlined">visibility</span>
                                        <div>
                                            <span class="hw-switch-title">Anuncio visible</span>
                                            <span class="hw-switch-description">Si lo retira, los usuarios dejan de verlo.</span>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch hw-switch">
                                        <input type="hidden" name="activo" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="activo" id="anuncio_activo" value="1" @checked($activo)>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ADJUNTOS --}}
                <section class="hw-form-section">
                    <div class="hw-form-section-heading">
                        <span class="material-symbols-outlined">attach_file</span>
                        <div>
                            <h3>Archivos adjuntos</h3>
                            <p>Opcional. Hasta {{ $maxArchivos }} archivos de {{ $maxMb }} MB cada uno.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        @if($adjuntosActuales->isNotEmpty())
                            <div class="col-12">
                                <ul class="anuncio-adjuntos">
                                    @foreach($adjuntosActuales as $adjunto)
                                        <li>
                                            <span class="material-symbols-outlined">{{ $adjunto->icono() }}</span>
                                            <div>
                                                <strong>{{ $adjunto->nombre_original }}</strong>
                                                <span class="hw-muted small">{{ $adjunto->tamanhoLegible() }}</span>
                                            </div>
                                            <label class="form-check anuncio-quitar">
                                                <input class="form-check-input" type="checkbox" name="quitar_adjuntos[]" value="{{ $adjunto->id }}" data-adjunto-quitar @checked($quitar->contains($adjunto->id))>
                                                <span class="form-check-label">Quitar</span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="col-12">
                            <label for="anuncio_adjuntos" class="form-label">Agregar archivos</label>
                            <input type="file" name="adjuntos[]" id="anuncio_adjuntos" class="form-control @if($errors->has('adjuntos') || $errors->has('adjuntos.*')) is-invalid @endif" multiple
                                   accept="{{ \App\Models\AnuncioAdjunto::atributoAccept() }}"
                                   data-max-archivos="{{ $maxArchivos }}" data-max-bytes="{{ \App\Models\AnuncioAdjunto::MAX_KB * 1024 }}"
                                   data-actuales="{{ $adjuntosActuales->count() }}">
                            <div class="hw-field-hint">Formatos: {{ \App\Models\AnuncioAdjunto::formatosTexto() }}.</div>
                            <div class="invalid-feedback d-block" data-adjuntos-error>
                                @foreach(collect($errors->get('adjuntos'))->merge(collect($errors->get('adjuntos.*'))->flatten())->unique() as $mensaje)
                                    {{ $mensaje }}<br>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="anuncio-formulario-pie">
                <a href="{{ $editando ? route('anuncios_globales.ver', ['id' => $anuncio->id]) : route('anuncios_globales') }}" class="btn btn-tonal">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined">{{ $editando ? 'save' : 'send' }}</span>
                    {{ $editando ? 'Guardar cambios' : 'Publicar anuncio' }}
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/anuncios_globales/anuncios_globales.js') }}"></script>
@endpush
