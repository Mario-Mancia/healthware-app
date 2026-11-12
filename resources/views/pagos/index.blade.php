@extends('layouts.app_layout')

@section('titulo', 'Pagos')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $listaPagos = $pagos ?? collect();
        $pendientes = $facturasPendientes ?? collect();
        $clasePago = ['aprobado' => 'success', 'rechazado' => 'error'];
        $fmt = fn ($valor, $formato = 'd/m/Y H:i') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">credit_card</span>
                </div>
                <div>
                    <h1 class="hw-title">Pagos</h1>
                    <p class="hw-subtitle">Cobros registrados en efectivo y con tarjeta de prueba.</p>
                </div>
            </div>
            <a href="{{ route('facturas') }}" class="btn btn-tonal">
                <span class="material-symbols-outlined">receipt_long</span>
                Ir a facturas
            </a>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">payments</span>
                <div><strong>{{ $listaPagos->count() }}</strong><span>Pagos</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">check_circle</span>
                <div><strong>${{ number_format((float) $listaPagos->where('estado', 'aprobado')->sum('monto'), 2) }}</strong><span>Cobrado</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">error</span>
                <div><strong>{{ $listaPagos->where('estado', 'rechazado')->count() }}</strong><span>Rechazados</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">hourglass_top</span>
                <div><strong>{{ $pendientes->count() }}</strong><span>Facturas con saldo</span></div>
            </div>
        </div>

        @if($pendientes->isNotEmpty())
            <section class="hw-surface">
                <div class="hw-surface-header">
                    <div>
                        <h2 class="hw-surface-title">Facturas con saldo</h2>
                        <p class="hw-surface-description">Abra la factura para registrar el cobro.</p>
                    </div>
                </div>
                <div class="exp-seccion-cuerpo exp-chips">
                    @foreach($pendientes as $factura)
                        <a href="{{ route('facturas.ver', ['id' => $factura->id]) }}" class="hw-chip">
                            {{ $factura->numero_factura }} · {{ trim(($factura->paciente?->nombres ?? '') . ' ' . ($factura->paciente?->apellidos ?? '')) }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Historial de pagos</h2>
                    <p class="hw-surface-description">De los más recientes a los más antiguos.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $listaPagos->count() }} registros</span>
                </div>
            </div>

            @if($listaPagos->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">credit_card_off</span>
                    <h3>Aún no hay pagos</h3>
                    <p>Cuando se cobre una factura aparecerá aquí.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar por referencia, factura o paciente…" aria-label="Buscar en la tabla" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="3" aria-label="Filtrar por método">
                        <option value="">Método: todos</option>
                    </select>
                    <select class="form-select" data-hw-filtro-columna="5" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-pagos" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Referencia</th>
                                <th>Factura</th>
                                <th>Paciente</th>
                                <th>Método</th>
                                <th class="text-end">Monto</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($listaPagos as $item)
                                <tr>
                                    <td class="hw-mono">{{ $item->referencia }}</td>
                                    <td>
                                        <a href="{{ route('facturas.ver', ['id' => $item->id_factura]) }}">{{ $item->factura?->numero_factura ?? '—' }}</a>
                                    </td>
                                    <td>{{ trim(($item->factura?->paciente?->nombres ?? '') . ' ' . ($item->factura?->paciente?->apellidos ?? '')) ?: '—' }}</td>
                                    <td>{{ ucfirst($item->metodo_pago) }}{{ $item->ultimos_digitos ? ' ••' . $item->ultimos_digitos : '' }}</td>
                                    <td class="text-end hw-mono">${{ number_format((float) $item->monto, 2) }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $clasePago[$item->estado] ?? 'neutral' }}" title="{{ $item->motivo_rechazo }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($item->estado) }}
                                        </span>
                                    </td>
                                    <td>{{ $fmt($item->fecha_pago) }}</td>
                                    <td>{{ trim(($item->registrador?->nombres ?? '') . ' ' . ($item->registrador?->apellidos ?? '')) ?: '—' }}</td>
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
