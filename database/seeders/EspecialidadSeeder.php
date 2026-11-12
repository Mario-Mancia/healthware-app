<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $especialidades = [
            [
                'especialidad' => 'Medicina General',
                'descripcion' => 'Atención médica integral y evaluación inicial de pacientes.',
            ],
            [
                'especialidad' => 'Cardiología',
                'descripcion' => 'Prevención, diagnóstico y tratamiento de enfermedades cardiovasculares.',
            ],
            [
                'especialidad' => 'Pediatría',
                'descripcion' => 'Atención médica especializada para niños y adolescentes.',
            ],
            [
                'especialidad' => 'Ginecología',
                'descripcion' => 'Atención médica especializada en salud reproductiva femenina.',
            ],
            [
                'especialidad' => 'Dermatología',
                'descripcion' => 'Diagnóstico y tratamiento de enfermedades de la piel, cabello y uñas.',
            ],
            [
                'especialidad' => 'Neurología',
                'descripcion' => 'Diagnóstico y tratamiento de enfermedades del sistema nervioso.',
            ],
            [
                'especialidad' => 'Traumatología',
                'descripcion' => 'Atención de lesiones y enfermedades del sistema musculoesquelético.',
            ],
            [
                'especialidad' => 'Oftalmología',
                'descripcion' => 'Prevención, diagnóstico y tratamiento de enfermedades oculares.',
            ],
        ];

        foreach ($especialidades as $especialidad) {
            Especialidad::create($especialidad);
        }
    }
}
