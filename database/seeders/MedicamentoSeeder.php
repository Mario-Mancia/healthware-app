<?php

namespace Database\Seeders;

use App\Models\Medicamento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medicamentos = [
            [
                'nombre' => 'Paracetamol',
                'principio_activo' => 'Paracetamol',
                'presentacion' => 'Tableta',
                'concentracion' => '500 mg',
                'descripcion' => 'Analgésico y antipirético.',
            ],
            [
                'nombre' => 'Amoxicilina',
                'principio_activo' => 'Amoxicilina',
                'presentacion' => 'Cápsula',
                'concentracion' => '500 mg',
                'descripcion' => 'Antibiótico de amplio espectro.',
            ],
            [
                'nombre' => 'Ibuprofeno',
                'principio_activo' => 'Ibuprofeno',
                'presentacion' => 'Tableta',
                'concentracion' => '400 mg',
                'descripcion' => 'Analgésico, antiinflamatorio y antipirético.',
            ],
            [
                'nombre' => 'Omeprazol',
                'principio_activo' => 'Omeprazol',
                'presentacion' => 'Cápsula',
                'concentracion' => '20 mg',
                'descripcion' => 'Medicamento utilizado para reducir la producción de ácido gástrico.',
            ],
            [
                'nombre' => 'Loratadina',
                'principio_activo' => 'Loratadina',
                'presentacion' => 'Tableta',
                'concentracion' => '10 mg',
                'descripcion' => 'Antihistamínico utilizado para tratar síntomas de alergia.',
            ],
            [
                'nombre' => 'Losartán',
                'principio_activo' => 'Losartán potásico',
                'presentacion' => 'Tableta',
                'concentracion' => '50 mg',
                'descripcion' => 'Medicamento utilizado para el control de la presión arterial.',
            ],
            [
                'nombre' => 'Metformina',
                'principio_activo' => 'Metformina',
                'presentacion' => 'Tableta',
                'concentracion' => '850 mg',
                'descripcion' => 'Medicamento utilizado para el control de la glucosa en sangre.',
            ],
            [
                'nombre' => 'Salbutamol',
                'principio_activo' => 'Salbutamol',
                'presentacion' => 'Inhalador',
                'concentracion' => '100 mcg/dosis',
                'descripcion' => 'Broncodilatador utilizado para aliviar síntomas respiratorios.',
            ],
            [
                'nombre' => 'Aspirina',
                'principio_activo' => 'Ácido acetilsalicílico',
                'presentacion' => 'Tableta',
                'concentracion' => '100 mg',
                'descripcion' => 'Medicamento con efecto antiagregante plaquetario utilizado en determinadas condiciones cardiovasculares.',
            ],
            [
                'nombre' => 'Diclofenaco',
                'principio_activo' => 'Diclofenaco sódico',
                'presentacion' => 'Tableta',
                'concentracion' => '50 mg',
                'descripcion' => 'Antiinflamatorio no esteroideo utilizado para aliviar dolor e inflamación.',
            ],
            [
                'nombre' => 'Naproxeno',
                'principio_activo' => 'Naproxeno',
                'presentacion' => 'Tableta',
                'concentracion' => '500 mg',
                'descripcion' => 'Antiinflamatorio no esteroideo utilizado para tratar dolor e inflamación.',
            ],
            [
                'nombre' => 'Azitromicina',
                'principio_activo' => 'Azitromicina',
                'presentacion' => 'Tableta',
                'concentracion' => '500 mg',
                'descripcion' => 'Antibiótico macrólido utilizado para tratar determinadas infecciones bacterianas.',
            ],
            [
                'nombre' => 'Cefalexina',
                'principio_activo' => 'Cefalexina',
                'presentacion' => 'Cápsula',
                'concentracion' => '500 mg',
                'descripcion' => 'Antibiótico perteneciente al grupo de las cefalosporinas.',
            ],
            [
                'nombre' => 'Metronidazol',
                'principio_activo' => 'Metronidazol',
                'presentacion' => 'Tableta',
                'concentracion' => '500 mg',
                'descripcion' => 'Antibiótico y antiparasitario utilizado para tratar determinadas infecciones.',
            ],
            [
                'nombre' => 'Ciprofloxacino',
                'principio_activo' => 'Ciprofloxacino',
                'presentacion' => 'Tableta',
                'concentracion' => '500 mg',
                'descripcion' => 'Antibiótico perteneciente al grupo de las fluoroquinolonas.',
            ],
            [
                'nombre' => 'Cetirizina',
                'principio_activo' => 'Cetirizina',
                'presentacion' => 'Tableta',
                'concentracion' => '10 mg',
                'descripcion' => 'Antihistamínico utilizado para aliviar síntomas de alergias.',
            ],
            [
                'nombre' => 'Dexametasona',
                'principio_activo' => 'Dexametasona',
                'presentacion' => 'Tableta',
                'concentracion' => '4 mg',
                'descripcion' => 'Corticoide utilizado para reducir procesos inflamatorios y determinadas reacciones alérgicas.',
            ],
            [
                'nombre' => 'Prednisona',
                'principio_activo' => 'Prednisona',
                'presentacion' => 'Tableta',
                'concentracion' => '20 mg',
                'descripcion' => 'Corticoide utilizado para tratar diversas condiciones inflamatorias.',
            ],
            [
                'nombre' => 'Hidroclorotiazida',
                'principio_activo' => 'Hidroclorotiazida',
                'presentacion' => 'Tableta',
                'concentracion' => '25 mg',
                'descripcion' => 'Diurético utilizado principalmente para el tratamiento de la hipertensión arterial.',
            ],
            [
                'nombre' => 'Amlodipino',
                'principio_activo' => 'Amlodipino',
                'presentacion' => 'Tableta',
                'concentracion' => '5 mg',
                'descripcion' => 'Medicamento utilizado para el tratamiento de la hipertensión arterial y algunas enfermedades cardiovasculares.',
            ],
            [
                'nombre' => 'Enalapril',
                'principio_activo' => 'Enalapril',
                'presentacion' => 'Tableta',
                'concentracion' => '10 mg',
                'descripcion' => 'Medicamento utilizado para el tratamiento de la hipertensión arterial y la insuficiencia cardíaca.',
            ],
            [
                'nombre' => 'Atorvastatina',
                'principio_activo' => 'Atorvastatina cálcica',
                'presentacion' => 'Tableta',
                'concentracion' => '20 mg',
                'descripcion' => 'Medicamento utilizado para reducir los niveles de colesterol en sangre.',
            ],
            [
                'nombre' => 'Levotiroxina',
                'principio_activo' => 'Levotiroxina sódica',
                'presentacion' => 'Tableta',
                'concentracion' => '50 mcg',
                'descripcion' => 'Hormona tiroidea utilizada como tratamiento de reemplazo en el hipotiroidismo.',
            ],
            [
                'nombre' => 'Glibenclamida',
                'principio_activo' => 'Glibenclamida',
                'presentacion' => 'Tableta',
                'concentracion' => '5 mg',
                'descripcion' => 'Medicamento antidiabético utilizado para ayudar a controlar la glucosa en sangre.',
            ],
            [
                'nombre' => 'Furosemida',
                'principio_activo' => 'Furosemida',
                'presentacion' => 'Tableta',
                'concentracion' => '40 mg',
                'descripcion' => 'Diurético utilizado para tratar la retención de líquidos y determinadas condiciones cardiovasculares.',
            ],
            [
                'nombre' => 'Acetaminofén',
                'principio_activo' => 'Paracetamol',
                'presentacion' => 'Jarabe',
                'concentracion' => '160 mg/5 mL',
                'descripcion' => 'Analgésico y antipirético en presentación líquida.',
            ],
            [
                'nombre' => 'Ambroxol',
                'principio_activo' => 'Ambroxol',
                'presentacion' => 'Jarabe',
                'concentracion' => '15 mg/5 mL',
                'descripcion' => 'Mucolítico utilizado para facilitar la expulsión de secreciones respiratorias.',
            ],
            [
                'nombre' => 'Loperamida',
                'principio_activo' => 'Loperamida',
                'presentacion' => 'Cápsula',
                'concentracion' => '2 mg',
                'descripcion' => 'Medicamento utilizado para el tratamiento sintomático de la diarrea.',
            ],
            [
                'nombre' => 'Ondansetrón',
                'principio_activo' => 'Ondansetrón',
                'presentacion' => 'Tableta',
                'concentracion' => '4 mg',
                'descripcion' => 'Medicamento utilizado para prevenir y controlar las náuseas y los vómitos.',
            ],
            [
                'nombre' => 'Budesonida',
                'principio_activo' => 'Budesonida',
                'presentacion' => 'Inhalador',
                'concentracion' => '200 mcg/dosis',
                'descripcion' => 'Corticoide inhalado utilizado para controlar la inflamación de las vías respiratorias.',
            ],
            [
                'nombre' => 'Clotrimazol',
                'principio_activo' => 'Clotrimazol',
                'presentacion' => 'Crema',
                'concentracion' => '1%',
                'descripcion' => 'Antifúngico tópico utilizado para tratar infecciones por hongos en la piel.',
            ],
            [
                'nombre' => 'Acetaminofén',
                'principio_activo' => 'Acetaminophen',
                'presentacion' => 'Tableta',
                'concentracion' => '500mg',
                'descripcion' => 'Tabletas analgésicas para el dolor local.',
            ],
        ];

        foreach ($medicamentos as $medicamento) {
            Medicamento::create($medicamento);
        }
    }
}
