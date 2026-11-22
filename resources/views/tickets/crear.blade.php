@extends('layouts.app_layout')

@section('titulo', 'Nuevo reporte')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/tickets.css') }}">
@endpush

@section('contenido')
    @php
        $maxArchivos = \App\Services\AlmacenArchivos::MAX_ARCHIVOS;
        $maxMb = \App\Services\AlmacenArchivos::MAX_KB / 1024;
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">add_circle</span>
                </div>
                <div>
                    <h1 class="hw-title">Nuevo reporte</h1>
                    <p class="hw-subtitle">Describa el problema o la solicitud. Un administrador lo tomará y usted recibirá una notificación en cada cambio.</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('tickets') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Cancelar
                </a>
            </div>
        </header>

        <form method="POST" action="{{ route('tickets.insertar') }}" enctype="multipart/form-data" novalidate data-ticket-formulario>
            @csrf

            @if($errors->any())
                <div class="alert alert-danger" role="alert">Hay campos que necesitan su atención. Revise los mensajes indicados.</div>
            @endif

            <div class="ticket-secciones">
                <section class="hw-form-section">
                    <div class="hw-form-section-heading">
                        <span class="material-symbols-outlined">report</span>
                        <div>
                            <h3>Datos del reporte</h3>
                            <p>Qué tipo de problema es y qué tan urgente.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" name="titulo" id="ticket_titulo" class="form-control @error('titulo') is-invalid @enderror" placeholder="Título" maxlength="150" value="{{ old('titulo') }}" required>
                                <label for="ticket_titulo">Título breve</label>
                            </div>
                            @error('titulo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="tipo" id="ticket_tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                                    <option value="" disabled @selected(!old('tipo'))>Seleccione…</option>
                                    @foreach(\App\Models\Ticket::TIPOS as $clave => $nombre)
                                        <option value="{{ $clave }}" @selected(old('tipo') === $clave)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <label for="ticket_tipo">Tipo de reporte</label>
                            </div>
                            @error('tipo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="prioridad" id="ticket_prioridad" class="form-select @error('prioridad') is-invalid @enderror" required>
                                    @foreach(\App\Models\Ticket::PRIORIDADES as $clave => $nombre)
                                        <option value="{{ $clave }}" @selected((old('prioridad') ?: 'normal') === $clave)>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <label for="ticket_prioridad">Prioridad</label>
                            </div>
                            <div class="hw-field-hint">Urgente: impide trabajar. Alta: afecta su trabajo. Normal: puede esperar. Baja: mejora o duda.</div>
                            @error('prioridad')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" name="modulo_afectado" id="ticket_modulo" class="form-control @error('modulo_afectado') is-invalid @enderror" placeholder="Módulo o pantalla" maxlength="100" value="{{ old('modulo_afectado') }}">
                                <label for="ticket_modulo">Módulo o pantalla afectada (opcional)</label>
                            </div>
                            @error('modulo_afectado')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="hw-form-section">
                    <div class="hw-form-section-heading">
                        <span class="material-symbols-outlined">description</span>
                        <div>
                            <h3>Detalles</h3>
                            <p>Qué hacía, qué esperaba y qué ocurrió. Si hay un mensaje de error, cópielo tal cual.</p>
                        </div>
                    </div>
                    <div class="form-floating">
                        <textarea name="descripcion" id="ticket_descripcion" class="form-control ticket-textarea @error('descripcion') is-invalid @enderror" placeholder="Descripción" maxlength="5000" required>{{ old('descripcion') }}</textarea>
                        <label for="ticket_descripcion">Descripción del problema</label>
                    </div>
                    @error('descripcion')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </section>

                <section class="hw-form-section">
                    <div class="hw-form-section-heading">
                        <span class="material-symbols-outlined">attach_file</span>
                        <div>
                            <h3>Archivos adjuntos</h3>
                            <p>Opcional. Una captura de pantalla ayuda mucho. Hasta {{ $maxArchivos }} archivos de {{ $maxMb }} MB cada uno.</p>
                        </div>
                    </div>
                    <input type="file" name="adjuntos[]" id="ticket_adjuntos" class="form-control @if($errors->has('adjuntos') || $errors->has('adjuntos.*')) is-invalid @endif" multiple
                           accept="{{ \App\Services\AlmacenArchivos::atributoAccept() }}"
                           data-max-archivos="{{ $maxArchivos }}" data-max-bytes="{{ \App\Services\AlmacenArchivos::MAX_KB * 1024 }}">
                    <div class="hw-field-hint">Formatos: {{ \App\Services\AlmacenArchivos::formatosTexto() }}.</div>
                    <div class="invalid-feedback d-block" data-adjuntos-error>
                        @foreach(collect($errors->get('adjuntos'))->merge(collect($errors->get('adjuntos.*'))->flatten())->unique() as $mensaje)
                            {{ $mensaje }}<br>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="ticket-formulario-pie">
                <a href="{{ route('tickets') }}" class="btn btn-tonal">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined">send</span>
                    Enviar reporte
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ticket/tickets.js') }}"></script>
@endpush
