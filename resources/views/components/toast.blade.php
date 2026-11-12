{{-- Componente reutilizable de notificaciones HealthWare --}}

@php
    $notificaciones = [];

    foreach (['success', 'error', 'warning', 'info'] as $tipo) {
        if (session()->has($tipo)) {
            $notificaciones[] = [
                'tipo' => $tipo,
                'mensaje' => session($tipo)
            ];
        }
    }

    if ($errors->any()) {
        foreach ($errors->all() as $error) {
            $notificaciones[] = [
                'tipo' => 'error',
                'mensaje' => $error
            ];
        }
    }

    $configuracionesToast = [
        'success' => [
            'titulo' => 'Operación exitosa',
            'icono' => 'check_circle'
        ],
        'error' => [
            'titulo' => 'Ha ocurrido un error',
            'icono' => 'error'
        ],
        'warning' => [
            'titulo' => 'Atención',
            'icono' => 'warning'
        ],
        'info' => [
            'titulo' => 'Información',
            'icono' => 'info'
        ]
    ];
@endphp

<div
    id="healthware-toast-container"
    class="toast-container position-fixed bottom-0 end-0 p-3 hw-toast-container"
    aria-live="polite"
    aria-atomic="false"
>
    @foreach ($notificaciones as $notificacion)
        @php
            $tipo = $notificacion['tipo'];
            $configuracion = $configuracionesToast[$tipo];
        @endphp

        <div
            class="toast hw-toast hw-toast--{{ $tipo }}"
            role="{{ $tipo === 'error' ? 'alert' : 'status' }}"
            aria-live="{{ $tipo === 'error' ? 'assertive' : 'polite' }}"
            aria-atomic="true"
            data-toast-duration="4500"
        >
            <div class="hw-toast__content">

                <div class="hw-toast__icon-container">
                    <span class="material-symbols-outlined hw-toast__icon">
                        {{ $configuracion['icono'] }}
                    </span>
                </div>

                <div class="hw-toast__message">
                    <div class="hw-toast__title">
                        {{ $configuracion['titulo'] }}
                    </div>

                    <div class="hw-toast__description">
                        {{ $notificacion['mensaje'] }}
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-close hw-toast__close"
                    data-bs-dismiss="toast"
                    aria-label="Cerrar notificación"
                ></button>

            </div>

            <div class="hw-toast__progress">
                <div class="hw-toast__progress-bar"></div>
            </div>
        </div>
    @endforeach
</div>
