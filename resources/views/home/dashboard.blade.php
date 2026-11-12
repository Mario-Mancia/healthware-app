@extends('layouts.app_layout')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('contenido')
    @php
        /*
            DATO TEMPORALES PARA DASHBOARD
        */

        $nombreUsuario = auth()->user()?->nombres
            ?? auth()->user()?->name
            ?? 'Administrador';

        $fechaActual = now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y');

        $kpis = [
            [
                'titulo' => 'Citas de hoy',
                'valor' => '28',
                'variacion' => '+12.5%',
                'detalle' => 'frente al día anterior',
                'icono' => 'calendar_month',
                'clase' => 'primary',
                'tendencia' => 'up',
            ],
            [
                'titulo' => 'Pacientes registrados',
                'valor' => '1,284',
                'variacion' => '+8.2%',
                'detalle' => 'frente al mes anterior',
                'icono' => 'groups',
                'clase' => 'secondary',
                'tendencia' => 'up',
            ],
            [
                'titulo' => 'Doctores activos',
                'valor' => '18',
                'variacion' => '15 disponibles',
                'detalle' => 'de 18 doctores',
                'icono' => 'stethoscope',
                'clase' => 'tertiary',
                'tendencia' => 'neutral',
            ],
            [
                'titulo' => 'Ingresos del mes',
                'valor' => '$8,420.00',
                'variacion' => '+6.4%',
                'detalle' => 'frente al mes anterior',
                'icono' => 'payments',
                'clase' => 'success',
                'tendencia' => 'up',
            ],
            [
                'titulo' => 'Saldo pendiente',
                'valor' => '$1,260.00',
                'variacion' => '12 facturas',
                'detalle' => 'requieren seguimiento',
                'icono' => 'account_balance_wallet',
                'clase' => 'warning',
                'tendencia' => 'neutral',
            ],
            [
                'titulo' => 'Inasistencia',
                'valor' => '8.4%',
                'variacion' => '-2.1%',
                'detalle' => 'frente al mes anterior',
                'icono' => 'event_busy',
                'clase' => 'error',
                'tendencia' => 'down',
            ],
        ];

        $usuariosEstadoDemo = [
            ['nombre_estado' => 'ACTIVO', 'total_usuarios' => 18],
            ['nombre_estado' => 'INACTIVO', 'total_usuarios' => 4],
            ['nombre_estado' => 'ELIMINADO', 'total_usuarios' => 2],
        ];

        $usuariosEstadoGrafico = isset($usuariosPorEstado)
            && count($usuariosPorEstado) > 0
                ? $usuariosPorEstado
                : collect($usuariosEstadoDemo);

        $actividadReciente = [
            [
                'hora' => '08:15',
                'titulo' => 'Nueva cita registrada',
                'descripcion' => 'Se programó una cita para el área de cardiología.',
                'icono' => 'event_available',
                'clase' => 'primary',
            ],
            [
                'hora' => '09:20',
                'titulo' => 'Nuevo paciente registrado',
                'descripcion' => 'Se creó un expediente de paciente.',
                'icono' => 'person_add',
                'clase' => 'success',
            ],
            [
                'hora' => '10:05',
                'titulo' => 'Pago recibido',
                'descripcion' => 'Se registró un pago por servicios médicos.',
                'icono' => 'payments',
                'clase' => 'tertiary',
            ],
            [
                'hora' => '11:30',
                'titulo' => 'Agenda actualizada',
                'descripcion' => 'Se modificó la disponibilidad de un doctor.',
                'icono' => 'edit_calendar',
                'clase' => 'warning',
            ],
        ];

        $proximasCitas = [
            [
                'hora' => '09:00 AM',
                'paciente' => 'María Rodríguez',
                'doctor' => 'Dr. Carlos Méndez',
                'especialidad' => 'Cardiología',
                'estado' => 'Confirmada',
                'clase' => 'confirmed',
            ],
            [
                'hora' => '09:30 AM',
                'paciente' => 'José Hernández',
                'doctor' => 'Dra. Ana López',
                'especialidad' => 'Medicina general',
                'estado' => 'Pendiente',
                'clase' => 'pending',
            ],
            [
                'hora' => '10:00 AM',
                'paciente' => 'Lucía Martínez',
                'doctor' => 'Dr. Pedro García',
                'especialidad' => 'Dermatología',
                'estado' => 'Confirmada',
                'clase' => 'confirmed',
            ],
            [
                'hora' => '10:30 AM',
                'paciente' => 'Miguel Flores',
                'doctor' => 'Dra. Elena Torres',
                'especialidad' => 'Pediatría',
                'estado' => 'En espera',
                'clase' => 'waiting',
            ],
            [
                'hora' => '11:00 AM',
                'paciente' => 'Sofía Castillo',
                'doctor' => 'Dr. Carlos Méndez',
                'especialidad' => 'Cardiología',
                'estado' => 'Pendiente',
                'clase' => 'pending',
            ],
        ];

        $serviciosPopulares = [
            ['nombre' => 'Consulta general', 'cantidad' => 142, 'porcentaje' => 85],
            ['nombre' => 'Consulta especializada', 'cantidad' => 96, 'porcentaje' => 62],
            ['nombre' => 'Exámenes de laboratorio', 'cantidad' => 74, 'porcentaje' => 48],
            ['nombre' => 'Control médico', 'cantidad' => 51, 'porcentaje' => 33],
        ];
    @endphp

    <div class="hw-dashboard">

        {{-- ENCABEZADO --}}
        <section class="hw-welcome">
            <div class="hw-welcome-content">
            <span class="hw-eyebrow">
                <span class="material-symbols-outlined">space_dashboard</span>
                PANEL DE ADMINISTRACIÓN
            </span>

                <h1>¡Bienvenido, {{ $nombreUsuario }}!</h1>

                <p>
                    Aquí tienes el resumen general de la actividad de HealthWare.
                    Administra y supervisa la clínica desde un solo lugar.
                </p>

                <div class="hw-welcome-date">
                    <span class="material-symbols-outlined">calendar_today</span>
                    {{ ucfirst($fechaActual) }}
                </div>
            </div>

            <div class="hw-welcome-illustration" aria-hidden="true">
                <span class="material-symbols-outlined">medical_services</span>
                <span class="hw-illustration-dot dot-one"></span>
                <span class="hw-illustration-dot dot-two"></span>
                <span class="hw-illustration-dot dot-three"></span>
            </div>
        </section>

        {{-- INDICADORES PRINCIPALES --}}
        <section class="hw-section">
            <div class="hw-section-heading">
                <div>
                    <span class="hw-section-overline">VISTA GENERAL</span>
                    <h2>Indicadores principales</h2>
                    <p>Resumen del rendimiento de la clínica.</p>
                </div>

                <span class="hw-demo-label">
                <span class="material-symbols-outlined">science</span>
                Datos de demostración
            </span>
            </div>

            <div class="hw-kpi-grid">
                @foreach ($kpis as $kpi)
                    <article class="hw-kpi-card">
                        <div class="hw-kpi-top">
                        <span class="hw-kpi-icon hw-icon-{{ $kpi['clase'] }}">
                            <span class="material-symbols-outlined">
                                {{ $kpi['icono'] }}
                            </span>
                        </span>

                            <button
                                type="button"
                                class="hw-more-button"
                                aria-label="Información de {{ $kpi['titulo'] }}"
                                title="{{ $kpi['titulo'] }}"
                            >
                                <span class="material-symbols-outlined">more_horiz</span>
                            </button>
                        </div>

                        <p class="hw-kpi-title">{{ $kpi['titulo'] }}</p>
                        <div class="hw-kpi-value">{{ $kpi['valor'] }}</div>

                        <div class="hw-kpi-footer">
                        <span class="hw-kpi-trend trend-{{ $kpi['tendencia'] }}">
                            @if ($kpi['tendencia'] === 'up')
                                <span class="material-symbols-outlined">trending_up</span>
                            @elseif ($kpi['tendencia'] === 'down')
                                <span class="material-symbols-outlined">trending_down</span>
                            @else
                                <span class="material-symbols-outlined">info</span>
                            @endif

                            {{ $kpi['variacion'] }}
                        </span>

                            <span class="hw-kpi-detail">{{ $kpi['detalle'] }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- GRÁFICOS DE TENDENCIAS --}}
        <section class="hw-section">
            <div class="hw-section-heading">
                <div>
                    <span class="hw-section-overline">ANÁLISIS</span>
                    <h2>Actividad y tendencias</h2>
                    <p>Visualiza la evolución de la clínica en el tiempo.</p>
                </div>
            </div>

            <div class="hw-chart-grid hw-chart-grid-main">

                {{-- GRÁFICO LINEAL --}}
                <article class="hw-panel hw-panel-large">
                    <div class="hw-panel-header">
                        <div>
                            <h3>Evolución de citas</h3>
                            <p>Comparativa de citas programadas y atendidas.</p>
                        </div>

                        <span class="hw-panel-icon">
                        <span class="material-symbols-outlined">monitoring</span>
                    </span>
                    </div>

                    <div class="hw-chart-container hw-chart-timeline">
                        <canvas id="graficoCitas"></canvas>
                    </div>
                </article>

                {{-- GRÁFICO DE BARRAS --}}
                <article class="hw-panel">
                    <div class="hw-panel-header">
                        <div>
                            <h3>Ingresos mensuales</h3>
                            <p>Recaudación de los últimos seis meses.</p>
                        </div>

                        <span class="hw-panel-icon">
                        <span class="material-symbols-outlined">bar_chart</span>
                    </span>
                    </div>

                    <div class="hw-chart-container hw-chart-bars">
                        <canvas id="graficoIngresos"></canvas>
                    </div>

                    <div class="hw-chart-footnote">
                        <span class="hw-status-dot"></span>
                        Importes ilustrativos en USD
                    </div>
                </article>
            </div>
        </section>

        {{-- DISTRIBUCIÓN Y SERVICIOS --}}
        <section class="hw-section">
            <div class="hw-section-heading">
                <div>
                    <span class="hw-section-overline">DISTRIBUCIÓN</span>
                    <h2>Composición de la actividad</h2>
                    <p>Consulta la distribución de usuarios y los servicios destacados.</p>
                </div>
            </div>

            <div class="hw-chart-grid hw-chart-grid-secondary">

                {{-- PIE CHART DE USUARIOS --}}
                <article class="hw-panel">
                    <div class="hw-panel-header">
                        <div>
                            <h3>Usuarios por estado</h3>
                            <p>Distribución de las cuentas registradas.</p>
                        </div>

                        <span class="hw-panel-icon">
                        <span class="material-symbols-outlined">donut_large</span>
                    </span>
                    </div>

                    <div class="hw-chart-container hw-chart-pie">
                        <canvas id="graficoUsuariosEstado"></canvas>
                    </div>

                    <div class="hw-chart-footnote">
                        Los estados se obtienen de la vista de MySQL cuando está disponible.
                    </div>
                </article>

                {{-- SERVICIOS POPULARES --}}
                <article class="hw-panel">
                    <div class="hw-panel-header">
                        <div>
                            <h3>Servicios destacados</h3>
                            <p>Servicios con mayor actividad en el período.</p>
                        </div>

                        <span class="hw-panel-icon">
                        <span class="material-symbols-outlined">medical_information</span>
                    </span>
                    </div>

                    <div class="hw-services-list">
                        @foreach ($serviciosPopulares as $servicio)
                            <div class="hw-service-item">
                                <div class="hw-service-info">
                                    <span>{{ $servicio['nombre'] }}</span>
                                    <strong>{{ $servicio['cantidad'] }}</strong>
                                </div>

                                <div
                                    class="hw-progress-track"
                                    role="progressbar"
                                    aria-label="{{ $servicio['nombre'] }}"
                                    aria-valuenow="{{ $servicio['porcentaje'] }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    <div
                                        class="hw-progress-fill"
                                        style="width: {{ $servicio['porcentaje'] }}%"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="hw-panel-note">
                        <span class="material-symbols-outlined">info</span>
                        Datos simulados para visualizar la distribución.
                    </div>
                </article>
            </div>
        </section>

        {{-- ACTIVIDAD RECIENTE Y TABLA --}}
        <section class="hw-section">
            <div class="hw-section-heading">
                <div>
                    <span class="hw-section-overline">SEGUIMIENTO</span>
                    <h2>Actividad reciente y agenda</h2>
                    <p>Un resumen de los movimientos y las próximas atenciones.</p>
                </div>
            </div>

            <div class="hw-bottom-grid">

                {{-- TIMELINE --}}
                <article class="hw-panel">
                    <div class="hw-panel-header">
                        <div>
                            <h3>Actividad reciente</h3>
                            <p>Movimientos de ejemplo del sistema.</p>
                        </div>

                        <span class="hw-panel-icon">
                        <span class="material-symbols-outlined">history</span>
                    </span>
                    </div>

                    <div class="hw-timeline">
                        @foreach ($actividadReciente as $actividad)
                            <div class="hw-timeline-item">
                                <div class="hw-timeline-marker hw-icon-{{ $actividad['clase'] }}">
                                <span class="material-symbols-outlined">
                                    {{ $actividad['icono'] }}
                                </span>
                                </div>

                                <div class="hw-timeline-content">
                                    <div class="hw-timeline-title">
                                        {{ $actividad['titulo'] }}
                                    </div>

                                    <p>{{ $actividad['descripcion'] }}</p>
                                    <span class="hw-timeline-time">{{ $actividad['hora'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>

                {{-- TABLA DE CITAS --}}
                <article class="hw-panel hw-appointments-panel">
                    <div class="hw-panel-header">
                        <div>
                            <h3>Próximas citas</h3>
                            <p>Vista previa de la agenda diaria.</p>
                        </div>

                        <span class="hw-panel-icon">
                        <span class="material-symbols-outlined">event_note</span>
                    </span>
                    </div>

                    <div class="hw-table-wrapper">
                        <table class="hw-table">
                            <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Doctor / Especialidad</th>
                                <th>Estado</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach ($proximasCitas as $cita)
                                <tr>
                                    <td class="hw-table-time">{{ $cita['hora'] }}</td>

                                    <td>
                                        <span class="hw-patient-name">
                                            {{ $cita['paciente'] }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="hw-doctor-name">{{ $cita['doctor'] }}</span>
                                        <span class="hw-specialty">{{ $cita['especialidad'] }}</span>
                                    </td>

                                    <td>
                                        <span class="hw-appointment-status status-{{ $cita['clase'] }}">
                                            {{ $cita['estado'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="hw-table-footer">
                        <span class="material-symbols-outlined">info</span>
                        Los pacientes y horarios mostrados son ficticios.
                    </div>
                </article>
            </div>
        </section>

        {{-- PIE DE DASHBOARD --}}
        <footer class="hw-dashboard-footer">
            <span class="material-symbols-outlined">verified_user</span>
            <span>HealthWare · Panel administrativo</span>
            <span class="hw-footer-separator">•</span>
            <span>Información de demostración</span>
        </footer>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Datos temporales para los gráficos.
            const datosCitas = {
                etiquetas: ['May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct'],
                programadas: [120, 145, 138, 170, 186, 205],
                atendidas: [105, 129, 121, 151, 169, 188]
            };

            const datosIngresos = {
                etiquetas: ['May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct'],
                valores: [5800, 6250, 6100, 7200, 7890, 8420]
            };

            // Paleta coherente con Material Design 3 de HealthWare.
            const colores = {
                primary: '#6750A4',
                primaryLight: '#D0BCFF',
                secondary: '#625B71',
                tertiary: '#7D5260',
                success: '#2E7D5B',
                warning: '#B7791F',
                error: '#B3261E',
                grid: 'rgba(127, 116, 142, 0.15)',
                text: '#79747E'
            };

            const estiloEjes = {
                color: colores.text,
                grid: {
                    color: colores.grid,
                    drawBorder: false
                },
                border: {
                    display: false
                },
                ticks: {
                    padding: 10
                }
            };

            // 1. Evolución de citas: gráfico de líneas.
            new Chart(document.getElementById('graficoCitas'), {
                type: 'line',
                data: {
                    labels: datosCitas.etiquetas,
                    datasets: [
                        {
                            label: 'Programadas',
                            data: datosCitas.programadas,
                            borderColor: colores.primary,
                            backgroundColor: 'rgba(103, 80, 164, 0.10)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 3,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Atendidas',
                            data: datosCitas.atendidas,
                            borderColor: colores.success,
                            backgroundColor: 'rgba(46, 125, 91, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 3,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20,
                                color: colores.text
                            }
                        }
                    },
                    scales: {
                        x: {
                            ...estiloEjes,
                            grid: { display: false }
                        },
                        y: {
                            ...estiloEjes,
                            beginAtZero: true,
                            ticks: {
                                ...estiloEjes.ticks,
                                precision: 0
                            }
                        }
                    }
                }
            });

            // 2. Ingresos mensuales: gráfico de barras.
            new Chart(document.getElementById('graficoIngresos'), {
                type: 'bar',
                data: {
                    labels: datosIngresos.etiquetas,
                    datasets: [{
                        label: 'Ingresos',
                        data: datosIngresos.valores,
                        backgroundColor: [
                            'rgba(103, 80, 164, 0.55)',
                            'rgba(103, 80, 164, 0.62)',
                            'rgba(103, 80, 164, 0.68)',
                            'rgba(103, 80, 164, 0.75)',
                            'rgba(103, 80, 164, 0.85)',
                            colores.primary
                        ],
                        borderRadius: 7,
                        borderSkipped: false,
                        maxBarThickness: 34
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (contexto) {
                                    return '$' + Number(contexto.raw).toLocaleString(
                                        'en-US',
                                        { minimumFractionDigits: 2 }
                                    );
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ...estiloEjes,
                            grid: { display: false }
                        },
                        y: {
                            ...estiloEjes,
                            beginAtZero: true,
                            ticks: {
                                ...estiloEjes.ticks,
                                callback: function (valor) {
                                    return '$' + Number(valor).toLocaleString('en-US');
                                }
                            }
                        }
                    }
                }
            });

            // 3. Usuarios por estado: datos provenientes del controlador
            // cuando estén disponibles; la vista aporta valores de respaldo.
            const usuariosPorEstado = @json($usuariosEstadoGrafico);

            const etiquetasUsuarios = usuariosPorEstado.map(
                usuario => usuario.nombre_estado
            );

            const cantidadesUsuarios = usuariosPorEstado.map(
                usuario => Number(usuario.total_usuarios)
            );

            new Chart(document.getElementById('graficoUsuariosEstado'), {
                type: 'pie',
                data: {
                    labels: etiquetasUsuarios,
                    datasets: [{
                        label: 'Usuarios',
                        data: cantidadesUsuarios,
                        backgroundColor: [
                            '#2E7D5B',
                            '#E5A43B',
                            '#9E9E9E',
                            '#6750A4',
                            '#4F7CAC'
                        ],
                        borderColor: getComputedStyle(document.documentElement)
                            .getPropertyValue('--md-sys-color-surface').trim() || '#FEF7FF',
                        borderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 18,
                                color: colores.text
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (contexto) {
                                    const total = contexto.dataset.data.reduce(
                                        (suma, valor) => suma + valor, 0
                                    );

                                    const cantidad = Number(contexto.raw);
                                    const porcentaje = total > 0
                                        ? (cantidad / total * 100).toFixed(1)
                                        : '0.0';

                                    return `${contexto.label}: ${cantidad} (${porcentaje}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
