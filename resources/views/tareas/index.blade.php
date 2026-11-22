@extends('layouts.app_layout')

@section('titulo', 'Mis tareas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/tareas.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor) => $valor ? $valor->format('d/m/Y H:i') : null;
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];
        $nombrePrioridad = ['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'];
        $nombreEstado = ['pendiente' => 'Pendiente', 'en_progreso' => 'En progreso'];
        $iconoEstado = ['pendiente' => 'schedule', 'en_progreso' => 'play_circle'];

        $total = $tareas->count();
        $vencidas = $tareas->filter(fn ($t) => $t->estaVencida())->count();
        $abiertas = $total - $vencidas;
        $libres = max(0, $limite - $total);
        $lleno = $total >= $limite;

        $minimo = now()->format('Y-m-d\TH:i');
        $maximo = $maximoVencimiento->format('Y-m-d\TH:i');

        $viejoNueva = fn ($campo) => old('_modal') === 'modalNuevaTarea' ? old($campo) : '';
        $viejoEditar = fn ($campo) => old('_modal') === 'modalEditarTarea' ? old($campo) : '';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">checklist</span>
                </div>
                <div>
                    <h1 class="hw-title">Mis tareas</h1>
                    <p class="hw-subtitle">Su pizarrón personal: solo usted ve y modifica estas tareas.</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('tareas.papelera') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    Papelera
                    @if($enPapelera > 0)
                        <span class="hw-chip">{{ $enPapelera }}</span>
                    @endif
                </a>
                <button type="button" class="btn btn-primary hw-create-button" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea"
                        @disabled($lleno) @if($lleno) title="El pizarrón está lleno: envíe una tarea a la papelera" @endif>
                    <span class="material-symbols-outlined">add</span>
                    Nueva tarea
                </button>
            </div>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $abiertas }}</strong><span>Al día</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">alarm</span>
                <div><strong>{{ $vencidas }}</strong><span>Vencidas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">dashboard</span>
                <div><strong>{{ $total }} / {{ $limite }}</strong><span>En el pizarrón</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">delete_sweep</span>
                <div><strong>{{ $enPapelera }}</strong><span>En la papelera</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Pizarrón</h2>
                    <p class="hw-surface-description">
                        Ordenadas por prioridad y estado; las vencidas van al final.
                        @if($lleno)
                            El pizarrón está lleno: envíe una tarea a la papelera para crear otra.
                        @else
                            Le quedan {{ $libres }} {{ $libres === 1 ? 'espacio' : 'espacios' }}.
                        @endif
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $total }} de {{ $limite }}</span>
                </div>
            </div>

            @if($tareas->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">checklist</span>
                    <h3>El pizarrón está vacío</h3>
                    <p>Cree una tarea con "Nueva tarea". Las completadas, canceladas y vencidas salen solas del pizarrón.</p>
                </div>
            @else
                <div class="pizarron">
                    @foreach($tareas as $tarea)
                        @php($vencida = $tarea->estaVencida())
                        <article class="nota nota-{{ $tarea->prioridad }} {{ $vencida ? 'is-vencida' : '' }} {{ $tarea->estado === 'en_progreso' ? 'is-progreso' : '' }}">
                            <div class="nota-cabecera">
                                <span class="hw-status hw-status-{{ $clasePrioridad[$tarea->prioridad] ?? 'neutral' }}">
                                    <span class="hw-status-dot"></span>{{ $nombrePrioridad[$tarea->prioridad] ?? ucfirst($tarea->prioridad) }}
                                </span>
                                <span class="nota-estado">
                                    <span class="material-symbols-outlined">{{ $vencida ? 'alarm' : ($iconoEstado[$tarea->estado] ?? 'schedule') }}</span>
                                    {{ $vencida ? 'Vencida' : ($nombreEstado[$tarea->estado] ?? $tarea->estado) }}
                                </span>
                            </div>

                            <h3 class="nota-titulo" title="{{ $tarea->titulo }}">{{ $tarea->titulo }}</h3>

                            @if($tarea->descripcion)
                                <p class="nota-descripcion" title="{{ $tarea->descripcion }}">{{ \Illuminate\Support\Str::limit($tarea->descripcion, 70) }}</p>
                            @endif

                            <p class="nota-vence" title="Vence: {{ $fmt($tarea->fecha_vencimiento) }}">
                                <span class="material-symbols-outlined">event</span>
                                <span>{{ $fmt($tarea->fecha_vencimiento) }}</span>
                                <small>{{ $tarea->vencimientoRelativo() }}</small>
                            </p>

                            <div class="nota-acciones">
                                @if($tarea->estado === 'pendiente')
                                    <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="estado" value="en_progreso">
                                        <button type="submit" class="hw-action-button" title="Iniciar" aria-label="Iniciar la tarea">
                                            <span class="material-symbols-outlined">play_arrow</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="estado" value="pendiente">
                                        <button type="submit" class="hw-action-button" title="Volver a pendiente" aria-label="Volver a pendiente">
                                            <span class="material-symbols-outlined">pause</span>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}"
                                      data-confirmar="¿Completar la tarea &quot;{{ $tarea->titulo }}&quot;? Saldrá del pizarrón y quedará en la papelera.">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="estado" value="completada">
                                    <button type="submit" class="hw-action-button nota-completar" title="Completar" aria-label="Completar la tarea">
                                        <span class="material-symbols-outlined">check_circle</span>
                                    </button>
                                </form>

                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalEditarTarea"
                                        data-url="{{ route('tareas.actualizar', ['tarea' => $tarea->id]) }}"
                                        data-titulo="{{ $tarea->titulo }}"
                                        data-vencimiento="{{ $tarea->fecha_vencimiento?->format('Y-m-d\TH:i') }}"
                                        title="Cambiar vencimiento" aria-label="Cambiar el vencimiento">
                                    <span class="material-symbols-outlined">edit_calendar</span>
                                </button>

                                <form method="POST" action="{{ route('tareas.estado', ['tarea' => $tarea->id]) }}"
                                      data-confirmar="¿Cancelar la tarea &quot;{{ $tarea->titulo }}&quot;? Saldrá del pizarrón y quedará en la papelera.">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="estado" value="cancelada">
                                    <button type="submit" class="hw-action-button" title="Cancelar tarea" aria-label="Cancelar la tarea">
                                        <span class="material-symbols-outlined">cancel</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('tareas.eliminar', ['tarea' => $tarea->id]) }}"
                                      data-confirmar="¿Enviar la tarea &quot;{{ $tarea->titulo }}&quot; a la papelera?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hw-action-button hw-action-delete" title="Enviar a la papelera" aria-label="Enviar a la papelera">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- MODAL: NUEVA TAREA --}}
    <div class="modal fade hw-modal" id="modalNuevaTarea" tabindex="-1" aria-labelledby="tmodalNuevaTarea" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">add_task</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaTarea">Nueva tarea</h2>
                            <p class="hw-modal-subtitle">Le quedan {{ $libres }} {{ $libres === 1 ? 'espacio' : 'espacios' }} en el pizarrón.</p>
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
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" name="titulo" id="tarea_nueva_titulo" class="form-control" placeholder="Título" maxlength="150" value="{{ $viejoNueva('titulo') }}" required>
                                    <label for="tarea_nueva_titulo">Título</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="descripcion" id="tarea_nueva_descripcion" class="form-control hw-textarea-md" placeholder="Descripción" maxlength="2000">{{ $viejoNueva('descripcion') }}</textarea>
                                    <label for="tarea_nueva_descripcion">Descripción (opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="prioridad" id="tarea_nueva_prioridad" class="form-select" required>
                                        @foreach($nombrePrioridad as $valor => $nombre)
                                            <option value="{{ $valor }}" @selected(($viejoNueva('prioridad') ?: 'normal') === $valor)>{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    <label for="tarea_nueva_prioridad">Prioridad</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_recordatorio" id="tarea_nueva_recordatorio" class="form-control" placeholder="Recordatorio" min="{{ $minimo }}" max="{{ $maximo }}" value="{{ $viejoNueva('fecha_recordatorio') }}">
                                    <label for="tarea_nueva_recordatorio">Recordatorio (opcional)</label>
                                </div>
                            </div>
                            <div class="col-12" data-atajos-grupo>
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_vencimiento" id="tarea_nueva_vencimiento" class="form-control" placeholder="Vencimiento" min="{{ $minimo }}" max="{{ $maximo }}" value="{{ $viejoNueva('fecha_vencimiento') }}" required>
                                    <label for="tarea_nueva_vencimiento">Vencimiento</label>
                                </div>
                                <div class="hw-atajos" role="group" aria-label="Atajos de vencimiento">
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="0">Hoy</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="1">Mañana</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="7">En 1 semana</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="30">En 30 días</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">
                                    El vencimiento puede ser desde ahora hasta 30 días. Después de crearla, lo único que podrá cambiar es el vencimiento.
                                    Recibirá una notificación en cada cambio de estado, en el recordatorio y si la tarea vence.
                                </p>
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

    {{-- MODAL: CAMBIAR VENCIMIENTO --}}
    <div class="modal fade hw-modal" id="modalEditarTarea" tabindex="-1" aria-labelledby="tmodalEditarTarea" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit_calendar</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalEditarTarea">Cambiar vencimiento</h2>
                            <p class="hw-modal-subtitle" data-tarea-titulo>La fecha de vencimiento es lo único que se puede editar.</p>
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
                            <div class="col-12" data-atajos-grupo>
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_vencimiento" id="tarea_editar_vencimiento" class="form-control" placeholder="Vencimiento" min="{{ $minimo }}" max="{{ $maximo }}" value="{{ $viejoEditar('fecha_vencimiento') }}" required>
                                    <label for="tarea_editar_vencimiento">Nuevo vencimiento</label>
                                </div>
                                <div class="hw-atajos" role="group" aria-label="Atajos de vencimiento">
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="0">Hoy</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="1">Mañana</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="7">En 1 semana</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="30">En 30 días</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">No puede ser anterior a este momento ni mayor a 30 días. Se volverá a avisar cuando venza.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar vencimiento
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
