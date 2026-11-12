<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Doctor;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cita>
 */
class CitaFactory extends Factory
{
    protected $model = Cita::class;

    /**
     * Define el estado por defecto del modelo.
     *
     * No valida el horario del doctor ni los cruces con otras citas: para
     * datos coherentes indique paciente, doctor, fecha y horas al crearla.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $horaInicio = $inicio->format('H:00');
        $horaFin = (clone $inicio)->modify('+30 minutes')->format('H:i');

        return [
            'id_paciente' => Paciente::query()->inRandomOrder()->value('id') ?? Paciente::factory(),
            'id_doctor' => Doctor::query()->inRandomOrder()->value('id'),
            'fecha_cita' => $inicio->format('Y-m-d'),
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'estado' => 'programada',
            'motivo' => $this->faker->randomElement([
                'Consulta de control',
                'Consulta general',
                'Seguimiento de tratamiento',
                'Revisión de resultados',
            ]),
            'notas' => null,
        ];
    }

    /**
     * Cita ya atendida.
     */
    public function completada(): static
    {
        return $this->state(fn (array $atributos) => [
            'estado' => 'completada',
        ]);
    }
}
