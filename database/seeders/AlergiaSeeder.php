<?php

namespace Database\Seeders;

use App\Models\Alergia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlergiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alergias = [
            [
                'alergia' => 'Penicilina',
                'descripcion' => 'Alergia a medicamentos derivados de la penicilina.',
            ],
            [
                'alergia' => 'Amoxicilina',
                'descripcion' => 'Alergia al antibiótico amoxicilina.',
            ],
            [
                'alergia' => 'Ibuprofeno',
                'descripcion' => 'Alergia o reacción adversa al ibuprofeno.',
            ],
            [
                'alergia' => 'Aspirina',
                'descripcion' => 'Alergia o reacción adversa al ácido acetilsalicílico.',
            ],
            [
                'alergia' => 'Mariscos',
                'descripcion' => 'Alergia a mariscos como camarones, cangrejos o langostas.',
            ],
            [
                'alergia' => 'Maní',
                'descripcion' => 'Alergia alimentaria al maní.',
            ],
            [
                'alergia' => 'Polen',
                'descripcion' => 'Reacción alérgica provocada por partículas de polen.',
            ],
            [
                'alergia' => 'Látex',
                'descripcion' => 'Alergia al látex presente en diversos productos médicos y de uso cotidiano.',
            ],
            [
                'alergia' => 'Leche de vaca',
                'descripcion' => 'Alergia a las proteínas presentes en la leche de vaca y sus derivados.',
            ],
            [
                'alergia' => 'Huevo',
                'descripcion' => 'Alergia a las proteínas presentes en el huevo.',
            ],
            [
                'alergia' => 'Trigo',
                'descripcion' => 'Reacción alérgica a proteínas presentes en el trigo.',
            ],
            [
                'alergia' => 'Soja',
                'descripcion' => 'Alergia alimentaria provocada por proteínas presentes en la soja.',
            ],
            [
                'alergia' => 'Nueces',
                'descripcion' => 'Alergia a frutos secos como nueces, almendras o avellanas.',
            ],
            [
                'alergia' => 'Pescado',
                'descripcion' => 'Alergia a proteínas presentes en diferentes tipos de pescado.',
            ],
            [
                'alergia' => 'Ácaros del polvo',
                'descripcion' => 'Reacción alérgica causada por partículas y residuos de ácaros presentes en el polvo doméstico.',
            ],
            [
                'alergia' => 'Pelo o caspa de animales',
                'descripcion' => 'Reacción alérgica asociada a proteínas presentes en la piel, saliva o secreciones de animales.',
            ],
            [
                'alergia' => 'Moho',
                'descripcion' => 'Reacción alérgica provocada por esporas de diferentes tipos de hongos presentes en el ambiente.',
            ],
            [
                'alergia' => 'Polvo',
                'descripcion' => 'Reacción alérgica relacionada con partículas presentes en el polvo doméstico.',
            ],
            [
                'alergia' => 'Cefalosporinas',
                'descripcion' => 'Alergia a medicamentos antibióticos pertenecientes al grupo de las cefalosporinas.',
            ],
            [
                'alergia' => 'Sulfonamidas',
                'descripcion' => 'Alergia a medicamentos que contienen compuestos pertenecientes al grupo de las sulfonamidas.',
            ],
        ];

        foreach ($alergias as $alergia) {
            Alergia::create($alergia);
        }
    }
}
