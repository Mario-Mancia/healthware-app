<?php

namespace Database\Seeders;

use App\Models\CondicionMedica;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CondicionMedicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $condicionesMedicas = [
            [
                'condicion_medica' => 'Diabetes mellitus tipo 2',
                'descripcion' => 'Trastorno metabólico caracterizado por niveles elevados de glucosa en sangre.',
            ],
            [
                'condicion_medica' => 'Hipertensión arterial',
                'descripcion' => 'Condición caracterizada por niveles elevados de presión arterial.',
            ],
            [
                'condicion_medica' => 'Asma',
                'descripcion' => 'Enfermedad respiratoria inflamatoria que afecta las vías respiratorias.',
            ],
            [
                'condicion_medica' => 'Enfermedad renal crónica',
                'descripcion' => 'Alteración persistente de la función o estructura de los riñones.',
            ],
            [
                'condicion_medica' => 'Artritis',
                'descripcion' => 'Grupo de enfermedades que afectan las articulaciones.',
            ],
            [
                'condicion_medica' => 'Hipotiroidismo',
                'descripcion' => 'Condición en la que la glándula tiroides produce una cantidad insuficiente de hormonas.',
            ],
            [
                'condicion_medica' => 'Obesidad',
                'descripcion' => 'Condición caracterizada por una acumulación excesiva de grasa corporal.',
            ],
            [
                'condicion_medica' => 'Enfermedad cardiovascular',
                'descripcion' => 'Conjunto de enfermedades que afectan el corazón y los vasos sanguíneos.',
            ],
            [
                'condicion_medica' => 'Hipercolesterolemia',
                'descripcion' => 'Condición caracterizada por niveles elevados de colesterol en la sangre.',
            ],
            [
                'condicion_medica' => 'Anemia',
                'descripcion' => 'Condición caracterizada por una disminución de la cantidad de hemoglobina o glóbulos rojos.',
            ],
            [
                'condicion_medica' => 'Alergias',
                'descripcion' => 'Respuesta del sistema inmunitario ante determinadas sustancias que pueden provocar diversos síntomas.',
            ],
            [
                'condicion_medica' => 'Gastritis crónica',
                'descripcion' => 'Inflamación persistente de la mucosa del estómago.',
            ],
            [
                'condicion_medica' => 'Enfermedad por reflujo gastroesofágico',
                'descripcion' => 'Condición en la que el contenido del estómago regresa de forma frecuente hacia el esófago.',
            ],
            [
                'condicion_medica' => 'Migraña',
                'descripcion' => 'Trastorno caracterizado por episodios recurrentes de dolor de cabeza que pueden acompañarse de otros síntomas.',
            ],
            [
                'condicion_medica' => 'Enfermedad pulmonar obstructiva crónica',
                'descripcion' => 'Enfermedad pulmonar crónica que provoca una limitación persistente del flujo de aire.',
            ],
            [
                'condicion_medica' => 'Osteoporosis',
                'descripcion' => 'Enfermedad caracterizada por una disminución de la densidad y resistencia de los huesos.',
            ],
            [
                'condicion_medica' => 'Epilepsia',
                'descripcion' => 'Trastorno neurológico caracterizado por una predisposición a presentar crisis epilépticas recurrentes.',
            ],
            [
                'condicion_medica' => 'Depresión',
                'descripcion' => 'Trastorno del estado de ánimo que puede afectar los pensamientos, emociones y actividades cotidianas.',
            ],
            [
                'condicion_medica' => 'Ansiedad',
                'descripcion' => 'Condición caracterizada por preocupación, temor o tensión persistente que puede afectar la vida cotidiana.',
            ],
            [
                'condicion_medica' => 'Enfermedad hepática crónica',
                'descripcion' => 'Alteración persistente que afecta la estructura o función del hígado.',
            ],
        ];

        foreach ($condicionesMedicas as $condicionMedica) {
            CondicionMedica::create($condicionMedica);
        }
    }
}
