<?php

namespace Database\Seeders;

use App\Models\Servicio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicios = [
            [
                'servicio' => 'Consulta médica general',
                'descripcion' => 'Consulta médica de atención general.',
                'precio' => 25.00,
            ],
            [
                'servicio' => 'Consulta especializada',
                'descripcion' => 'Consulta médica realizada por un especialista.',
                'precio' => 40.00,
            ],
            [
                'servicio' => 'Consulta de seguimiento',
                'descripcion' => 'Consulta para seguimiento de una atención médica previa.',
                'precio' => 20.00,
            ],
            [
                'servicio' => 'Control médico',
                'descripcion' => 'Evaluación general de control del estado de salud del paciente.',
                'precio' => 20.00,
            ],
            [
                'servicio' => 'Curación',
                'descripcion' => 'Procedimiento de limpieza y tratamiento de heridas.',
                'precio' => 15.00,
            ],
            [
                'servicio' => 'Aplicación de inyección',
                'descripcion' => 'Servicio de administración de medicamentos mediante inyección.',
                'precio' => 5.00,
            ],
            [
                'servicio' => 'Examen de laboratorio',
                'descripcion' => 'Servicio relacionado con la realización de pruebas de laboratorio.',
                'precio' => 18.00,
            ],
            [
                'servicio' => 'Electrocardiograma',
                'descripcion' => 'Estudio para registrar la actividad eléctrica del corazón.',
                'precio' => 30.00,
            ],
        ];

        foreach ($servicios as $servicio) {
            Servicio::create($servicio);
        }
    }
}
