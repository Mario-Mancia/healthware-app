@extends('layouts.app_layout')

@section('titulo', 'Mis tareas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor) => $valor ? $valor->format('d/m/Y H:i') : null;
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];
        $nombreEstado = ['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completada' => 'Completada', 'cancelada' => 'Cancelada'];
        $viejoNueva = fn ($campo) => old('_modal') === 'modalNuevaTarea' ? old($campo) : '';
        $viejoEditar = fn ($campo) => old('_modal') === 'modalEditarTarea' ? old($campo) : '';
        $secciones = [
            ['titulo' => 'Vencidas', 'icono' => 'alarm', 'lista' => $vencidas, 'clase' => 'is-vencida', 'vacio' => 'No tiene tareas vencidas.'],
            ['titulo' => 'Pendientes y en progreso', 'icono' => 'pending_actions', 'lista' => $abiertas, 'clase' => '', 'vacio' => 'No tiene tareas abiertas. Cree una con "Nueva tarea".'],
            ['titulo' => 'Completadas', 'icono' => 'task_alt', 'lista' => $completadas, 'clase' => 'is-completada', 'vacio' => 'Aún no ha completado tareas.'],
            ['titulo' => 'Canceladas', 'icono' => 'cancel', 'lista' => $canceladas, 'clase' => 'is-cancelada', 'vacio' => 'No tiene tareas canceladas.'],
        ];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">checklist</span>
                </div>
                <div>
                    <h1 class="hw-title">Mis tareas</h1>
                    <p class="hw-subtitle">Su espacio de trabajo personal: solo usted ve y modifica estas tareas.</p>
                </div>
            </div>
            <button type="button" class="btn btn-primary hw-create-button" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                <span class="material-symbols-outlined">add</span>
                Nueva tarea
            </button>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $abiertas->count() }}</strong><span>Abiertas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">alarm</span>
                <div><strong>{{ $vencidas->count() }}</strong><span>Vencidas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">task_alt</span>
                <div><strong>{{ $completadas->count() }}</strong><span>Completadas</span></div>
            </div>
        </div>

        @foreach($secciones as $seccion)
            <section class="hw-surface">
                <div class="hw-surface-header">
                    <div>
                        <h2 class="hw-surface-title">{{ $seccion['titulo'] }}</h2>
                        <p class="hw-surface-description">{{ $seccion['lista']->count() }} tareas.</p>
                    </div>
                </div>

                <div class="exp-seccion-cuerpo">
                    @forelse($seccion['lista'] as $tarea)
                        <article class="tarea-card {{ $seccion['clase'] }}">
                            <div class="tarea-cabecera">
                                <h3>{{ $tarea->titulo }}</h3>
                                <div class="tarea-etiquetas">
                                    <span class="hw-status hw-status-{{ $clasePrioridad[$tarea->prioridad] ?? 'neutral' }}">
                                        <span class="hw-status-dot"></span>{{ ucfirst($tarea->prioridad) }}
                                    </span>
                                    <span class="hw-chip">{{ $nombreEstado[$tarea->estado] ?? $tarea->estado }}</span>
                                </div>
                            </div>

                            @if($tarea->descripcion)
                                <p class="tarea-descripcion">{{ $tarea->descripcion }}</p>
                            @endif

                            <div class="tarea-fechas">
                                @if($tarea->fecha_vencimiento)<span><span class="material-symbols-outlined">event</span> Vence: {{ $fmt($tarea->fecha_vencimiento) }}</span>@endif
                                @if($tarea->fecha_recordatorio)<span><span class="material-symbols-outlined">notifications</span> Recordatorio: {{ $fmt($tarea->fecha_recordatorio) }}</span>@endif
                                @if($tarea->fecha_completada)<span><span class="material-symbols-outlined">check_circle</span> Completada: {{ $fmt($tarea->fecha_completada) }}</span>@endif
                            </div>

                            <div class="tarea-acciones">
                                @if($tarea->estaAbierta())
                                    @if($tarea->estado === 'pendiente')
                                        <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="estado" value="en_progreso">
                                            <button type="submit" class="btn btn-tonal btn-sm">Iniciar</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="estado" value="completada">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <span class="material-symbols-outlined">check</span>
                                            Completar
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="estado" value="pendiente">
                                        <button type="submit" class="btn btn-tonal btn-sm">Reabrir</button>
                                    </form>
                                @endif

                                <button type="button" class="btn btn-tonal btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarTarea"
                                        data-url="{{ route('tareas.actualizar', ['tarea' => $tarea->id]) }}"
                                        data-tarea="{{ json_encode([
                                            'titulo' => $tarea->titulo,
                                            'descripcion' => $tarea->descripcion,
                                            'prioridad' => $tarea->prioridad,
                                            'estado' => $tarea->estado,
                                            'fecha_inicio' => $tarea->fecha_inicio?->format('Y-m-d\TH:i'),
                                            'fecha_vencimiento' => $tarea->fecha_vencimiento?->format('Y-m-d\TH:i'),
                                            'fecha_recordatorio' => $tarea->fecha_recordatorio?->format('Y-m-d\TH:i'),
                                        ]) }}">
                                    <span class="material-symbols-outlined">edit</span>
                                    Editar
                                </button>

                                <form method="POST" action="{{ route('tareas.eliminar', ['tarea' => $tarea->id]) }}"
                                      data-confirmar="¿Eliminar la tarea &quot;{{ $tarea->titulo }}&quot;?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-text btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="hw-muted mb-0">{{ $seccion['vacio'] }}</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <div class="modal fade hw-modal" id="modalNuevaTarea" tabindex="-1" aria-labelledby="tmodalNuevaTarea" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">add_task</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaTarea">Nueva tarea</h2>
                            <p class="hw-modal-subtitle">Una tarea personal con prioridad, vencimiento y recordatorio.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('tareas.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaTarea">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="titulo" id="tarea_nueva_titulo" class="form-control" placeholder="Título" maxlength="150" value="{{ $viejoNueva('titulo') }}" required>
                                    <label for="tarea_nueva_titulo">Título</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="descripcion" id="tarea_nueva_descripcion" class="form-control" placeholder="Descripción" maxlength="2000" style="min-height: 6rem;">{{ $viejoNueva('descripcion') }}</textarea>
                                    <label for="tarea_nueva_descripcion">Descripción (opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="prioridad" id="tarea_nueva_prioridad" class="form-select" required>
                                        @foreach(['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(($viejoNueva('prioridad') ?: 'normal') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="tarea_nueva_prioridad">Prioridad</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_inicio" id="tarea_nueva_inicio" class="form-control" placeholder="Inicio" value="{{ $viejoNueva('fecha_inicio') }}">
                                    <label for="tarea_nueva_inicio">Inicio</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_vencimiento" id="tarea_nueva_vencimiento" class="form-control" placeholder="Vencimiento" value="{{ $viejoNueva('fecha_vencimiento') }}">
                                    <label for="tarea_nueva_vencimiento">Vencimiento</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_recordatorio" id="tarea_nueva_recordatorio" class="form-control" placeholder="Recordatorio" value="{{ $viejoNueva('fecha_recordatorio') }}">
                                    <label for="tarea_nueva_recordatorio">Recordatorio</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">Recibirá una notificación en la fecha del recordatorio y otra si la tarea vence sin completarse.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">add_task</span>
                            Crear tarea
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    <div class="modal fade hw-modal" id="modalEditarTarea" tabindex="-1" aria-labelledby="tmodalEditarTarea" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalEditarTarea">Editar tarea</h2>
                            <p class="hw-modal-subtitle">Si cambia el vencimiento o el recordatorio, se vuelve a avisar.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-tarea-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalEditarTarea">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="titulo" id="tarea_editar_titulo" class="form-control" placeholder="Título" maxlength="150" value="{{ $viejoEditar('titulo') }}" required>
                                    <label for="tarea_editar_titulo">Título</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="descripcion" id="tarea_editar_descripcion" class="form-control" placeholder="Descripción" maxlength="2000" style="min-height: 6rem;">{{ $viejoEditar('descripcion') }}</textarea>
                                    <label for="tarea_editar_descripcion">Descripción (opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="prioridad" id="tarea_editar_prioridad" class="form-select" required>
                                        @foreach(['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(($viejoEditar('prioridad') ?: 'normal') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="tarea_editar_prioridad">Prioridad</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="estado" id="tarea_editar_estado" class="form-select">
                                        @foreach(['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso', 'completada' => 'Completada', 'cancelada' => 'Cancelada'] as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected($viejoEditar('estado') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="tarea_editar_estado">Estado</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_inicio" id="tarea_editar_inicio" class="form-control" placeholder="Inicio" value="{{ $viejoEditar('fecha_inicio') }}">
                                    <label for="tarea_editar_inicio">Inicio</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_vencimiento" id="tarea_editar_vencimiento" class="form-control" placeholder="Vencimiento" value="{{ $viejoEditar('fecha_vencimiento') }}">
                                    <label for="tarea_editar_vencimiento">Vencimiento</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_recordatorio" id="tarea_editar_recordatorio" class="form-control" placeholder="Recordatorio" value="{{ $viejoEditar('fecha_recordatorio') }}">
                                    <label for="tarea_editar_recordatorio">Recordatorio</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">Recibirá una notificación en la fecha del recordatorio y otra si la tarea vence sin completarse.</p>
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
@endsection

@push('scripts')
    <script src="{{ asset('js/tarea/tareas.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
