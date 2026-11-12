<?php

namespace Database\Factories;

use App\Models\Orden;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Orden>
 */
class OrdenFactory extends Factory
{
    protected $model = Orden::class;

    /**
     * Define el estado por defecto del modelo.
     *
     * Crea solo la cabecera de la orden. Para una orden con sus servicios y
     * numeración correlativa use Orden::insertar().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_paciente' => Paciente::query()->inRandomOrder()->value('id') ?? Paciente::factory(),
            'numero_orden' => 'ORD-' . now()->format('Y') . '-' . $this->faker->unique()->numerify('9#####'),
            'fecha_orden' => now(),
            'estado' => Orden::ESTADO_PENDIENTE,
            'notas' => null,
        ];
    }
}
