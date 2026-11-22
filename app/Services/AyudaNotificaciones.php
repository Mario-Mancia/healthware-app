<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * Piezas comunes de las notificaciones clínicas y de cuenta: quién las
 * recibe y cómo se redactan.
 *
 * Reglas que se cumplen aquí para no saturar el sistema ni exponer datos:
 *  - Solo reciben avisos las cuentas activas.
 *  - Los textos mencionan al paciente solo por su nombre (nunca DUI,
 *    diagnósticos ni resultados).
 *  - Un fallo al notificar se reporta, pero nunca interrumpe la operación
 *    que lo originó (una cita no debe fallar porque no se pudo avisar).
 */
class AyudaNotificaciones
{
    /** Nombre de cada día según Carbon::dayOfWeekIso (1 = lunes). */
    public const DIAS = [
        1 => 'lunes',
        2 => 'martes',
        3 => 'miércoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sábado',
        7 => 'domingo',
    ];

    // Destinatarios

    /**
     * Ids de los usuarios activos de un rol.
     *
     * @return Collection<int, int>
     */
    public static function idsPorRol(string $rol, ?int $excluir = null): Collection
    {
        $normalizado = mb_strtolower(trim($rol));

        return Usuario::query()
            ->whereHas('rol', fn ($consulta) => $consulta->whereRaw('LOWER(TRIM(rol)) = ?', [$normalizado]))
            ->whereHas('estado', fn ($consulta) => $consulta->whereRaw('LOWER(TRIM(estado)) = ?', ['activo']))
            ->when($excluir, fn ($consulta) => $consulta->where('id', '<>', $excluir))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    /** @return Collection<int, int> */
    public static function administradores(?int $excluir = null): Collection
    {
        return static::idsPorRol(Usuario::ROL_ADMINISTRADOR, $excluir);
    }

    /** @return Collection<int, int> */
    public static function recepcion(?int $excluir = null): Collection
    {
        return static::idsPorRol(Usuario::ROL_RECEPCIONISTA, $excluir);
    }

    /** @return Collection<int, int> */
    public static function contabilidad(?int $excluir = null): Collection
    {
        return static::idsPorRol(Usuario::ROL_CONTADOR, $excluir);
    }

    /**
     * Cuenta de usuario de un médico (solo si esa cuenta está activa).
     */
    public static function idUsuarioDeDoctor(?int $idDoctor): ?int
    {
        if (!$idDoctor) {
            return null;
        }

        $idUsuario = Doctor::query()->whereKey($idDoctor)->value('usuario_id');

        if (!$idUsuario) {
            return null;
        }

        $activo = Usuario::query()
            ->whereKey($idUsuario)
            ->whereHas('estado', fn ($consulta) => $consulta->whereRaw('LOWER(TRIM(estado)) = ?', ['activo']))
            ->exists();

        return $activo ? (int) $idUsuario : null;
    }

    // Envío

    /**
     * Envía el aviso a cada usuario (sin repetir destinatarios). Con $unaVez,
     * si el usuario ya tiene un aviso igual sin leer, se actualiza en lugar de
     * crear otro. Devuelve a cuántos se envió.
     *
     * @param  iterable<int>  $idsUsuarios
     */
    public static function enviar(
        iterable $idsUsuarios,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $enlace = null,
        bool $unaVez = false
    ): int {
        $enviadas = 0;

        $destinatarios = collect($idsUsuarios)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        foreach ($destinatarios as $idUsuario) {
            try {
                if ($unaVez) {
                    Notificacion::notificarUnaVez($idUsuario, $tipo, $titulo, $mensaje, $enlace);
                } else {
                    Notificacion::notificar($idUsuario, $tipo, $titulo, $mensaje, $enlace);
                }

                $enviadas++;
            } catch (Throwable $error) {
                report($error);
            }
        }

        return $enviadas;
    }

    // Redacción

    /**
     * Solo nombre y apellidos: nunca el DUI ni otros datos del paciente.
     */
    public static function nombrePaciente(?int $idPaciente): string
    {
        if (!$idPaciente) {
            return 'un paciente';
        }

        $paciente = Paciente::withTrashed()->find($idPaciente, ['id', 'nombres', 'apellidos']);

        $nombre = $paciente ? trim($paciente->nombres . ' ' . $paciente->apellidos) : '';

        return $nombre !== '' ? $nombre : 'un paciente';
    }

    public static function nombreUsuario(?int $idUsuario): string
    {
        if (!$idUsuario) {
            return 'Un usuario';
        }

        $usuario = Usuario::query()->find($idUsuario, ['id', 'nombres', 'apellidos']);

        $nombre = $usuario ? trim($usuario->nombres . ' ' . $usuario->apellidos) : '';

        return $nombre !== '' ? $nombre : 'Un usuario';
    }

    public static function nombreDoctor(?int $idDoctor): string
    {
        if (!$idDoctor) {
            return 'el médico';
        }

        $doctor = Doctor::query()->with('usuario:id,nombres,apellidos')->find($idDoctor);

        $nombre = $doctor?->usuario
            ? trim($doctor->usuario->nombres . ' ' . $doctor->usuario->apellidos)
            : '';

        return $nombre !== '' ? 'Dr(a). ' . $nombre : 'el médico';
    }

    /**
     * La prioridad va en el título, para que se note en la lista sin abrir el
     * aviso: lo normal no lleva marca.
     */
    public static function prefijoPrioridad(?string $prioridad): string
    {
        return match ($prioridad) {
            'urgente' => '[URGENTE] ',
            'prioritaria' => '[Prioritaria] ',
            default => '',
        };
    }

    public static function fecha(mixed $valor): string
    {
        return Carbon::parse($valor)->format('d/m/Y');
    }

    public static function hora(mixed $valor): string
    {
        return substr((string) $valor, 0, 5);
    }

    public static function recortar(?string $texto, int $limite = 120): string
    {
        return Str::limit(trim((string) $texto), $limite, '…');
    }

    public static function plural(int $cantidad, string $singular, string $plural): string
    {
        return $cantidad === 1 ? $singular : $plural;
    }
}
