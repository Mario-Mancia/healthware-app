<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Estado;
use App\Models\Notificacion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Avisos sobre la propia cuenta del usuario.
 *
 *  - Bienvenida: la primera vez que el usuario cambia la contraseña que le dio
 *    el administrador.
 *  - Cambios hechos por el propio usuario: solo los sensibles (contraseña y
 *    correo). Cambiar el teléfono o la foto no genera avisos.
 *  - Cambios hechos por un administrador (contraseña restablecida, correo,
 *    teléfono, rol o estado): un solo aviso con todo lo que cambió.
 *  - Bloqueo temporal por intentos fallidos de inicio de sesión: un solo aviso,
 *    y solo si el correo pertenece a una cuenta activa de la plataforma. Los
 *    correos desconocidos no generan nada (de ellos se encarga el limitador).
 */
class AvisosDeCuenta
{
    /** Debe coincidir con el límite de intentos de LoginController. */
    public const LIMITE_INTENTOS = 5;

    /** Descripción que la bitácora guarda al cambiar la contraseña (PerfilController). */
    private const BITACORA_CAMBIO_CLAVE = 'El usuario cambió su contraseña.';

    /**
     * @param  array<string, mixed>  $cambios     Campos de la cuenta que cambiaron (valor nuevo)
     * @param  array<string, mixed>  $originales  Valor que tenían antes
     * @param  int|null              $idActor     Quien hizo el cambio (null: consola o proceso automático)
     */
    public function alActualizar(int $idUsuario, array $cambios, array $originales, ?int $idActor): void
    {
        // Sin sesión (seeders, consola) no hay a quién atribuir el cambio.
        if ($idActor === null) {
            return;
        }

        $usuario = Usuario::query()->find($idUsuario);

        if (!$usuario) {
            return;
        }

        if ($idActor === $idUsuario) {
            $this->avisarCambiosPropios($usuario, $cambios, $originales);

            return;
        }

        $this->avisarCambiosDeAdministracion($usuario, $cambios, $idActor);
    }

    /**
     * Llamado por LoginController después de contabilizar un intento fallido.
     * Avisa justo cuando se alcanza el límite (una vez por bloqueo).
     */
    public function alFallarInicioDeSesion(string $correo, string $claveLimite): void
    {
        try {
            if (RateLimiter::attempts($claveLimite) !== self::LIMITE_INTENTOS) {
                return;
            }

            $usuario = Usuario::query()
                ->where('correo', $correo)
                ->whereHas('estado', fn ($consulta) => $consulta->whereRaw('LOWER(TRIM(estado)) = ?', ['activo']))
                ->first();

            if (!$usuario) {
                return;
            }

            Notificacion::notificarUnaVez(
                (int) $usuario->getKey(),
                'cuenta_bloqueada',
                'Inicio de sesión bloqueado temporalmente',
                'Se bloquearon por unos minutos los inicios de sesión con su correo desde una misma conexión '
                    . 'tras ' . self::LIMITE_INTENTOS . ' intentos fallidos. Si no fue usted, cambie su '
                    . 'contraseña y avise al administrador.',
                route('contrasenha.cambiar', [], false)
            );
        } catch (Throwable $error) {
            report($error);
        }
    }

    // Cambios propios

    private function avisarCambiosPropios(Usuario $usuario, array $cambios, array $originales): void
    {
        $enlace = route('perfil', [], false);
        $cambioClave = array_key_exists('contrasenha', $cambios);
        $primeraClave = $cambioClave && $this->esPrimerCambioDeClave($usuario, $cambios, $originales);

        if ($primeraClave) {
            AyudaNotificaciones::enviar(
                [$usuario->getKey()],
                'bienvenida',
                'Bienvenido a HealthWare',
                'Hola, ' . trim($usuario->nombres) . '. Su contraseña quedó configurada y ya puede usar el sistema. '
                    . 'Revise sus datos en el perfil y no comparta su contraseña con nadie.',
                $enlace
            );
        }

        $lineas = [];

        if ($cambioClave && !$primeraClave) {
            $lineas[] = 'su contraseña se cambió';
        }

        if (array_key_exists('correo', $cambios)) {
            $lineas[] = 'su correo de acceso ahora es ' . $cambios['correo'];
        }

        if ($lineas !== []) {
            AyudaNotificaciones::enviar(
                [$usuario->getKey()],
                'cuenta',
                'Seguridad de su cuenta',
                'Se registró un cambio en su cuenta: ' . implode('; ', $lineas)
                    . '. Si no fue usted, avise al administrador de inmediato.',
                $enlace
            );
        }
    }

    /**
     * Es la primera vez cuando el cambio levantó la obligación de cambiar la
     * contraseña y la bitácora no tiene un cambio de contraseña anterior de
     * esa cuenta (así un restablecimiento posterior no repite la bienvenida).
     */
    private function esPrimerCambioDeClave(Usuario $usuario, array $cambios, array $originales): bool
    {
        $eraObligatorio = (bool) ($originales['debe_cambiar_contrasenha'] ?? false);
        $dejaDeSerlo = array_key_exists('debe_cambiar_contrasenha', $cambios)
            && !$cambios['debe_cambiar_contrasenha'];

        if (!$eraObligatorio || !$dejaDeSerlo) {
            return false;
        }

        return !Bitacora::query()
            ->where('modulo', 'USUARIOS')
            ->where('registro_id', $usuario->getKey())
            ->where('descripcion', self::BITACORA_CAMBIO_CLAVE)
            ->exists();
    }

    // Cambios hechos por un administrador

    private function avisarCambiosDeAdministracion(Usuario $usuario, array $cambios, int $idActor): void
    {
        $partes = [];

        if (array_key_exists('contrasenha', $cambios)) {
            $partes[] = 'restableció su contraseña (deberá cambiarla al entrar)';
        }

        if (array_key_exists('correo', $cambios)) {
            $partes[] = 'cambió su correo de acceso a ' . $cambios['correo'];
        }

        if (array_key_exists('telefono', $cambios)) {
            $partes[] = 'actualizó su teléfono';
        }

        if (array_key_exists('id_rol', $cambios)) {
            $rol = Rol::query()->whereKey($cambios['id_rol'])->value('rol');

            if ($rol) {
                $partes[] = 'cambió su rol a ' . $rol;
            }
        }

        if (array_key_exists('id_estado', $cambios) && (int) $cambios['id_estado'] !== Usuario::ESTADO_ELIMINADO) {
            $estado = Estado::query()->whereKey($cambios['id_estado'])->value('estado');

            if ($estado) {
                $partes[] = 'cambió el estado de su cuenta a ' . mb_strtolower($estado);
            }
        }

        if ($partes === []) {
            return;
        }

        AyudaNotificaciones::enviar(
            [$usuario->getKey()],
            'cuenta',
            'Su cuenta fue actualizada',
            AyudaNotificaciones::nombreUsuario($idActor) . ' ' . implode('; ', $partes) . '.',
            route('perfil', [], false)
        );
    }
}
