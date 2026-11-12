{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Recetas')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/clinico.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['medico']);
        $puedeEditar = false;
        $puedeEliminar = false;
        $lista = $recetas ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">prescriptions</span>
                </div>
                <div>
                    <h1 class="hw-title">Recetas médicas</h1>
                    <p class="hw-subtitle">Consulta de recetas emitidas y sus medicamentos.</p>
                </div>
            </div>
            @if($puedeCrear)
                <button type="button" class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal" data-bs-target="#modalNuevaReceta">
                    <span class="material-symbols-outlined">add</span>
                    Nueva receta
                </button>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Recetas emitidas</h2>
                    <p class="hw-surface-description">Consulta cada receta y el detalle de sus medicamentos.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">prescriptions</span>
                    <h3>Aún no hay registros</h3>
                    <p>Cuando existan datos aparecerán aquí.</p>
                </div>
            @else
            <div class="hw-toolbar">
                <div class="hw-search">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" class="form-control" data-hw-buscar placeholder="Buscar en la tabla…" aria-label="Buscar en la tabla" autocomplete="off">
                    <button type="button" class="btn btn-icon hw-search-clear" data-hw-buscar-limpiar aria-label="Limpiar búsqueda">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <select class="form-select" data-filtro-columna="3" aria-label="Filtrar por doctor">
                    <option value="">Doctor: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-recetas" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Notas</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr>
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>{{ $fmtFecha($item->fecha_prescripcion, 'd/m/Y H:i') }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">person</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ ($item->registroMedico?->paciente?->nombres ?? '') . ' ' . ($item->registroMedico?->paciente?->apellidos ?? '') }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ('Registro #' . $item->id_registro_medico ?? ""), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td>{{ ($item->registroMedico?->doctor?->usuario?->nombres ?? '') . ' ' . ($item->registroMedico?->doctor?->usuario?->apellidos ?? '') ?? "—" }}</td>
                            <td class="hw-muted" title="{{ $item->notas }}">{{ \Illuminate\Support\Str::limit((string) ($item->notas ?? ""), 70) ?: "—" }}</td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar receta" aria-label="Consultar receta">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
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


    @if($puedeCrear)
    <div class="modal fade hw-modal" id="modalNuevaReceta" tabindex="-1" aria-labelledby="tmodalNuevaReceta" aria-hidden="true" data-abrir-con-dui>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">prescriptions</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tmodalNuevaReceta">Nueva receta</h2>
                            <p class="hw-modal-subtitle">Indique el paciente por su DUI y agregue los medicamentos.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <form id="formNuevaReceta" method="POST" action="{{ route('recetas.insertar') }}" novalidate>
                    @csrf
                    <div class="modal-body hw-modal-body">
                        <input type="hidden" name="_modal" value="modalNuevaReceta">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @include('components.campo_dui', ['id' => 'receta_dui_paciente'])
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="id_registro_medico" id="receta_id_registro_medico" class="form-select"
                                            data-dui-registros data-valor-inicial="{{ old('id_registro_medico') }}" required>
                                        <option value="">Seleccione el registro médico</option>
                                    </select>
                                    <label for="receta_id_registro_medico">Registro médico</label>
                                </div>
                                <div class="hw-field-hint">Se cargan los registros del paciente que usted atendió.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea name="notas" id="receta_notas" class="form-control" placeholder="Notas" maxlength="250">{{ old('notas') }}</textarea>
                                    <label for="receta_notas">Notas de la receta (opcional)</label>
                                </div>
                            </div>
                        </div>

                        <h3 class="hw-form-section-heading mt-4">Medicamentos</h3>
                        @php $lineasReceta = old('detalles', []); @endphp
                        <div data-hw-lineas data-lineas-minimo="1" data-lineas-siguiente="{{ count($lineasReceta) }}">
                            <div class="hw-lineas-lista" data-lineas-lista>
                                @foreach($lineasReceta as $i => $linea)
                                    <div class="hw-linea hw-linea-receta" data-linea>
                                        <select name="detalles[{{ $i }}][id_medicamento]" class="form-select" required aria-label="Medicamento">
                                            <option value="">Medicamento</option>
                                            @foreach(($medicamentos ?? collect()) as $medicamento)
                                                <option value="{{ $medicamento->id }}" @selected((string) ($linea['id_medicamento'] ?? '') === (string) $medicamento->id)>
                                                    {{ $medicamento->nombre }}{{ $medicamento->concentracion ? ' ' . $medicamento->concentracion : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="detalles[{{ $i }}][dosis]" class="form-control" placeholder="Dosis" maxlength="100" value="{{ $linea['dosis'] ?? '' }}" required aria-label="Dosis">
                                        <input type="text" name="detalles[{{ $i }}][frecuencia]" class="form-control" placeholder="Frecuencia" maxlength="100" value="{{ $linea['frecuencia'] ?? '' }}" required aria-label="Frecuencia">
                                        <input type="text" name="detalles[{{ $i }}][duracion]" class="form-control" placeholder="Duración" maxlength="100" value="{{ $linea['duracion'] ?? '' }}" aria-label="Duración">
                                        <input type="text" name="detalles[{{ $i }}][via_administracion]" class="form-control" placeholder="Vía" maxlength="50" value="{{ $linea['via_administracion'] ?? '' }}" aria-label="Vía de administración">
                                        <button type="button" class="btn btn-icon" data-lineas-quitar aria-label="Quitar medicamento">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                        <input type="text" name="detalles[{{ $i }}][instrucciones]" class="form-control hw-linea-instrucciones" placeholder="Indicaciones (opcional)" value="{{ $linea['instrucciones'] ?? '' }}" aria-label="Indicaciones">
                                    </div>
                                @endforeach
                            </div>
                            <template data-lineas-plantilla>
                                <div class="hw-linea hw-linea-receta" data-linea>
                                    <select name="detalles[__I__][id_medicamento]" class="form-select" required aria-label="Medicamento">
                                        <option value="">Medicamento</option>
                                        @foreach(($medicamentos ?? collect()) as $medicamento)
                                            <option value="{{ $medicamento->id }}">{{ $medicamento->nombre }}{{ $medicamento->concentracion ? ' ' . $medicamento->concentracion : '' }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="detalles[__I__][dosis]" class="form-control" placeholder="Dosis" maxlength="100" required aria-label="Dosis">
                                    <input type="text" name="detalles[__I__][frecuencia]" class="form-control" placeholder="Frecuencia" maxlength="100" required aria-label="Frecuencia">
                                    <input type="text" name="detalles[__I__][duracion]" class="form-control" placeholder="Duración" maxlength="100" aria-label="Duración">
                                    <input type="text" name="detalles[__I__][via_administracion]" class="form-control" placeholder="Vía" maxlength="50" aria-label="Vía de administración">
                                    <button type="button" class="btn btn-icon" data-lineas-quitar aria-label="Quitar medicamento">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                    <input type="text" name="detalles[__I__][instrucciones]" class="form-control hw-linea-instrucciones" placeholder="Indicaciones (opcional)" aria-label="Indicaciones">
                                </div>
                            </template>
                            <button type="button" class="btn btn-tonal mt-3" data-lineas-agregar>
                                <span class="material-symbols-outlined">add</span>
                                Agregar medicamento
                            </button>
                        </div>
                    </div>
                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar receta
                        </button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: CONSULTA --}}
    <div class="modal fade hw-modal" id="modalVer" tabindex="-1" aria-labelledby="tituloVer" aria-hidden="true"
         data-buscar-url="{{ route('recetas.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de receta</h2>
                            <p class="hw-modal-subtitle">Información completa del registro.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                <div class="modal-body hw-modal-body">
                    <dl class="hw-detail-grid">
                        <div class="hw-detail-item"><dt>Paciente</dt><dd data-ver="registro_medico.paciente.nombres+registro_medico.paciente.apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>Doctor responsable</dt><dd data-ver="registro_medico.doctor.usuario.nombres+registro_medico.doctor.usuario.apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>Fecha de prescripción</dt><dd data-ver="fecha_prescripcion" data-formato="fechahora">—</dd></div>
                        <div class="hw-detail-item"><dt>Registro médico</dt><dd data-ver="id_registro_medico">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Notas</dt><dd data-ver="notas">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Medicamentos</dt><dd data-ver="detalles" data-formato="lista" data-columnas="Medicamento:medicamento.nombre;Dosis:dosis;Frecuencia:frecuencia;Duración:duracion;Vía:via_administracion;Indicaciones:instrucciones">—</dd></div>
                    </dl>
                </div>

                <div class="hw-modal-footer">
                    <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ESPECIFICACIÓN ORIGINAL (conservada por generar-frontend.sh) ===== --}}
    {{--
        VISTA: Consulta y gestión general de recetas médicas.

        OBJETIVO:
        Proporcionar una vista para consultar recetas registradas y
        acceder a sus respectivos detalles.

        CONTENIDO:
        1. ENCABEZADO:
           - Título "Recetas médicas".
           - Botón para emitir una receta, según permisos.

        2. FILTROS:
           - Paciente.
           - Doctor.
           - Fecha de emisión.
           - Estado de la receta, si está contemplado.

        3. TABLA:
           - Identificador de receta.
           - Paciente.
           - Doctor responsable.
           - Fecha de emisión.
           - Cantidad de medicamentos.
           - Acciones.

        4. FORMULARIO DE RECETA:
           - Seleccionar paciente.
           - Asociar registro médico, cuando corresponda.
           - Seleccionar doctor responsable.
           - Fecha de emisión.
           - Indicaciones generales.

        5. DETALLE DE MEDICAMENTOS:
           - Medicamento.
           - Dosis.
           - Frecuencia.
           - Duración.
           - Cantidad prescrita.
           - Indicaciones particulares.

        6. ACCIONES:
           - Consultar receta.
           - Agregar medicamentos.
           - Consultar expediente del paciente.
           - Preparar una versión imprimible de la receta.

        CONSIDERACIONES:
        - Las recetas_items representan los medicamentos de cada receta.
        - La creación de la receta y sus detalles debe mantener integridad
          mediante una transacción cuando corresponda.
        - El catálogo de medicamentos debe proporcionar los productos
          seleccionables.
        - El médico es responsable de las operaciones clínicas.
        - Esta vista puede funcionar como listado general, mientras que
          el expediente del paciente muestra su historial individual.
    --}}
@endsection

@push('scripts')
    <script src="{{ asset('js/hw/hw-dui.js') }}"></script>
    <script src="{{ asset('js/hw/hw-lineas.js') }}"></script>
    @include('components.reabrir_modal')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/recetas/recetas.js') }}"></script>
@endpush
