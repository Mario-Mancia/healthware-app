<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Inicializar la base de datos, en orden específico de creación.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            EstadoSeeder::class,
            TipoSanguineoSeeder::class,
            EspecialidadSeeder::class,
            DiagnosticoSeeder::class,
            CondicionMedicaSeeder::class,
            AlergiaSeeder::class,
            MedicamentoSeeder::class,
            ServicioSeeder::class,
            UsuarioSeeder::class,
            TarjetaPruebaSeeder::class,
        ]);

        /*
         * Datos de demostración: solo en el entorno local.
         */
        if (app()->environment('local')) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}
