{{-- HEALTHWARE-GENERADO: generado por generar-frontend.sh v1.0.0 · no mezclar con otros marcadores --}}
@extends('layouts.app_layout')

@section('titulo', 'Pacientes')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('contenido')

    @php
        $rolActual = \Illuminate\Support\Str::of(auth()->user()?->rol?->rol ?? '')->ascii()->lower()->toString();
        $puedeCrear = in_array($rolActual, ['administrador','recepcionista']);
        $puedeEditar = in_array($rolActual, ['administrador','recepcionista']);
        $puedeEliminar = in_array($rolActual, ['administrador','recepcionista']);
        $puedeExpediente = in_array($rolActual, ['administrador','medico']);
        $lista = $pacientes ?? collect();
        $fmtFecha = fn($v, $f = 'd/m/Y') => $v ? \Illuminate\Support\Carbon::parse($v)->format($f) : '—';
    @endphp

    <div class="hw-page">

        {{-- ENCABEZADO --}}
        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">personal_injury</span>
                </div>
                <div>
                    <h1 class="hw-title">Pacientes</h1>
                    <p class="hw-subtitle">Registro administrativo de los pacientes de la clínica.</p>
                </div>
            </div>

            @if($puedeCrear)
                <button type="button"
                        class="btn btn-primary hw-create-button"
                        data-bs-toggle="modal"
                        data-bs-target="#modalNuevo">
                    <span class="material-symbols-outlined">person_add</span>
                    Nuevo paciente
                </button>
            @endif
            @if($rolActual === 'administrador')
                <a href="{{ route('pacientes.papelera') }}" class="btn btn-tonal">
                    <span class="material-symbols-outlined">delete_sweep</span>
                    Papelera
                </a>
            @endif
        </header>


        {{-- SUPERFICIE --}}
        <section class="hw-surface">

            <div class="hw-surface-header">
                <div>
                    <h2 class="hw-surface-title">Directorio de pacientes</h2>
                    <p class="hw-surface-description">Consulta y administra los pacientes registrados.</p>
                </div>
                <div class="hw-record-indicator">
                    <span class="material-symbols-outlined">database</span>
                    <span>{{ $lista->count() }} registros</span>
                </div>
            </div>

            @if($lista->isEmpty())
                <div class="hw-empty">
                    <span class="material-symbols-outlined">personal_injury</span>
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
                <select class="form-select" data-filtro-columna="5" aria-label="Filtrar por género">
                    <option value="">Género: todos</option>
                </select>
                <select class="form-select" data-filtro-columna="6" aria-label="Filtrar por sangre">
                    <option value="">Sangre: todos</option>
                </select>
                <span class="hw-toolbar-count" data-hw-contador></span>
                <button type="button" class="btn btn-text hw-toolbar-clear" data-filtros-limpiar>Limpiar filtros</button>
            </div>

            <div class="table-responsive hw-table-container">
                <table id="tabla-pacientes" class="table hw-data-table align-middle mb-0" data-page-length="10">
                    <thead>
                        <tr>
                            <th scope="col" class="hw-column-id">ID</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">DUI</th>
                            <th scope="col">Nacimiento</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col">Género</th>
                            <th scope="col">Sangre</th>
                            <th scope="col" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lista as $item)
                        <tr>
                            <td class="hw-id">#{{ $item->id }}</td>
                            <td>
                              <div class="hw-identity">
                                <div class="hw-avatar"><span class="material-symbols-outlined">person</span></div>
                                <div class="hw-identity-data">
                                  <span class="hw-fullname" data-filtro-valor>{{ ($item->nombres ?? '') . ' ' . ($item->apellidos ?? '') }}</span>
                                  <span class="hw-identity-caption">{{ \Illuminate\Support\Str::limit((string) ($item->correo ?? ""), 60) }}</span>
                                </div>
                              </div>
                            </td>
                            <td class="hw-mono">{{ $item->numero_identificacion ?? "—" }}</td>
                            <td>{{ $fmtFecha($item->fecha_nacimiento) }} <span class="hw-muted">· {{ $item->fecha_nacimiento ? \Illuminate\Support\Carbon::parse($item->fecha_nacimiento)->age : '—' }} años</span></td>
                            <td>{{ $item->telefono ?? "—" }}</td>
                            <td><span data-filtro-valor>{{ $item->genero }}</span></td>
                            <td><span class="hw-chip" data-filtro-valor>{{ $item->tipoSanguineo?->tipo_sanguineo ?? "N/A" }}</span></td>
                            <td>
                                <div class="hw-actions">
                                    <button type="button" class="hw-action-button"
                                            data-bs-toggle="modal" data-bs-target="#modalVer"
                                            data-id="{{ $item->id }}"
                                            title="Consultar paciente" aria-label="Consultar paciente">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    <a href="{{ route('pacientes.perfil', ['paciente' => $item->id]) }}" class="hw-action-button"
                                       title="Perfil del paciente" aria-label="Perfil del paciente">
                                        <span class="material-symbols-outlined">badge</span>
                                    </a>
                                    @if($puedeExpediente)
                                        <a href="{{ route('pacientes.expediente', ['paciente' => $item->id]) }}" class="hw-action-button"
                                           title="Expediente clínico" aria-label="Expediente clínico">
                                            <span class="material-symbols-outlined">folder_shared</span>
                                        </a>
                                    @endif
                                    @if($puedeEditar)
                                        <button type="button" class="hw-action-button"
                                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                data-id="{{ $item->id }}"
                                                title="Editar paciente" aria-label="Editar paciente">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    @endif
                                    @if($puedeEliminar)
                                        <button type="button" class="hw-action-button hw-action-delete"
                                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                data-id="{{ $item->id }}"
                                                data-nombre="{{ ($item->nombres ?? "") . " " . ($item->apellidos ?? "") }}"
                                                data-sub="{{ "DUI " . ($item->numero_identificacion ?? "—") }}"
                                                title="Eliminar paciente" aria-label="Eliminar paciente">
                                            <span class="material-symbols-outlined">delete</span>
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

    {{-- MODAL: NUEVO --}}
    @if($puedeCrear)
    <div class="modal fade hw-modal" id="modalNuevo" tabindex="-1" aria-labelledby="tituloNuevo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">person_add</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloNuevo">Registrar paciente</h2>
                            <p class="hw-modal-subtitle">Completa la información solicitada.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formNuevo" method="POST" action="{{ route('pacientes.insertar') }}" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">badge</span>
                            <div>
                              <h3>Datos personales</h3>
                              <p>Identificación del paciente.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="nombres" id="nuevo_nombres" class="form-control" placeholder="Nombres" maxlength="100" required>
                                <label for="nuevo_nombres">Nombres</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="apellidos" id="nuevo_apellidos" class="form-control" placeholder="Apellidos" maxlength="100" required>
                                <label for="nuevo_apellidos">Apellidos</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="numero_identificacion" id="nuevo_numero_identificacion" class="form-control" placeholder="00000000-0" maxlength="10" inputmode="numeric" pattern="[0-9]{8}-[0-9]" required>
                                <label for="nuevo_numero_identificacion">DUI</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="date" name="fecha_nacimiento" id="nuevo_fecha_nacimiento" class="form-control" placeholder="Fecha de nacimiento" required>
                                <label for="nuevo_fecha_nacimiento">Fecha de nacimiento</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="genero" id="nuevo_genero" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  <option value="Masculino">Masculino</option>
                                  <option value="Femenino">Femenino</option>
                                </select>
                                <label for="nuevo_genero">Género</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="id_tipo_sanguineo" id="nuevo_id_tipo_sanguineo" class="form-select">
                                  <option value="" selected>Sin especificar</option>
                                  @foreach(($tiposSanguineos ?? collect()) as $o)
                                    <option value="{{ $o->id }}">{{ $o->tipo_sanguineo }}</option>
                                  @endforeach
                                </select>
                                <label for="nuevo_id_tipo_sanguineo">Tipo sanguíneo</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">call</span>
                            <div>
                              <h3>Contacto</h3>
                              <p>Medios para comunicarse con el paciente.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="tel" name="telefono" id="nuevo_telefono" class="form-control" placeholder="Teléfono" maxlength="25">
                                <label for="nuevo_telefono">Teléfono</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="email" name="correo" id="nuevo_correo" class="form-control" placeholder="Correo electrónico" maxlength="150">
                                <label for="nuevo_correo">Correo electrónico</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="direccion" id="nuevo_direccion" class="form-control" placeholder="Dirección"></textarea>
                                <label for="nuevo_direccion">Dirección</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">emergency</span>
                            <div>
                              <h3>Contacto de emergencia</h3>
                              <p>Persona a quien avisar en caso necesario.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="nombre_contacto_emergencia" id="nuevo_nombre_contacto_emergencia" class="form-control" placeholder="Nombre del contacto" maxlength="150">
                                <label for="nuevo_nombre_contacto_emergencia">Nombre del contacto</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="tel" name="telefono_contacto_emergencia" id="nuevo_telefono_contacto_emergencia" class="form-control" placeholder="Teléfono del contacto" maxlength="25">
                                <label for="nuevo_telefono_contacto_emergencia">Teléfono del contacto</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="text" name="parentesco_contacto_emergencia" id="nuevo_parentesco_contacto_emergencia" class="form-control" placeholder="Parentesco" maxlength="50">
                                <label for="nuevo_parentesco_contacto_emergencia">Parentesco</label>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>

                    <div class="hw-modal-footer">
                        <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL: EDITAR --}}
    @if($puedeEditar)
    <div class="modal fade hw-modal" id="modalEditar" tabindex="-1" aria-labelledby="tituloEditar" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">edit</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloEditar">Editar paciente #<span id="editarTituloId">—</span></h2>
                            <p class="hw-modal-subtitle">Actualiza la información y guarda los cambios.</p>
                        </div>
                    </div>
                    <button type="button" class="hw-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form id="formEditar" method="POST"
                      data-url="{{ route('pacientes.actualizar', ['paciente' => '__ID__']) }}"
                      data-buscar-url="{{ route('pacientes.buscar', ['id' => '__ID__']) }}"
                      novalidate>
                    @csrf
                    @method('PUT')
                    <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                    <div class="modal-body hw-modal-body">
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">badge</span>
                            <div>
                              <h3>Datos personales</h3>
                              <p>Identificación del paciente.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="nombres" id="editar_nombres" class="form-control" placeholder="Nombres" maxlength="100" required>
                                <label for="editar_nombres">Nombres</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="apellidos" id="editar_apellidos" class="form-control" placeholder="Apellidos" maxlength="100" required>
                                <label for="editar_apellidos">Apellidos</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="numero_identificacion" id="editar_numero_identificacion" class="form-control" placeholder="00000000-0" maxlength="10" inputmode="numeric" pattern="[0-9]{8}-[0-9]" required>
                                <label for="editar_numero_identificacion">DUI</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="date" name="fecha_nacimiento" id="editar_fecha_nacimiento" class="form-control" placeholder="Fecha de nacimiento" required>
                                <label for="editar_fecha_nacimiento">Fecha de nacimiento</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="genero" id="editar_genero" class="form-select" required>
                                  <option value="" disabled selected></option>
                                  <option value="Masculino">Masculino</option>
                                  <option value="Femenino">Femenino</option>
                                </select>
                                <label for="editar_genero">Género</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <select name="id_tipo_sanguineo" id="editar_id_tipo_sanguineo" class="form-select">
                                  <option value="" selected>Sin especificar</option>
                                  @foreach(($tiposSanguineos ?? collect()) as $o)
                                    <option value="{{ $o->id }}">{{ $o->tipo_sanguineo }}</option>
                                  @endforeach
                                </select>
                                <label for="editar_id_tipo_sanguineo">Tipo sanguíneo</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">call</span>
                            <div>
                              <h3>Contacto</h3>
                              <p>Medios para comunicarse con el paciente.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="tel" name="telefono" id="editar_telefono" class="form-control" placeholder="Teléfono" maxlength="25">
                                <label for="editar_telefono">Teléfono</label>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="email" name="correo" id="editar_correo" class="form-control" placeholder="Correo electrónico" maxlength="150">
                                <label for="editar_correo">Correo electrónico</label>
                              </div>
                            </div>
                            <div class="col-md-12">
                              <div class="form-floating">
                                <textarea name="direccion" id="editar_direccion" class="form-control" placeholder="Dirección"></textarea>
                                <label for="editar_direccion">Dirección</label>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hw-form-section">
                          <div class="hw-form-section-heading">
                            <span class="material-symbols-outlined">emergency</span>
                            <div>
                              <h3>Contacto de emergencia</h3>
                              <p>Persona a quien avisar en caso necesario.</p>
                            </div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="form-floating">
                                <input type="text" name="nombre_contacto_emergencia" id="editar_nombre_contacto_emergencia" class="form-control" placeholder="Nombre del contacto" maxlength="150">
                                <label for="editar_nombre_contacto_emergencia">Nombre del contacto</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="tel" name="telefono_contacto_emergencia" id="editar_telefono_contacto_emergencia" class="form-control" placeholder="Teléfono del contacto" maxlength="25">
                                <label for="editar_telefono_contacto_emergencia">Teléfono del contacto</label>
                              </div>
                            </div>
                            <div class="col-md-3">
                              <div class="form-floating">
                                <input type="text" name="parentesco_contacto_emergencia" id="editar_parentesco_contacto_emergencia" class="form-control" placeholder="Parentesco" maxlength="50">
                                <label for="editar_parentesco_contacto_emergencia">Parentesco</label>
                              </div>
                            </div>
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
    @endif

    {{-- MODAL: CONSULTA --}}
    <div class="modal fade hw-modal" id="modalVer" tabindex="-1" aria-labelledby="tituloVer" aria-hidden="true"
         data-buscar-url="{{ route('pacientes.buscar', ['id' => '__ID__']) }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="hw-modal-header">
                    <div class="hw-modal-heading">
                        <div class="hw-modal-icon"><span class="material-symbols-outlined">visibility</span></div>
                        <div>
                            <h2 class="hw-modal-title" id="tituloVer">Detalle de paciente</h2>
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
                        <div class="hw-detail-item"><dt>Paciente</dt><dd data-ver="nombres+apellidos">—</dd></div>
                        <div class="hw-detail-item"><dt>DUI</dt><dd data-ver="numero_identificacion">—</dd></div>
                        <div class="hw-detail-item"><dt>Fecha de nacimiento</dt><dd data-ver="fecha_nacimiento" data-formato="fecha">—</dd></div>
                        <div class="hw-detail-item"><dt>Género</dt><dd data-ver="genero">—</dd></div>
                        <div class="hw-detail-item"><dt>Tipo sanguíneo</dt><dd data-ver="tipo_sanguineo.tipo_sanguineo">—</dd></div>
                        <div class="hw-detail-item"><dt>Teléfono</dt><dd data-ver="telefono">—</dd></div>
                        <div class="hw-detail-item"><dt>Correo</dt><dd data-ver="correo">—</dd></div>
                        <div class="hw-detail-item is-wide"><dt>Dirección</dt><dd data-ver="direccion">—</dd></div>
                        <div class="hw-detail-item"><dt>Contacto de emergencia</dt><dd data-ver="nombre_contacto_emergencia">—</dd></div>
                        <div class="hw-detail-item"><dt>Teléfono de emergencia</dt><dd data-ver="telefono_contacto_emergencia">—</dd></div>
                        <div class="hw-detail-item"><dt>Parentesco</dt><dd data-ver="parentesco_contacto_emergencia">—</dd></div>
                    </dl>
                </div>

                <div class="hw-modal-footer">
                    <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: ELIMINAR --}}
    @if($puedeEliminar)
    <div class="modal fade hw-modal hw-delete-modal" id="modalEliminar" tabindex="-1" aria-labelledby="tituloEliminar" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="hw-delete-content">
                    <div class="hw-delete-icon"><span class="material-symbols-outlined">delete</span></div>

                    <button type="button" class="hw-modal-close hw-delete-close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span class="material-symbols-outlined">close</span>
                    </button>

                    <div class="hw-delete-heading">
                        <span class="hw-delete-overline">Confirmación requerida</span>
                        <h2 id="tituloEliminar">¿Eliminar este paciente?</h2>
                        <p>Esta acción no se puede deshacer desde la interfaz.</p>
                    </div>

                    <form id="formEliminar" method="POST"
                          data-url="{{ route('pacientes.eliminar', ['paciente' => '__ID__']) }}" novalidate>
                        @csrf
                        @method('DELETE')
                        <div class="alert alert-danger d-none" role="alert" data-form-errors></div>

                        <div class="hw-delete-identity">
                            <div class="hw-delete-avatar"><span class="material-symbols-outlined">person</span></div>
                            <div class="hw-delete-data">
                                <span class="hw-delete-name" id="eliminar_nombre">—</span>
                                <span class="hw-delete-sub" id="eliminar_sub">—</span>
                                <span class="hw-delete-id">Identificador: #<span id="eliminar_id">—</span></span>
                            </div>
                        </div>

                        <div class="hw-delete-notice">
                            <span class="material-symbols-outlined">info</span>
                            <p>La eliminación respeta la lógica de estados definida. No se eliminan datos clínicos relacionados.</p>
                        </div>

                        <div class="hw-delete-actions">
                            <button type="button" class="btn btn-tonal" data-bs-dismiss="modal">Conservar</button>
                            <button type="submit" class="btn hw-delete-confirm">
                                <span class="material-symbols-outlined">delete</span>
                                Eliminar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== ESPECIFICACIÓN ORIGINAL (conservada por generar-frontend.sh) ===== --}}
    {{--
        VISTA: Gestión de pacientes.

        OBJETIVO:
        Mostrar el listado general de pacientes registrados en la clínica
        y permitir las operaciones administrativas correspondientes.

        CONTENIDO:
        1. ENCABEZADO:
           - Título "Pacientes".
           - Descripción breve.
           - Botón para registrar un nuevo paciente.

        2. FILTROS:
           - Búsqueda por nombres, apellidos o DUI.
           - Filtro por sexo.
           - Filtro por estado del paciente, si aplica.
           - Botón para limpiar filtros.

        3. TABLA:
           - Identificador del paciente.
           - Nombres y apellidos.
           - DUI.
           - Fecha de nacimiento o edad.
           - Teléfono.
           - Correo electrónico, si está disponible.
           - Acciones.

        4. MODALES:
           - Registrar paciente.
           - Editar información del paciente.
           - Confirmar eliminación lógica.
           - Consultar información resumida, si resulta conveniente.

        INTERACCIÓN:
        - Acceso al perfil del paciente.
        - Acceso a su expediente clínico.
        - Envío de formularios mediante las rutas correspondientes.
        - Validación y presentación de errores.
        - Actualización de la tabla después de operaciones exitosas.

        CONSIDERACIONES:
        - No eliminar físicamente pacientes si existen relaciones clínicas.
        - La eliminación debe respetar la lógica de estados definida.
        - Los datos clínicos no deben exponerse en esta tabla administrativa.
        - El acceso a operaciones depende del rol autenticado.
    --}}
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/paciente/pacientes.js') }}"></script>
@endpush
