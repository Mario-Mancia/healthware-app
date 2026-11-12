<?php

namespace Database\Seeders;

use App\Models\TipoSanguineo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoSanguineoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposSanguineos = [
            [
                'tipo_sanguineo' => 'A+',
                'descripcion' => 'Grupo sanguíneo A con factor Rh positivo.',
            ],
            [
                'tipo_sanguineo' => 'A-',
                'descripcion' => 'Grupo sanguíneo A con factor Rh negativo.',
            ],
            [
                'tipo_sanguineo' => 'B+',
                'descripcion' => 'Grupo sanguíneo B con factor Rh positivo.',
            ],
            [
                'tipo_sanguineo' => 'B-',
                'descripcion' => 'Grupo sanguíneo B con factor Rh negativo.',
            ],
            [
                'tipo_sanguineo' => 'AB+',
                'descripcion' => 'Grupo sanguíneo AB con factor Rh positivo.',
            ],
            [
                'tipo_sanguineo' => 'AB-',
                'descripcion' => 'Grupo sanguíneo AB con factor Rh negativo.',
            ],
            [
                'tipo_sanguineo' => 'O+',
                'descripcion' => 'Grupo sanguíneo O con factor Rh positivo.',
            ],
            [
                'tipo_sanguineo' => 'O-',
                'descripcion' => 'Grupo sanguíneo O con factor Rh negativo.',
            ],
        ];

        foreach ($tiposSanguineos as $tipoSanguineo) {
            TipoSanguineo::create($tipoSanguineo);
        }
    }
}
