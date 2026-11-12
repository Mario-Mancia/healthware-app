<?php

namespace Database\Factories;

use App\Models\Paciente;
use App\Models\TipoSanguineo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paciente>
 */
class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $genero = $this->faker->randomElement(['Masculino', 'Femenino']);

        $nombres = $genero === 'Masculino'
            ? $this->faker->firstNameMale()
            : $this->faker->firstNameFemale();

        return [
            'nombres' => $nombres,
            'apellidos' => $this->faker->lastName() . ' ' . $this->faker->lastName(),
            'fecha_nacimiento' => $this->faker->dateTimeBetween('-85 years', '-1 year')->format('Y-m-d'),
            'genero' => $genero,
            'id_tipo_sanguineo' => TipoSanguineo::query()->inRandomOrder()->value('id'),
            'numero_identificacion' => $this->duiValido(),
            'telefono' => $this->faker->numerify('+503 7###-####'),
            'correo' => $this->faker->boolean(70) ? $this->faker->unique()->safeEmail() : null,
            'direccion' => $this->faker->streetAddress(),
            'nombre_contacto_emergencia' => $this->faker->name(),
            'telefono_contacto_emergencia' => $this->faker->numerify('+503 7###-####'),
            'parentesco_contacto_emergencia' => $this->faker->randomElement([
                'Madre',
                'Padre',
                'Hermano',
                'Hermana',
                'Esposo',
                'Esposa',
                'Hijo',
                'Hija',
            ]),
        ];
    }

    /**
     * Genera un DUI con el formato 00000000-0 y un dígito verificador válido.
     */
    protected function duiValido(): string
    {
        $digitos = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        $suma = 0;

        for ($i = 0; $i < 8; $i++) {
            $suma += (int) $digitos[$i] * (9 - $i);
        }

        $verificador = (10 - ($suma % 10)) % 10;

        return $digitos . '-' . $verificador;
    }
}
