@extends('layouts.auth_layout')

@section('titulo', 'Iniciar Sesión | HealthWare')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('contenido')

    <div class="login-card">

        <div class="row g-0 h-100">

            {{-- INSTITUTIONAL PANEL --}}
            <aside class="col-lg-5 login-brand-panel">

                <div class="login-brand-content">

                    <div class="login-brand-icon">
                    <span class="material-symbols-outlined">
                        health_and_safety
                    </span>
                    </div>

                    <span class="login-overline">
                    HEALTHWARE
                </span>

                    <h1 class="login-brand-title">
                        Tu salud, nuestra prioridad.
                    </h1>

                    <p class="login-brand-description">
                        Plataforma integral para la gestión clínica,
                        administración de pacientes y seguimiento médico.
                    </p>

                    <div class="login-brand-divider"></div>

                    <div class="login-feature">

                    <span class="material-symbols-outlined">
                        medical_information
                    </span>

                        <div>
                            <h2>Gestión clínica</h2>
                            <p>Expedientes y registros médicos centralizados.</p>
                        </div>

                    </div>

                    <div class="login-feature">

                    <span class="material-symbols-outlined">
                        verified_user
                    </span>

                        <div>
                            <h2>Acceso seguro</h2>
                            <p>Control de acceso para personal autorizado.</p>
                        </div>

                    </div>

                </div>

                <span class="login-brand-footer">
                Sistema de Gestión Clínica
            </span>

            </aside>

            {{-- AUTHENTICATION PANEL --}}
            <section class="col-12 col-lg-7 login-form-panel">

                <div class="login-form-content">

                    <div class="login-heading">

                        <div class="login-mobile-brand">
                        <span class="material-symbols-outlined">
                            health_and_safety
                        </span>
                            <span>HealthWare</span>
                        </div>

                        <div class="login-heading-icon">
                        <span class="material-symbols-outlined">
                            account_circle
                        </span>
                        </div>

                        <h2>Bienvenido</h2>

                        <p>
                            Ingresa tus credenciales para acceder
                            a tu cuenta.
                        </p>

                    </div>

                    <form
                        action="{{ route('login') }}"
                        method="POST"
                        class="login-form"
                        novalidate>

                        @csrf

                        {{-- EMAIL --}}
                        <div class="form-floating mb-4">

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="correo"
                                placeholder="correo@institucion.com"
                                autocomplete="username"
                                required
                                autofocus>

                            <label for="email">
                                Correo electrónico
                            </label>

                        </div>

                        {{-- PASSWORD --}}
                        <div class="login-password-field mb-3">

                            <div class="form-floating">

                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="contrasenha"
                                    placeholder="Contraseña"
                                    autocomplete="current-password"
                                    required>

                                <label for="password">
                                    Contraseña
                                </label>

                            </div>

                            <button
                                type="button"
                                class="login-password-toggle"
                                id="togglePassword"
                                aria-label="Mostrar contraseña"
                                aria-pressed="false">

                            <span class="material-symbols-outlined"
                                  id="passwordIcon">
                                visibility_off
                            </span>

                            </button>

                        </div>

                        {{-- EXTRA OPTIONS --}}
                        <div class="login-extra-options">

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="rememberMe">

                                <label
                                    class="form-check-label"
                                    for="rememberMe">

                                    Recordarme

                                </label>

                            </div>

                            <a href="#" class="login-forgot-link">
                                ¿Olvidaste tu contraseña?
                            </a>

                        </div>

                        {{-- SUBMIT --}}
                        <button
                            type="submit"
                            class="btn btn-primary login-submit">

                            <span>Iniciar sesión</span>

                            <span class="material-symbols-outlined">
                            arrow_forward
                        </span>

                        </button>

                    </form>

                    <div class="login-security-note">

                    <span class="material-symbols-outlined">
                        lock
                    </span>

                        <span>
                        Acceso exclusivo para personal autorizado.
                    </span>

                    </div>

                </div>

            </section>

        </div>

    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/login-scripts.js') }}"></script>
@endpush
