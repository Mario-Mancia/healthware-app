<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Tarjetas ficticias que simulan el cobro con tarjeta de crédito o débito.
 * Nunca deben guardarse tarjetas reales en esta tabla.
 */
class TarjetaPrueba extends Model
{
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_BLOQUEADA = 'bloqueada';

    protected $table = 'tarjetas_prueba';

    protected $fillable = [
        'numero',
        'titular',
        'tipo',
        'marca',
        'mes_vencimiento',
        'anio_vencimiento',
        'cvv',
        'saldo',
        'estado',
    ];

    protected $hidden = [
        'cvv',
    ];

    protected $casts = [
        'mes_vencimiento' => 'integer',
        'anio_vencimiento' => 'integer',
        'saldo' => 'decimal:2',
    ];

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_tarjeta_prueba');
    }

    public static function mostrarTodos(): Collection
    {
        return static::orderBy('id')->get();
    }

    /**
     * Comprueba el dígito verificador del número de tarjeta (algoritmo de Luhn).
     */
    public static function luhnValido(string $numero): bool
    {
        $numero = preg_replace('/\D/', '', $numero);

        if ($numero === '' || strlen($numero) < 13 || strlen($numero) > 19) {
            return false;
        }

        $suma = 0;
        $alterno = false;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];

            if ($alterno) {
                $digito *= 2;

                if ($digito > 9) {
                    $digito -= 9;
                }
            }

            $suma += $digito;
            $alterno = !$alterno;
        }

        return $suma % 10 === 0;
    }

    /**
     * Simula la autorización de un cobro. Debe ejecutarse dentro de una
     * transacción porque bloquea la fila de la tarjeta.
     *
     * Claves esperadas en $datos: numero_tarjeta, mes_vencimiento,
     * anio_vencimiento, cvv y, opcionalmente, tipo_tarjeta.
     *
     * @return array{aprobado: bool, motivo: ?string, tarjeta: ?TarjetaPrueba}
     */
    public static function autorizar(array $datos, float $monto): array
    {
        $numero = preg_replace('/\D/', '', (string) ($datos['numero_tarjeta'] ?? ''));

        if (!static::luhnValido($numero)) {
            return static::rechazo('El número de tarjeta no es válido.');
        }

        $tarjeta = static::query()
            ->where('numero', $numero)
            ->lockForUpdate()
            ->first();

        if (!$tarjeta) {
            return static::rechazo('Tarjeta no reconocida.');
        }

        if ($tarjeta->estado !== self::ESTADO_ACTIVA) {
            return static::rechazo('La tarjeta está bloqueada.', $tarjeta);
        }

        $mes = (int) ($datos['mes_vencimiento'] ?? 0);
        $anio = (int) ($datos['anio_vencimiento'] ?? 0);

        if ($anio > 0 && $anio < 100) {
            $anio += 2000;
        }

        if ($mes < 1 || $mes > 12) {
            return static::rechazo('El mes de vencimiento no es válido.', $tarjeta);
        }

        if (
            $tarjeta->mes_vencimiento !== $mes ||
            $tarjeta->anio_vencimiento !== $anio
        ) {
            return static::rechazo('La fecha de vencimiento no coincide.', $tarjeta);
        }

        if (Carbon::create($anio, $mes, 1)->endOfMonth()->isPast()) {
            return static::rechazo('La tarjeta está vencida.', $tarjeta);
        }

        if (!hash_equals((string) $tarjeta->cvv, (string) ($datos['cvv'] ?? ''))) {
            return static::rechazo('El código de seguridad es incorrecto.', $tarjeta);
        }

        if (
            !empty($datos['tipo_tarjeta']) &&
            $datos['tipo_tarjeta'] !== $tarjeta->tipo
        ) {
            return static::rechazo('El tipo de tarjeta (crédito o débito) no coincide.', $tarjeta);
        }

        if ((float) $tarjeta->saldo + 0.001 < $monto) {
            $motivo = $tarjeta->tipo === 'credito'
                ? 'Límite de crédito insuficiente.'
                : 'Fondos insuficientes.';

            return static::rechazo($motivo, $tarjeta);
        }

        return [
            'aprobado' => true,
            'motivo' => null,
            'tarjeta' => $tarjeta,
        ];
    }

    /**
     * Arma la respuesta de una autorización rechazada.
     */
    protected static function rechazo(string $motivo, ?TarjetaPrueba $tarjeta = null): array
    {
        return [
            'aprobado' => false,
            'motivo' => $motivo,
            'tarjeta' => $tarjeta,
        ];
    }
}
