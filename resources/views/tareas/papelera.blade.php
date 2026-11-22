@extends('layouts.app_layout')

@section('titulo', 'Papelera de tareas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/tareas.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? $valor->format($formato) : '—';
        $clasePrioridad = ['baja' => 'neutral', 'normal' => 'info', 'alta' => 'warning', 'urgente' => 'error'];
        $nombrePrioridad = ['baja' => 'Baja', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'];
        $claseMotivo = ['completada' => 'success', 'cancelada' => 'neutral', 'vencida' => 'error', 'manual' => 'info'];

        $lleno = $enPizarron >= $limite;
        $minimo = now()->format('Y-m-d\TH:i');
        $maximo = $maximoVencimiento->format('Y-m-d\TH:i');
        $viejoRestaurar = fn ($campo) => old('_modal') === 'modalRestaurarTarea' ? old($campo) : '';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">delete_sweep</span>
                </div>
                <div>
                    <h1 class="hw-title">Papelera de tareas</h1>
                    <p class="hw-subtitle">Las tareas se eliminan definitivamente a los {{ $diasPapelera }} días. Al restaurarlas debe indicar un nuevo vencimiento.</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('tareas') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver al pizarrón
                </a>
                @if($tareas->isNotEmpty())
                    <form method="POST" action="{{ route('tareas.papelera.vaciar') }}" class="d-inline"
                          data-confirmar="¿Purgar toda la papelera? Se eliminarán definitivamente {{ $tareas->count() }} {{ $tareas->count() === 1 ? 'tarea' : 'tareas' }} y no podrá recuperarlas.">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <span class="material-symbols-outlined">delete_forever</span>
                            Purgar toda la papelera
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Tareas en la papelera</h2>
                    <p class="hw-surface-description">
                        @if($lleno)
                            El pizarrón está lleno ({{ $enPizarron }} / {{ $limite }}): libere un espacio para poder restaurar.
                        @else
                            Pizarrón: {{ $enPizarron }} / {{ $limite }} tareas.
                        @endif
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $tareas->count() }} registros</span>
                </div>
            </div>

            @if($tareas->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    <h3>La papelera está vacía</h3>
                    <p>Las tareas completadas, canceladas, vencidas o enviadas desde el pizarrón aparecerán aquí.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar en la papelera…" aria-label="Buscar en la papelera" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="3" aria-label="Filtrar por motivo">
                        <option value="">Motivo: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-tareas-papelera" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="hw-column-id">ID</th>
                                <th scope="col">Tarea</th>
                                <th scope="col">Prioridad</th>
                                <th scope="col">Motivo</th>
                                <th scope="col">Enviada</th>
                                <th scope="col">Se elimina</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tareas as $item)
                                <tr>
                                    <td class="hw-id">#{{ $item->id }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $item->titulo }}</span>
                                            @if($item->descripcion)
                                                <span class="hw-muted small" title="{{ $item->descripcion }}">{{ \Illuminate\Support\Str::limit($item->descripcion, 60) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $clasePrioridad[$item->prioridad] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ $nombrePrioridad[$item->prioridad] ?? ucfirst($item->prioridad) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseMotivo[$item->motivo_papelera] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ $item->etiquetaMotivo() }}
                                        </span>
                                    </td>
                                    <td>{{ $fmt($item->deleted_at) }}</td>
                                    <td>
                                        {{ $fmt($item->fechaDePurga(), 'd/m/Y') }}
                                        <span class="hw-muted small d-block">
                                            {{ $item->diasRestantesPapelera() === 1 ? 'Queda 1 día' : 'Quedan ' . $item->diasRestantesPapelera() . ' días' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="hw-actions">
                                            <button type="button" class="hw-action-button"
                                                    data-bs-toggle="modal" data-bs-target="#modalRestaurarTarea"
                                                    data-url="{{ route('tareas.restaurar', ['id' => $item->id]) }}"
                                                    data-titulo="{{ $item->titulo }}"
                                                    @disabled($lleno)
                                                    title="{{ $lleno ? 'El pizarrón está lleno' : 'Restaurar' }}" aria-label="Restaurar la tarea">
                                                <span class="material-symbols-outlined">restore_from_trash</span>
                                            </button>
                                            <form method="POST" action="{{ route('tareas.purgar', ['id' => $item->id]) }}" class="d-inline"
                                                  data-confirmar="¿Eliminar definitivamente la tarea &quot;{{ $item->titulo }}&quot;? No podrá recuperarla.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="hw-action-button hw-action-delete" title="Eliminar definitivamente" aria-label="Eliminar definitivamente">
                                                    <span class="material-symbols-outlined">delete_forever</span>
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

    {{-- MODAL: RESTAURAR TAREA --}}
    <div class="modal fade hw-modal" id="modalRestaurarTarea" tabindex="-1" aria-labelledby="tmodalRestaurarTarea" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">restore_from_trash</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalRestaurarTarea">Restaurar tarea</h2>
                            <p class="hw-modal-subtitle" data-tarea-titulo>Indique un nuevo vencimiento.</p>
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
                        <input type="hidden" name="_modal" value="modalRestaurarTarea">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <div class="row g-3">
                            <div class="col-12" data-atajos-grupo>
                                <div class="form-floating">
                                    <input type="datetime-local" name="fecha_vencimiento" id="tarea_restaurar_vencimiento" class="form-control" placeholder="Vencimiento" min="{{ $minimo }}" max="{{ $maximo }}" value="{{ $viejoRestaurar('fecha_vencimiento') }}" required>
                                    <label for="tarea_restaurar_vencimiento">Nuevo vencimiento</label>
                                </div>
                                <div class="hw-atajos" role="group" aria-label="Atajos de vencimiento">
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="0">Hoy</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="1">Mañana</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="7">En 1 semana</button>
                                    <button type="button" class="btn btn-tonal btn-sm" data-atajo-dias="30">En 30 días</button>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="hw-field-hint mb-0">La tarea vuelve al pizarrón como pendiente. El vencimiento no puede ser anterior a este momento ni mayor a 30 días.</p>
                            </div>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">restore_from_trash</span>
                            Restaurar tarea
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/tarea/papelera.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
