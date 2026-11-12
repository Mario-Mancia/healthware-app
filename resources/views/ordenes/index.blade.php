@extends('layouts.app_layout')

@section('titulo', 'Órdenes de servicio')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
@endpush

@section('contenido')
    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $esMedico = $rolActual === 'medico';
        $puedeCrear = in_array($rolActual, ['medico', 'recepcionista']);
        $puedeCancelar = in_array($rolActual, ['administrador', 'medico', 'recepcionista']);
        $esContador = $rolActual === 'contador';
        $lista = $ordenes ?? collect();
        $claseOrden = ['pendiente' => 'warning', 'facturada' => 'success', 'cancelada' => 'error'];
        $fmtFecha = fn ($valor, $formato = 'd/m/Y') => $valor ? \Illuminate\Support\Carbon::parse($valor)->format($formato) : '—';
    @endphp

    <div class="hw-page">
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">assignment</span>
                </div>
                <div>
                    <h1 class="hw-title">Órdenes de servicio</h1>
                    <p class="hw-subtitle">Servicios prestados a los pacientes, pendientes de facturar por el contador.</p>
                </div>
            </div>
            @if($puedeCrear)
                <button type="button" class="btn btn-primary hw-create-button" data-bs-toggle="modal" data-bs-target="#modalNuevaOrden">
                    <span class="material-symbols-outlined">add</span>
                    Nueva orden
                </button>
            @endif
            @if($esContador)
                <a href="{{ route('facturas') }}" class="btn btn-primary">
                    <span class="material-symbols-outlined">receipt_long</span>
                    Ir a facturar
                </a>
            @endif
        </header>

        <div class="hw-stats">
            <div class="hw-stat">
                <span class="material-symbols-outlined">assignment</span>
                <div><strong>{{ $lista->count() }}</strong><span>Total</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">pending_actions</span>
                <div><strong>{{ $lista->where('estado', 'pendiente')->count() }}</strong><span>Pendientes</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">receipt_long</span>
                <div><strong>{{ $lista->where('estado', 'facturada')->count() }}</strong><span>Facturadas</span></div>
            </div>
            <div class="hw-stat">
                <span class="material-symbols-outlined">cancel</span>
                <div><strong>{{ $lista->where('estado', 'cancelada')->count() }}</strong><span>Canceladas</span></div>
            </div>
        </div>

        <section class="hw-surface">
            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Órdenes</h2>
                    <p class="hw-surface-description">
                        {{ in_array($rolActual, ['administrador', 'contador']) ? 'Todas las órdenes del sistema.' : 'Las órdenes que usted creó.' }}
                    </p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">assignment</span>
                    <h3>Aún no hay órdenes</h3>
                    <p>Cuando se cree una orden de servicios aparecerá aquí.</p>
                </div>
            @else
                <div class="hw-toolbar">
                    <div class="hw-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" class="form-control" data-hw-buscar placeholder="Buscar por orden, paciente o DUI…" aria-label="Buscar en la tabla" autocomplete="off">
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
                    <table id="tabla-ordenes" class="table hw-data-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Orden</th>
                                <th scope="col">Paciente</th>
                                <th scope="col">Servicios</th>
                                <th scope="col" class="text-end">Total estimado</th>
                                <th scope="col">Creada</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lista as $item)
                                <tr>
                                    <td class="hw-mono">{{ $item->numero_orden }}</td>
                                    <td>
                                        <div class="hw-identity-data">
                                            <span class="hw-fullname">{{ trim(($item->paciente?->nombres ?? '') . ' ' . ($item->paciente?->apellidos ?? '')) ?: '—' }}</span>
                                            <span class="hw-identity-caption">DUI {{ $item->paciente?->numero_identificacion ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $item->detalles->map(fn ($d) => $d->descripcion ?: ($d->servicio?->servicio ?? '—'))->implode(', ') }}</td>
                                    <td class="text-end hw-mono">${{ number_format((float) $item->detalles->sum('subtotal'), 2) }}</td>
                                    <td>
                                        {{ $fmtFecha($item->fecha_orden) }}
                                        <span class="hw-muted d-block">{{ trim(($item->creador?->nombres ?? '') . ' ' . ($item->creador?->apellidos ?? '')) ?: '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="hw-status hw-status-{{ $claseOrden[$item->estado] ?? 'neutral' }}">
                                            <span class="hw-status-dot"></span>{{ ucfirst($item->estado) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="hw-actions">
                                            <button type="button" class="hw-action-button" data-bs-toggle="modal" data-bs-target="#modalVerOrden"
                                                    data-id="{{ $item->id }}" title="Consultar orden" aria-label="Consultar orden">
                                                <span class="material-symbols-outlined">visibility</span>
                                            </button>
                                            @if($item->factura && $esContador)
                                                <a href="{{ route('facturas.ver', ['id' => $item->factura->id]) }}" class="hw-action-button"
                                                   title="Ver factura" aria-label="Ver factura">
                                                    <span class="material-symbols-outlined">receipt_long</span>
                                                </a>
                                            @endif
                                            @if($puedeCancelar && $item->estado === 'pendiente')
                                                <form method="POST" action="{{ route('ordenes.cancelar', ['orden' => $item->id]) }}" class="d-inline"
                                                      data-confirmar="¿Cancelar la orden {{ $item->numero_orden }}?">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="hw-action-button hw-action-delete" title="Cancelar orden" aria-label="Cancelar orden">
                                                        <span class="material-symbols-outlined">cancel</span>
                                                    </button>
                                                </form>
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

    {{-- MODAL: CONSULTA --}}
    <div class="modal fade hw-modal" id="modalVerOrden" tabindex="-1" aria-labelledby="tmodalVerOrden" aria-hidden="true" data-buscar-url="{{ route('ordenes.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalVerOrden">Detalle de la orden</h2>
                            <p class="hw-modal-subtitle">Servicios, estado y factura asociada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                    <div class="modal-body hw-modal-body">
                        <div data-orden-detalle>Cargando…</div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                    </div>
            </div>
        </div>
    </div>

    {{-- MODAL: NUEVA ORDEN --}}
    @if($puedeCrear)
    <div class="modal fade hw-modal" id="modalNuevaOrden" tabindex="-1" aria-labelledby="tmodalNuevaOrden" aria-hidden="true" data-abrir-con-dui>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">assignment</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaOrden">Nueva orden de servicios</h2>
                            <p class="hw-modal-subtitle">Indique el paciente por su DUI y agregue los servicios prestados.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form id="formNuevaOrden" method="POST" action="{{ route('ordenes.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaOrden">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @include('components.campo_dui', ['id' => 'orden_dui_paciente'])
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_cita" id="orden_id_cita" class="form-select" data-dui-citas data-valor-inicial="{{ old('id_cita') }}">
                                        <option value="">Sin cita asociada</option>
                                    </select>
                                    <label for="orden_id_cita">Cita (opcional)</label>
                                </div>
                            </div>
                            @if($esMedico)
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select name="id_registro_medico" id="orden_id_registro" class="form-select" data-dui-registros data-valor-inicial="{{ old('id_registro_medico') }}">
                                            <option value="">Sin registro médico</option>
                                        </select>
                                        <label for="orden_id_registro">Registro médico (opcional)</label>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-{{ $esMedico ? '6' : '12' }}">
                                <div class="form-floating">
                                    <input type="text" name="notas" id="orden_notas" class="form-control" placeholder="Notas" maxlength="255" value="{{ old('notas') }}">
                                    <label for="orden_notas">Notas (opcional)</label>
                                </div>
                            </div>
                        </div>

                        <h3 class="hw-form-section-heading mt-4">Servicios</h3>
                        @php $lineasOrden = old('detalles', []); @endphp
                        <div data-hw-lineas data-lineas-minimo="1" data-lineas-siguiente="{{ count($lineasOrden) }}">
                            <div class="hw-lineas-lista" data-lineas-lista>
                                @foreach($lineasOrden as $i => $linea)
                                    <div class="hw-linea" data-linea>
                                        <select name="detalles[{{ $i }}][id_servicio]" class="form-select" data-orden-servicio required aria-label="Servicio">
                                            <option value="">Servicio</option>
                                            @foreach($servicios as $servicio)
                                                <option value="{{ $servicio->id }}" data-precio="{{ $servicio->precio }}" @selected((string) ($linea['id_servicio'] ?? '') === (string) $servicio->id)>
                                                    {{ $servicio->servicio }} (${{ number_format((float) $servicio->precio, 2) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="detalles[{{ $i }}][cantidad]" class="form-control" data-orden-cantidad
                                               min="0.01" step="0.01" value="{{ $linea['cantidad'] ?? 1 }}" aria-label="Cantidad">
                                        <span class="hw-linea-subtotal" data-linea-subtotal>$0.00</span>
                                        <button type="button" class="btn btn-icon" data-lineas-quitar aria-label="Quitar servicio">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <template data-lineas-plantilla>
                                <div class="hw-linea" data-linea>
                                    <select name="detalles[__I__][id_servicio]" class="form-select" data-orden-servicio required aria-label="Servicio">
                                        <option value="">Servicio</option>
                                        @foreach($servicios as $servicio)
                                            <option value="{{ $servicio->id }}" data-precio="{{ $servicio->precio }}">
                                                {{ $servicio->servicio }} (${{ number_format((float) $servicio->precio, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="detalles[__I__][cantidad]" class="form-control" data-orden-cantidad
                                           min="0.01" step="0.01" value="1" aria-label="Cantidad">
                                    <span class="hw-linea-subtotal" data-linea-subtotal>$0.00</span>
                                    <button type="button" class="btn btn-icon" data-lineas-quitar aria-label="Quitar servicio">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </template>
                            <button type="button" class="btn btn-tonal mt-3" data-lineas-agregar>
                                <span class="material-symbols-outlined">add</span>
                                Agregar servicio
                            </button>
                        </div>
                        <div class="hw-orden-total">
                            <span>Total estimado (sin impuesto)</span>
                            <span data-orden-total>$0.00</span>
                        </div>
                        <p class="hw-field-hint mt-2">El precio de cada servicio sale del catálogo. El contador aplica el descuento y el impuesto al facturar.</p>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Crear orden
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-dui.js') }}"></script>
    <script src="{{ asset('js/hw/hw-lineas.js') }}"></script>
    <script src="{{ asset('js/orden/ordenes.js') }}"></script>
    @include('components.reabrir_modal')
@endpush
