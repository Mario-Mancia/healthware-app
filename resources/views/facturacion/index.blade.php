@extends('layouts.app_layout')

@section('titulo', 'Facturas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeFacturar = $rolActual === 'contador';
        $listaFacturas = $facturas ?? collect();
        $pendientes = $ordenesPendientes ?? collect();
        $tasaImpuesto = \App\Models\Factura::TASA_IMPUESTO;
        $claseFactura = ['pendiente' => 'warning', 'pago_parcial' => 'info', 'pagada' => 'success', 'cancelada' => 'error'];
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $porCobrar = $listaFacturas->whereIn('estado', ['pendiente', 'pago_parcial'])->sum(fn ($f) => max(0, (float) $f->total - (float) $f->pagos->where('estado', 'aprobado')->sum('monto')));
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">receipt_long</span>
                </div>
                <div>
                    <h1 class="hw-title">Facturas</h1>
                    <p class="hw-subtitle">Emisión de facturas a partir de las órdenes de servicio y seguimiento de los cobros.</p>
                </div>
            </div>
            <a href="{{ route('pagos') }}" class="btn btn-tonal">
                <span class="material-symbols-outlined">credit_card</span>
                Ver pagos
            </a>
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $pendientes->count() }}</strong><span>Órdenes por facturar</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">receipt_long</span>
                <div><strong>{{ $listaFacturas->count() }}</strong><span>Facturas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">hourglass_top</span>
                <div><strong>{{ $listaFacturas->whereIn('estado', ['pendiente', 'pago_parcial'])->count() }}</strong><span>Con saldo</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">payments</span>
                <div><strong>${{ number_format((float) $porCobrar, 2) }}</strong><span>Por cobrar</span></div>
            </div>
        </div>

        {{-- ÓRDENES PENDIENTES --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Órdenes pendientes de facturar</h2>
                    <p class="hw-surface-description">Creadas por médicos y recepción. El precio sale del catálogo de servicios.</p>
                </div>
            </div>
            @if($pendientes->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">task_alt</span>
                    <h3>No hay órdenes pendientes</h3>
                    <p>Las órdenes nuevas aparecerán aquí para facturarlas.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Paciente</th>
                                <th>Servicios</th>
                                <th class="text-end">Subtotal</th>
                                <th>Creada</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendientes as $orden)
                                @php
                                    $subtotalOrden = (float) $orden->detalles->sum('subtotal');
                                    $nombreOrden = trim(($orden->paciente?->nombres ?? '') . ' ' . ($orden->paciente?->apellidos ?? ''));
                                @endphp
                                <tr>
                                    <td class="hw-mono">{{ $orden->numero_orden }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ $nombreOrden ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $orden->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $orden->detalles->map(fn ($d) => $d->descripcion ?: ($d->servicio?->servicio ?? '—'))->implode(', ') }}</td>
                                    <td class="text-end hw-mono">${{ number_format($subtotalOrden, 2) }}</td>
                                    <td>
                                        {{ $fmtFecha($orden->fecha_orden) }}
                                        <span class="hw-muted d-block">{{ trim(($orden->creador?->nombres ?? '') . ' ' . ($orden->creador?->apellidos ?? '')) ?: '—' }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($puedeFacturar)
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalFacturar"
                                                    data-orden-id="{{ $orden->id }}"
                                                    data-orden-numero="{{ $orden->numero_orden }}"
                                                    data-paciente="{{ $nombreOrden }} (DUI {{ $orden->paciente?->numero_identificacion ?? '—' }})"
                                                    data-subtotal="{{ $subtotalOrden }}">
                                                <span class="material-symbols-outlined">receipt_long</span>
                                                Facturar
                                            </button>
                                        @else
                                            <span class="hw-muted">Solo el contador factura</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- FACTURAS --}}
        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Facturas emitidas</h2>
                    <p class="hw-surface-description">Abra una factura para cobrarla, imprimirla o consultarla.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $listaFacturas->count() }} registros</span>
                </div>
            </div>

            @if($listaFacturas->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <h3>Aún no hay facturas</h3>
                    <p>Cuando se emita una factura aparecerá aquí.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar por factura, paciente o DUI…" aria-label="Buscar en la tabla" autocomplete="off">
                        <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <select class="form-select" data-hw-filtro-columna="5" aria-label="Filtrar por estado">
                        <option value="">Estado: todos</option>
                    </select>
                    <span class="hw-toolbar-count" data-hw-contador></span>
                    <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
                </div>

                <div class="table-responsive hw-table-container">
                    <table id="tabla-facturas" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Paciente</th>
                                <th>Emisión</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Saldo</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($listaFacturas as $item)
                                @php
                                    $pagadoItem = (float) $item->pagos->where('estado', 'aprobado')->sum('monto');
                                    $saldoItem = $item->estado === 'cancelada' ? 0 : max(0, (float) $item->total - $pagadoItem);
                                @endphp
                                <tr>
                                    <td class="hw-mono">{{ $item->numero_factura }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? '')) ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $fmtFecha($item->fecha_emision) }}</td>
                                    <td class="text-end hw-mono">${{ number_format((float) $item->total, 2) }}</td>
                                    <td class="text-end hw-mono">${{ number_format($saldoItem, 2) }}</td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseFactura[$item->estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst(str_replace('_', ' ', $item->estado)) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('facturas.ver', ['id' => $item->id]) }}" class="hw-action-button" title="Abrir factura" aria-label="Abrir factura">
                                            <span class="material-symbols-outlined">open_in_new</span>
                                        </a>
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

    @if($puedeFacturar)
    <div class="modal fade hw-modal" id="modalFacturar" tabindex="-1" aria-labelledby="tmodalFacturar" aria-hidden="true" data-tasa="{{ $tasaImpuesto }}">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">receipt_long</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalFacturar">Emitir factura</h2>
                            <p class="hw-modal-subtitle">Se numera automáticamente (FAC-AAAA-NNNNNN).</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('facturas.emitir') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalFacturar">
                        <input type="hidden" name="id_orden" value="{{ old('id_orden') }}">
                        <div class="hw-document-party mb-3">
                            <h3>Orden</h3>
                            <p><strong data-fac-orden>—</strong></p>
                            <p data-fac-paciente>—</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" name="descuento" id="fac_descuento" class="form-control" placeholder="Descuento"
                                           min="0" step="0.01" value="{{ old('descuento') }}">
                                    <label for="fac_descuento">Descuento (USD, opcional)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="fac_notas" class="form-control" placeholder="Notas" maxlength="255" value="{{ old('notas') }}">
                                    <label for="fac_notas">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>
                        <div class="hw-invoice-summary mt-3">
                            <div><span>Subtotal</span><span data-prev-subtotal>$0.00</span></div>
                            <div><span>Descuento</span><span data-prev-descuento>-$0.00</span></div>
                            <div><span>Impuesto ({{ rtrim(rtrim(number_format($tasaImpuesto * 100, 2), '0'), '.') }} %)</span><span data-prev-impuesto>$0.00</span></div>
                            <div><span>Total</span><span data-prev-total>$0.00</span></div>
                        </div>
                        <p class="hw-field-hint mt-2">Los totales definitivos los calcula el sistema al emitir la factura.</p>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">receipt_long</span>
                            Emitir factura
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/facturacion/facturas.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
