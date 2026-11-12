@extends('layouts.app_layout')

@section('titulo', 'Auditoría de expedientes')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $lista = $eventos ?? collect();
        $claseAccion = [
            'consulta' => 'info', 'creacion' => 'success', 'modificacion' => 'warning', 'eliminacion' => 'error',
            'restauracion' => 'success', 'descarga' => 'info', 'acceso_excepcional' => 'error', 'gestion_acceso' => 'neutral',
        ];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">policy</span>
                </div>
                <div>
                    <h1 class="hw-title">Auditoría de expedientes</h1>
                    <p class="hw-subtitle">Quién consultó o modificó un expediente clínico. Es de solo lectura: ningún evento se puede editar ni borrar.</p>
                </div>
            </div>
        </header>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Filtros</h2>
                    <p class="hw-surface-description">Se muestran los 300 eventos más recientes que coincidan.</p>
                </div>
            </div>
            <form method="GET" action="{{ route('auditoria_expedientes') }}" class="hw-server-filters" style="padding: 0 1.25rem 1.25rem;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="text" name="dui" id="aud_dui" class="form-control" placeholder="DUI" maxlength="10" value="{{ $filtros['dui'] ?? '' }}">
                            <label for="aud_dui">DUI del paciente</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select name="tipo_accion" id="aud_accion" class="form-select">
                                <option value="">Todas</option>
                                @foreach($acciones as $accion)
                                    <option value="{{ $accion }}" @selected(($filtros['tipo_accion'] ?? '') === $accion)>{{ ucfirst(str_replace('_', ' ', $accion)) }}</option>
                                @endforeach
                            </select>
                            <label for="aud_accion">Acción</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="date" name="desde" id="aud_desde" class="form-control" placeholder="Desde" value="{{ $filtros['desde'] ?? '' }}">
                            <label for="aud_desde">Desde</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-floating">
                            <input type="date" name="hasta" id="aud_hasta" class="form-control" placeholder="Hasta" value="{{ $filtros['hasta'] ?? '' }}">
                            <label for="aud_hasta">Hasta</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">search</span>
                            Buscar
                        </button>
                        <a href="{{ route('auditoria_expedientes') }}" class="btn btn-tonal">Limpiar</a>
                    </div>
                </div>
            </form>
        </section>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Eventos</h2>
                    <p class="hw-surface-description">
                        @if($duiSinResultado) No existe un paciente con ese DUI. @else De los más recientes a los más antiguos. @endif
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">policy</span>
                    <h3>Sin eventos</h3>
                    <p>No hay eventos de auditoría con esos filtros.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Paciente</th>
                                <th>Acción</th>
                                <th>Entidad</th>
                                <th>Detalle</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $evento)
                                <tr>
                                    <td>{{ $fmt($evento->created_at) }}</td>
                                    <td>{{ $evento->usuario ? trim($evento->usuario->nombres . ' ' . $evento->usuario->apellidos) : 'Sistema' }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ trim(($evento->paciente?->nombres ?? '') . ' ' . ($evento->paciente?->apellidos ?? '')) ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $evento->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseAccion[$evento->tipo_accion] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst(str_replace('_', ' ', $evento->tipo_accion)) }}
                                        </span>
                                    </td>
                                    <td>{{ str_replace('_', ' ', $evento->entidad) }}{{ $evento->id_registro ? ' #' . $evento->id_registro : '' }}</td>
                                    <td>
                                        @if($evento->motivo)<span class="d-block">{{ \Illuminate\Support\Str::limit($evento->motivo, 90) }}</span>@endif
                                        @if($evento->datos_relevantes)
                                            <span class="hw-muted d-block">{{ collect($evento->datos_relevantes)->map(fn ($v, $k) => $k . ': ' . (is_scalar($v) ? $v : json_encode($v)))->implode(' · ') }}</span>
                                        @endif
                                    </td>
                                    <td class="hw-mono">{{ $evento->ip ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
