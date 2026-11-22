<?php

namespace App\Providers;

use App\Events\FacturaAnulada;
use App\Events\HorarioDoctorModificado;
use App\Events\PagoRechazado;
use App\Models\Factura;
use App\Models\HorarioDoctor;
use App\Models\Pago;
use App\Models\Usuario;
use App\Services\AvisosDeCuenta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Engancha las notificaciones a los cambios de los modelos que no pasan por un
 * servicio con eventos propios: cuenta de usuario, horarios, facturas y pagos.
 * Así se avisa desde cualquier punto del sistema que haga el cambio, sin tocar
 * los controladores. Un fallo al avisar se reporta pero no interrumpe el cambio.
 */
class NotificacionesClinicasServiceProvider extends ServiceProvider
{
    /** Campos de la cuenta de los que se avisa. */
    private const CAMPOS_DE_CUENTA = [
        'contrasenha',
        'correo',
        'telefono',
        'id_rol',
        'id_estado',
        'debe_cambiar_contrasenha',
    ];

    public function boot(): void
    {
        $this->cuentasDeUsuario();
        $this->horariosDeDoctores();
        $this->facturasYPagos();
    }

    private function cuentasDeUsuario(): void
    {
        Usuario::updated(function (Usuario $usuario) {
            $cambios = array_intersect_key($usuario->getChanges(), array_flip(self::CAMPOS_DE_CUENTA));

            if ($cambios === []) {
                return;
            }

            // Los valores anteriores se guardan ahora: al confirmarse la
            // transacción el modelo ya tendrá los nuevos como "originales".
            $originales = [];

            foreach (array_keys($cambios) as $campo) {
                $originales[$campo] = $usuario->getOriginal($campo);
            }

            $id = (int) $usuario->getKey();
            $actor = Auth::id() !== null ? (int) Auth::id() : null;

            DB::afterCommit(function () use ($id, $cambios, $originales, $actor) {
                try {
                    app(AvisosDeCuenta::class)->alActualizar($id, $cambios, $originales, $actor);
                } catch (Throwable $error) {
                    report($error);
                }
            });
        });
    }

    private function horariosDeDoctores(): void
    {
        HorarioDoctor::updated(function (HorarioDoctor $horario) {
            // Los horarios llegan como «08:00» y se guardan como «08:00:00»:
            // se comparan normalizados para ignorar ediciones sin cambios reales.
            $recortar = fn ($valor) => substr((string) $valor, 0, 5);

            $cambioDia = (int) $horario->getOriginal('dia_semana') !== (int) $horario->dia_semana;
            $cambioHora = $recortar($horario->getOriginal('hora_inicio')) !== $recortar($horario->hora_inicio)
                || $recortar($horario->getOriginal('hora_fin')) !== $recortar($horario->hora_fin);

            if (!$cambioDia && !$cambioHora) {
                return;
            }

            $this->despachar(fn () => event(new HorarioDoctorModificado(
                (int) $horario->id_doctor,
                [(int) $horario->getOriginal('dia_semana'), (int) $horario->dia_semana],
                'actualizado',
                Auth::id() !== null ? (int) Auth::id() : null
            )));
        });

        HorarioDoctor::deleted(function (HorarioDoctor $horario) {
            $this->despachar(fn () => event(new HorarioDoctorModificado(
                (int) $horario->id_doctor,
                [(int) $horario->dia_semana],
                'eliminado',
                Auth::id() !== null ? (int) Auth::id() : null
            )));
        });
    }

    private function facturasYPagos(): void
    {
        Factura::updated(function (Factura $factura) {
            if ($factura->wasChanged('estado') && $factura->estado === Factura::ESTADO_CANCELADA) {
                $this->despachar(fn () => event(new FacturaAnulada(
                    (int) $factura->getKey(),
                    Auth::id() !== null ? (int) Auth::id() : null
                )));
            }
        });

        Pago::created(function (Pago $pago) {
            if ($pago->estado === Pago::ESTADO_RECHAZADO) {
                $this->despachar(fn () => event(new PagoRechazado(
                    (int) $pago->getKey(),
                    Auth::id() !== null ? (int) Auth::id() : null
                )));
            }
        });
    }

    /**
     * Avisar nunca debe romper la operación que lo originó.
     */
    private function despachar(callable $accion): void
    {
        try {
            $accion();
        } catch (Throwable $error) {
            report($error);
        }
    }
}
