<?php

namespace Database\Seeders;

use App\Models\Diagnostico;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiagnosticoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diagnosticos = [
            [
                'diagnostico' => 'Hipertensión arterial',
                'descripcion' => 'Elevación persistente de la presión arterial.',
            ],
            [
                'diagnostico' => 'Diabetes mellitus tipo 2',
                'descripcion' => 'Trastorno metabólico caracterizado por niveles elevados de glucosa en sangre.',
            ],
            [
                'diagnostico' => 'Gastritis',
                'descripcion' => 'Inflamación de la mucosa del estómago.',
            ],
            [
                'diagnostico' => 'Migraña',
                'descripcion' => 'Trastorno caracterizado por episodios recurrentes de dolor de cabeza.',
            ],
            [
                'diagnostico' => 'Asma',
                'descripcion' => 'Enfermedad respiratoria caracterizada por inflamación y estrechamiento de las vías respiratorias.',
            ],
            [
                'diagnostico' => 'Rinitis alérgica',
                'descripcion' => 'Inflamación de la mucosa nasal causada por una respuesta alérgica.',
            ],
            [
                'diagnostico' => 'Anemia',
                'descripcion' => 'Condición caracterizada por una disminución de la cantidad de hemoglobina o glóbulos rojos.',
            ],
            [
                'diagnostico' => 'Infección respiratoria',
                'descripcion' => 'Infección que afecta las vías respiratorias superiores o inferiores.',
            ],
            [
                'diagnostico' => 'Resfriado común',
                'descripcion' => 'Infección viral leve que afecta principalmente las vías respiratorias superiores.',
            ],
            [
                'diagnostico' => 'Faringitis',
                'descripcion' => 'Inflamación de la garganta que puede ser causada por infecciones virales o bacterianas.',
            ],
            [
                'diagnostico' => 'Bronquitis',
                'descripcion' => 'Inflamación de los bronquios que puede provocar tos y dificultad para respirar.',
            ],
            [
                'diagnostico' => 'Infección urinaria',
                'descripcion' => 'Infección que afecta alguna parte del sistema urinario, como la vejiga o la uretra.',
            ],
            [
                'diagnostico' => 'Gastroenteritis',
                'descripcion' => 'Inflamación del estómago y los intestinos que puede causar diarrea, vómitos y dolor abdominal.',
            ],
            [
                'diagnostico' => 'Estreñimiento',
                'descripcion' => 'Alteración caracterizada por una disminución en la frecuencia o dificultad para evacuar.',
            ],
            [
                'diagnostico' => 'Dermatitis',
                'descripcion' => 'Inflamación de la piel que puede producir enrojecimiento, picazón o irritación.',
            ],
            [
                'diagnostico' => 'Obesidad',
                'descripcion' => 'Condición caracterizada por una acumulación excesiva de grasa corporal que puede afectar la salud.',
            ],
            [
                'diagnostico' => 'Colesterol elevado',
                'descripcion' => 'Alteración caracterizada por niveles elevados de colesterol en la sangre.',
            ],
            [
                'diagnostico' => 'Dolor lumbar',
                'descripcion' => 'Dolor localizado en la región inferior de la espalda, de origen diverso.',
            ],
            [
                'diagnostico' => 'Artritis',
                'descripcion' => 'Inflamación de una o más articulaciones que puede provocar dolor y limitación del movimiento.',
            ],
            [
                'diagnostico' => 'Alergia',
                'descripcion' => 'Respuesta del sistema inmunitario ante sustancias que normalmente no son perjudiciales.',
            ],
        ];

        foreach ($diagnosticos as $diagnostico) {
            Diagnostico::create($diagnostico);
        }
    }
}
