@extends('layouts.app_layout')

@section('titulo', 'Detalle de factura')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $fmt = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
        $estadoFactura = (string) $factura->estado;
        $claseEstado = ['pagada' => 'success', 'pendiente' => 'warning', 'pago_parcial' => 'info', 'cancelada' => 'error'][$estadoFactura] ?? 'neutral';
        $nombrePaciente = trim(($factura->paciente?->nombres ?? '') . ' ' . ($factura->paciente?->apellidos ?? ''));
        $pagosAprobados = $factura->pagos->where('estado', 'aprobado');
        $pagado = (float) $pagosAprobados->sum('monto');
        $saldo = $estadoFactura === 'cancelada' ? 0.0 : max(0.0, (float) $factura->total - $pagado);
        $puedeCobrar = $rolActual === 'contador' && $saldo > 0 && in_array($estadoFactura, ['pendiente', 'pago_parcial']);
        $puedeAnular = $rolActual === 'administrador' && $estadoFactura === 'pendiente' && $pagosAprobados->isEmpty();
        $tasaPorcentaje = rtrim(rtrim(number_format(\App\Models\Factura::TASA_IMPUESTO * 100, 2), '0'), '.');
        $clasePago = ['aprobado' => 'success', 'rechazado' => 'error'];
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">receipt_long</span>
                </div>
                <div>
                    <h1 class="hw-title">Factura {{ $factura->numero_factura }}</h1>
                    <p class="hw-subtitle">Documento, conceptos y cobros de la factura.</p>
                </div>
            </div>
            <div class="hw-document-actions">
                <a href="{{ route('facturas') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Volver al listado
                </a>
                <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                    <span class="material-symbols-outlined">print</span>
                    Imprimir o guardar PDF
                </button>
                @if($puedeCobrar)
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPago">
                        <span class="material-symbols-outlined">payments</span>
                        Registrar pago
                    </button>
                @endif
                @if($puedeAnular)
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalAnular">
                        <span class="material-symbols-outlined">block</span>
                        Anular factura
                    </button>
                @endif
            </div>
        </header>

        <article class="hw-document">
            <div class="hw-document-head">
                <div class="hw-document-brand">
                    <span class="material-symbols-outlined">health_and_safety</span>
                    <div>
                        <strong>HealthWare</strong>
                        <span>Sistema de Gestión Clínica</span>
                    </div>
                </div>
                <div class="hw-document-meta">
                    <h2>Factura {{ $factura->numero_factura }}</h2>
                    <p>Emitida el {{ $fmt($factura->fecha_emision) }}</p>
                    <span class="hw-status hw-status-{{ $claseEstado }}">
                        <span class="hw-status-dot"></span>{{ ucfirst(str_replace('_', ' ', $estadoFactura)) }}
                    </span>
                </div>
            </div>

            <div class="hw-document-parties">
                <div class="hw-document-party">
                    <h3>Emisor</h3>
                    <p><strong>HealthWare</strong></p>
                    <p>Emitida por {{ trim(($factura->emisor?->nombres ?? '') . ' ' . ($factura->emisor?->apellidos ?? '')) ?: '—' }}</p>
                    @if($factura->orden)<p>Orden {{ $factura->orden->numero_orden }}</p>@endif
                </div>
                <div class="hw-document-party">
                    <h3>Paciente</h3>
                    <p><strong>{{ $nombrePaciente ?: '—' }}</strong></p>
                    @if($factura->paciente?->numero_identificacion)<p>DUI: {{ $factura->paciente->numero_identificacion }}</p>@endif
                    @if($factura->paciente?->telefono)<p>Tel.: {{ $factura->paciente->telefono }}</p>@endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table hw-data-table align-middle">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">Precio unitario</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($factura->detalles as $linea)
                            <tr>
                                <td>{{ $linea->descripcion ?: ($linea->servicio?->servicio ?? '—') }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float) $linea->cantidad, 2), '0'), '.') }}</td>
                                <td class="text-end">${{ number_format((float) $linea->precio_unitario, 2) }}</td>
                                <td class="text-end">${{ number_format((float) $linea->subtotal, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center hw-muted">Sin conceptos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="hw-document-totals">
                <div><span>Subtotal</span><span>${{ number_format((float) $factura->subtotal, 2) }}</span></div>
                <div><span>Descuento</span><span>-${{ number_format((float) $factura->descuento, 2) }}</span></div>
                <div><span>Impuesto ({{ $tasaPorcentaje }} %)</span><span>${{ number_format((float) $factura->impuesto, 2) }}</span></div>
                <div class="is-total"><span>Total</span><span>${{ number_format((float) $factura->total, 2) }}</span></div>
                <div><span>Pagado</span><span>${{ number_format($pagado, 2) }}</span></div>
                <div class="fac-saldo {{ $saldo > 0 ? 'is-pendiente' : 'is-pagado' }}"><span>Saldo pendiente</span><span>${{ number_format($saldo, 2) }}</span></div>
            </div>

            @if($factura->notas)
                <p class="mt-3 mb-0"><strong>Notas:</strong> {{ $factura->notas }}</p>
            @endif

            @if($estadoFactura === 'cancelada')
                <div class="fac-anulada" role="alert">
                    <strong>Factura anulada.</strong> {{ $factura->motivo_anulacion }}
                </div>
            @endif
        </article>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Pagos</h2>
                    <p class="hw-surface-description">Intentos de cobro de esta factura, incluidos los rechazados.</p>
                </div>
            </div>
            @if($factura->pagos->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">credit_card_off</span>
                    <h3>Sin pagos registrados</h3>
                    <p>Cuando el contador cobre la factura aparecerá aquí.</p>
                </div>
            @else
                <div class="table-responsive hw-table-container">
                    <table class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Referencia</th>
                                <th>Fecha</th>
                                <th>Método</th>
                                <th class="text-end">Monto</th>
                                <th>Detalle</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($factura->pagos->sortByDesc('fecha_pago') as $pago)
                                <tr>
                                    <td class="hw-mono">{{ $pago->referencia }}</td>
                                    <td>{{ $fmt($pago->fecha_pago, 'd/m/Y H:i') }}</td>
                                    <td>{{ ucfirst($pago->metodo_pago) }}</td>
                                    <td class="text-end hw-mono">${{ number_format((float) $pago->monto, 2) }}</td>
                                    <td>
                                        @if($pago->metodo_pago === 'tarjeta')
                                            Tarjeta terminada en {{ $pago->ultimos_digitos }}{{ $pago->motivo_rechazo ? ' · ' . $pago->motivo_rechazo : '' }}
                                        @else
                                            Recibido ${{ number_format((float) $pago->monto_recibido, 2) }} · cambio ${{ number_format((float) $pago->cambio, 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $clasePago[$pago->estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($pago->estado) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    @if($puedeCobrar)
    <div class="modal fade hw-modal" id="modalPago" tabindex="-1" aria-labelledby="tmodalPago" aria-hidden="true" data-saldo="{{ number_format($saldo, 2, '.', '') }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">payments</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalPago">Registrar pago</h2>
                            <p class="hw-modal-subtitle">Cobro en efectivo o con tarjeta de crédito o débito simulada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('pagos.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalPago">
                        <input type="hidden" name="id_factura" value="{{ $factura->id }}">
                        <div class="form-floating mb-3">
                            <input type="text" name="monto" id="pago_monto" class="form-control" value="{{ number_format($saldo, 2, '.', '') }}" readonly>
                            <label for="pago_monto">Monto a cobrar (USD)</label>
                        </div>

                        <div class="fac-metodos">
                            <label class="fac-metodo">
                                <input type="radio" name="metodo_pago" value="efectivo" @checked(old('metodo_pago', 'efectivo') === 'efectivo')>
                                <span class="material-symbols-outlined">payments</span> Efectivo
                            </label>
                            <label class="fac-metodo">
                                <input type="radio" name="metodo_pago" value="tarjeta" @checked(old('metodo_pago') === 'tarjeta')>
                                <span class="material-symbols-outlined">credit_card</span> Tarjeta
                            </label>
                        </div>

                        <div data-metodo="efectivo">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" name="monto_recibido" id="pago_recibido" class="form-control" placeholder="Monto recibido"
                                               min="0" step="0.01" value="{{ old('monto_recibido') }}">
                                        <label for="pago_recibido">Monto recibido (USD)</label>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="fac-saldo is-pagado w-100"><span>Cambio</span><span data-pago-cambio>$0.00</span></div>
                                </div>
                            </div>
                        </div>

                        <div data-metodo="tarjeta" hidden>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" name="numero_tarjeta" id="pago_numero" class="form-control" placeholder="Número de tarjeta"
                                               inputmode="numeric" autocomplete="off" maxlength="23" value="{{ old('numero_tarjeta') }}">
                                        <label for="pago_numero">Número de tarjeta</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="number" name="mes_vencimiento" id="pago_mes" class="form-control" placeholder="Mes" min="1" max="12" value="{{ old('mes_vencimiento') }}">
                                        <label for="pago_mes">Mes</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="number" name="anio_vencimiento" id="pago_anio" class="form-control" placeholder="Año" min="2000" max="2100" value="{{ old('anio_vencimiento') }}">
                                        <label for="pago_anio">Año</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="password" name="cvv" id="pago_cvv" class="form-control" placeholder="CVV" inputmode="numeric" maxlength="4" autocomplete="off">
                                        <label for="pago_cvv">CVV</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <select name="tipo_tarjeta" id="pago_tipo" class="form-select">
                                            <option value="credito" @selected(old('tipo_tarjeta') === 'credito')>Crédito</option>
                                            <option value="debito" @selected(old('tipo_tarjeta', 'debito') === 'debito')>Débito</option>
                                        </select>
                                        <label for="pago_tipo">Tipo</label>
                                    </div>
                                </div>
                            </div>
                            <p class="hw-field-hint mt-2">Cobro simulado con tarjetas de prueba. No se guarda el número de la tarjeta: solo los últimos 4 dígitos.</p>

                            @if($tarjetasPrueba->isNotEmpty())
                                <details class="fac-tarjetas-prueba">
                                    <summary>Tarjetas de prueba (solo en desarrollo)</summary>
                                    <table class="table hw-subtable">
                                        <thead><tr><th>Titular</th><th>Número</th><th>Tipo</th><th>Vence</th><th>CVV</th><th>Saldo</th><th>Estado</th></tr></thead>
                                        <tbody>
                                            @foreach($tarjetasPrueba as $tarjeta)
                                                <tr data-tarjeta-prueba style="cursor: pointer;"
                                                    data-numero="{{ $tarjeta->numero }}" data-mes="{{ $tarjeta->mes_vencimiento }}"
                                                    data-anio="{{ $tarjeta->anio_vencimiento }}" data-cvv="{{ $tarjeta->cvv }}" data-tipo="{{ $tarjeta->tipo }}">
                                                    <td>{{ $tarjeta->titular }}</td>
                                                    <td class="hw-mono">{{ $tarjeta->numero }}</td>
                                                    <td>{{ $tarjeta->tipo }}</td>
                                                    <td>{{ str_pad($tarjeta->mes_vencimiento, 2, '0', STR_PAD_LEFT) }}/{{ $tarjeta->anio_vencimiento }}</td>
                                                    <td>{{ $tarjeta->cvv }}</td>
                                                    <td>${{ number_format((float) $tarjeta->saldo, 2) }}</td>
                                                    <td>{{ $tarjeta->estado }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <p class="mb-0 hw-muted">Toque una fila para rellenar el formulario.</p>
                                </details>
                            @endif
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">payments</span>
                            Cobrar
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif

    @if($puedeAnular)
    <div class="modal fade hw-modal" id="modalAnular" tabindex="-1" aria-labelledby="tmodalAnular" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">block</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalAnular">Anular factura</h2>
                            <p class="hw-modal-subtitle">La factura quedará cancelada y la orden volverá a estar pendiente.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('facturas.anular', ['factura' => $factura->id]) }}" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalAnular">
                        <p class="mb-3">Solo se pueden anular las facturas pendientes y sin pagos. La orden vuelve a quedar pendiente de facturar.</p>
                        <div class="form-floating">
                            <textarea name="motivo_anulacion" id="anular_motivo" class="form-control" placeholder="Motivo" maxlength="250" required>{{ old('motivo_anulacion') }}</textarea>
                            <label for="anular_motivo">Motivo de la anulación</label>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <span class="material-symbols-outlined">block</span>
                            Anular factura
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/facturacion/pagos.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
