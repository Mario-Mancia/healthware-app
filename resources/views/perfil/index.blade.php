@extends('layouts.app_layout')

@section('titulo', 'Mi perfil')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/perfil.css') }}">
@endpush

@section('contenido')
    @php
        $nombreCompleto = trim(($usuario->nombres ?? '') . ' ' . ($usuario->apellidos ?? ''));
        $inicialesPerfil = mb_strtoupper(
            mb_substr($usuario->nombres ?? 'U', 0, 1) .
            mb_substr($usuario->apellidos ?? '', 0, 1)
        );
        $fotoPerfil = $usuario->fotoUrl();
        $doctorPerfil = $usuario->doctor;
    @endphp

    <div class="hw-page">

        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">manage_accounts</span>
                </div>
                <div>
                    <h1 class="hw-title">Mi perfil</h1>
                    <p class="hw-subtitle">Tus datos personales, tu foto y la seguridad de tu cuenta.</p>
                </div>
            </div>
        </header>

        <div class="row g-4">

            {{-- IDENTIDAD Y FOTO --}}
            <div class="col-lg-4">
                <section class="hw-surface perfil-tarjeta">
                    <div class="perfil-foto" data-perfil-foto>
                        @if($fotoPerfil)
                            <img src="{{ $fotoPerfil }}" alt="Foto de {{ $nombreCompleto }}">
                        @else
                            {{ $inicialesPerfil }}
                        @endif
                    </div>

                    <h2 class="perfil-nombre">{{ $nombreCompleto }}</h2>
                    <p class="perfil-correo">{{ $usuario->correo }}</p>

                    <span class="hw-status hw-status-info">
                        <span class="hw-status-dot"></span>{{ $usuario->rol->rol ?? 'Usuario' }}
                    </span>

                    <form method="POST" action="{{ route('perfil.foto') }}" enctype="multipart/form-data"
                          class="perfil-formulario-foto" novalidate>
                        @csrf
                        <input type="file" name="foto" id="perfil_foto" class="form-control"
                               accept="image/jpeg,image/png,image/webp" data-perfil-foto-input required>
                        <div class="hw-field-hint">JPG, PNG o WEBP. Máximo 2 MB.</div>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">upload</span>
                            Guardar foto
                        </button>
                    </form>

                    @if($fotoPerfil)
                        <form method="POST" action="{{ route('perfil.foto.eliminar') }}" class="w-100">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-text w-100">Eliminar foto</button>
                        </form>
                    @endif
                </section>
            </div>

            {{-- DATOS PERSONALES --}}
            <div class="col-lg-8">
                <section class="hw-surface perfil-seccion">
                    <div class="hw-surface-header">
                        <div>
                            <h2 class="hw-surface-title">Datos personales</h2>
                            <p class="hw-surface-description">El correo y el rol los administra el administrador del sistema.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('perfil.actualizar') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="nombres" id="perfil_nombres" class="form-control"
                                           placeholder="Nombres" maxlength="100"
                                           value="{{ old('nombres', $usuario->nombres) }}" required>
                                    <label for="perfil_nombres">Nombres</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="apellidos" id="perfil_apellidos" class="form-control"
                                           placeholder="Apellidos" maxlength="100"
                                           value="{{ old('apellidos', $usuario->apellidos) }}" required>
                                    <label for="perfil_apellidos">Apellidos</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="telefono" id="perfil_telefono" class="form-control"
                                           placeholder="Teléfono" maxlength="25"
                                           value="{{ old('telefono', $usuario->telefono) }}">
                                    <label for="perfil_telefono">Teléfono</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" id="perfil_correo" class="form-control" placeholder="Correo"
                                           value="{{ $usuario->correo }}" disabled>
                                    <label for="perfil_correo">Correo electrónico</label>
                                </div>
                            </div>
                        </div>

                        <div class="perfil-acciones">
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-outlined">save</span>
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </section>

                {{-- DATOS PROFESIONALES (solo médicos) --}}
                @if($doctorPerfil)
                    <section class="hw-surface perfil-seccion">
                        <div class="hw-surface-header">
                            <div>
                                <h2 class="hw-surface-title">Datos profesionales</h2>
                                <p class="hw-surface-description">Los modifica el administrador desde Doctores.</p>
                            </div>
                        </div>

                        <dl class="hw-detail-grid">
                            <div class="hw-detail-item"><dt>Especialidad</dt><dd>{{ $doctorPerfil->especialidad->especialidad ?? '—' }}</dd></div>
                            <div class="hw-detail-item"><dt>Número de licencia</dt><dd>{{ $doctorPerfil->numero_licencia }}</dd></div>
                            <div class="hw-detail-item"><dt>DUI</dt><dd>{{ $doctorPerfil->dui }}</dd></div>
                            <div class="hw-detail-item is-wide"><dt>Descripción profesional</dt><dd>{{ $doctorPerfil->descripcion_profesional ?: '—' }}</dd></div>
                        </dl>
                    </section>
                @endif

                {{-- SEGURIDAD --}}
                <section class="hw-surface perfil-seccion">
                    <div class="hw-surface-header">
                        <div>
                            <h2 class="hw-surface-title">Seguridad</h2>
                            <p class="hw-surface-description">Cambia tu contraseña periódicamente y no la compartas.</p>
                        </div>
                    </div>

                    <a href="{{ route('contrasenha.cambiar') }}" class="btn btn-tonal">
                        <span class="material-symbols-outlined">lock_reset</span>
                        Cambiar contraseña
                    </a>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/perfil/perfil.js') }}"></script>
@endpush
