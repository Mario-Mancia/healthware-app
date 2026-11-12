@extends('layouts.app_layout')

@section('titulo', 'Relaciones asistenciales')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $esAdmin = $rolActual === 'administrador';
        $lista = $relaciones ?? collect();
        $claseEstado = ['activa' => 'success', 'suspendida' => 'warning', 'cerrada' => 'neutral', 'revocada' => 'error'];
        $nombreTipo = ['principal' => 'Principal', 'especializada' => 'Especializada', 'referencia' => 'Por referencia', 'seguimiento' => 'Seguimiento'];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">diversity_3</span>
                </div>
                <div>
                    <h1 class="hw-title">Relaciones asistenciales</h1>
                    <p class="hw-subtitle">Quién puede consultar el expediente de cada paciente. Se crean al programar una cita o una referencia.</p>
                </div>
            </div>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">diversity_3</span>
                <div><strong>{{ $lista->count() }}</strong><span>Relaciones</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">verified_user</span>
                <div><strong>{{ $lista->where('estado', 'activa')->count() }}</strong><span>Activas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">pause_circle</span>
                <div><strong>{{ $lista->where('estado', 'suspendida')->count() }}</strong><span>Suspendidas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">history</span>
                <div><strong>{{ $lista->whereIn('estado', ['cerrada', 'revocada'])->count() }}</strong><span>En historial</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">{{ $esAdmin ? 'Todas las relaciones' : 'Mis relaciones' }}</h2>
                    <p class="hw-surface-description">
                        Una relación activa permite consultar el expediente del paciente. Al cerrarla (con motivo) se conserva como historial y se pierde el acceso ordinario.
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">diversity_3</span>
                    <h3>Aún no hay relaciones</h3>
                    <p>Se crean cuando recepción programa una cita con un médico.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar por paciente, DUI o médico…" aria-label="Buscar en la tabla" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="2" aria-label="Filtrar por tipo">
                        <option value="">Tipo: todos</option>
                    </select>
                    <select class="form-select" data-hw-filtro-columna="3" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-relaciones" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Paciente</th>
                                <th scope="col">Médico</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Inicio</th>
                                <th scope="col">Fin y motivo</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $item)
                                @php
                                    $nombrePaciente = trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? ''));
                                    $nombreMedico = trim(($item->doctor?->usuario?->nombres ?? '') . ' ' . ($item->doctor?->usuario?->apellidos ?? ''));
                                    $activa = $item->estado === 'activa';
                                    $suspendida = $item->estado === 'suspendida';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombrePaciente ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombreMedico ?: '—' }}</span>
                                            <span class="hw-identity-caption">{{ $item->doctor?->especialidad?->especialidad ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $nombreTipo[$item->tipo_relacion] ?? $item->tipo_relacion }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseEstado[$item->estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($item->estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $fmt($item->fecha_inicio, 'd/m/Y') }}
                                        <span class="hw-muted d-block">{{ \Illuminate\Support\Str::limit($item->motivo, 48) }}</span>
                                    </td>
                                    <td>
                                        @if($item->fecha_fin)
                                            {{ $fmt($item->fecha_fin, 'd/m/Y') }}
                                            <span class="hw-muted d-block">{{ \Illuminate\Support\Str::limit((string) $item->motivo_cierre, 48) }}</span>
                                        @else
                                            <span class="hw-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="hw-actions">
                                            @if($activa || $suspendida)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Cerrar la relación" aria-label="Cerrar la relación"
                                                        data-url="{{ route('relaciones.cerrar', ['relacion' => $item->id]) }}"
                                                        data-titulo="Cerrar la relación asistencial"
                                                        data-descripcion="El médico dejará de tener acceso ordinario al expediente. La relación se conserva como historial."
                                                        data-boton="Cerrar relación">
                                                    <span class="material-symbols-outlined">lock</span>
                                                </button>
                                            @endif
                                            @if($esAdmin && $activa)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Suspender" aria-label="Suspender"
                                                        data-url="{{ route('relaciones.suspender', ['relacion' => $item->id]) }}"
                                                        data-titulo="Suspender la relación asistencial"
                                                        data-descripcion="Se suspende el acceso mientras se aclara una situación. Puede reactivarse."
                                                        data-boton="Suspender">
                                                    <span class="material-symbols-outlined">pause_circle</span>
                                                </button>
                                            @endif
                                            @if($esAdmin && $suspendida)
                                                <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Reactivar" aria-label="Reactivar"
                                                        data-url="{{ route('relaciones.reactivar', ['relacion' => $item->id]) }}"
                                                        data-titulo="Reactivar la relación asistencial"
                                                        data-descripcion="El médico recupera el acceso ordinario al expediente."
                                                        data-boton="Reactivar">
                                                    <span class="material-symbols-outlined">play_circle</span>
                                                </button>
                                            @endif
                                            @if($esAdmin && ($activa || $suspendida))
                                                <button type="button" class="hw-action-button hw-action-delete" data-bs-toggle="modal" data-bs-target="#modalMotivo"
                                                        title="Revocar" aria-label="Revocar"
                                                        data-url="{{ route('relaciones.revocar', ['relacion' => $item->id]) }}"
                                                        data-titulo="Revocar la relación asistencial"
                                                        data-descripcion="Se retira el acceso por una causa administrativa. Queda en el historial y en la auditoría."
                                                        data-boton="Revocar">
                                                    <span class="material-symbols-outlined">block</span>
                                                </button>
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

    <div class="modal fade hw-modal" id="modalMotivo" tabindex="-1" aria-labelledby="tmodalMotivo" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit_note</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalMotivo">Confirmar acción</h2>
                            <p class="hw-modal-subtitle">Indique el motivo: quedará registrado.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="#" data-motivo-formulario novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalMotivo">
                        <input type="hidden" name="_url" value="{{ old('_url') }}">
                        <p class="mb-3" data-motivo-descripcion></p>
                        <div class="form-floating">
                            <textarea name="motivo" id="accion_motivo" class="form-control" placeholder="Motivo" maxlength="500" required>{{ old('_modal') === 'modalMotivo' ? old('motivo') : '' }}</textarea>
                            <label for="accion_motivo">Motivo</label>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" data-motivo-boton>
                            <span class="material-symbols-outlined">check</span>
                            <span data-motivo-boton-texto>Confirmar</span>
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-accion-motivo.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
