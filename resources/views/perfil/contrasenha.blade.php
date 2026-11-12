@extends('layouts.app_layout')

@section('titulo', 'Cambiar contraseña')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/perfil.css') }}">
@endpush

@section('contenido')
    <div class="hw-page">

        <header class="hw-header">
            <div class="hw-heading">
                <div class="hw-heading-icon">
                    <span class="material-symbols-outlined">lock_reset</span>
                </div>
                <div>
                    <h1 class="hw-title">Cambiar contraseña</h1>
                    <p class="hw-subtitle">Elige una contraseña nueva para tu cuenta.</p>
                </div>
            </div>
        </header>

        <section class="hw-surface perfil-seccion" style="max-width: 36rem;">

            @if($usuario->debe_cambiar_contrasenha)
                <div class="perfil-aviso" role="alert">
                    <span class="material-symbols-outlined">info</span>
                    <span>
                        Tu cuenta tiene una contraseña temporal. Debes cambiarla para continuar
                        usando el sistema.
                    </span>
                </div>
            @endif

            <form method="POST" action="{{ route('contrasenha.actualizar') }}" novalidate>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12">
                        <div class="perfil-campo-contrasenha">
                            <div class="form-floating">
                                <input type="password" name="contrasenha_actual" id="contrasenha_actual"
                                       class="form-control" placeholder="Contraseña actual"
                                       autocomplete="current-password" required>
                                <label for="contrasenha_actual">Contraseña actual</label>
                            </div>
                            <button type="button" class="btn btn-icon perfil-ver-contrasenha"
                                    data-ver-contrasenha aria-label="Mostrar u ocultar la contraseña" aria-pressed="false">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="perfil-campo-contrasenha">
                            <div class="form-floating">
                                <input type="password" name="contrasenha" id="contrasenha" class="form-control"
                                       placeholder="Contraseña nueva" autocomplete="new-password"
                                       minlength="8" maxlength="250" required>
                                <label for="contrasenha">Contraseña nueva</label>
                            </div>
                            <button type="button" class="btn btn-icon perfil-ver-contrasenha"
                                    data-ver-contrasenha aria-label="Mostrar u ocultar la contraseña" aria-pressed="false">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                        <ul class="perfil-requisitos">
                            <li>Al menos 8 caracteres.</li>
                            <li>Mayúsculas, minúsculas y al menos un número.</li>
                            <li>Distinta de tu contraseña actual.</li>
                        </ul>
                    </div>

                    <div class="col-12">
                        <div class="perfil-campo-contrasenha">
                            <div class="form-floating">
                                <input type="password" name="contrasenha_confirmation" id="contrasenha_confirmation"
                                       class="form-control" placeholder="Confirmar contraseña nueva"
                                       autocomplete="new-password" required>
                                <label for="contrasenha_confirmation">Confirmar contraseña nueva</label>
                            </div>
                            <button type="button" class="btn btn-icon perfil-ver-contrasenha"
                                    data-ver-contrasenha aria-label="Mostrar u ocultar la contraseña" aria-pressed="false">
                                <span class="material-symbols-outlined">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="perfil-acciones">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined">save</span>
                        Guardar contraseña
                    </button>
                    @unless($usuario->debe_cambiar_contrasenha)
                        <a href="{{ route('perfil') }}" class="btn btn-tonal">Cancelar</a>
                    @endunless
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/perfil/perfil.js') }}"></script>
@endpush
