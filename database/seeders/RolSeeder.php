<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'rol' => 'Administrador',
                'descripcion' => 'Usuario encargado de la gestión general del sistema.'
            ],
            [
                'rol' => 'Médico',
                'descripcion' => 'Usuario encargado de atención y gestión de información clínica de los pacientes.'
            ],
            [
                'rol' => 'Recepcionista',
                'descripcion' => 'Usuario encargado de la recepción, gestión de citas y atención general.'
            ],
            [
                'rol' => 'Contador',
                'descripcion' => 'Usuario encargado de emitir facturas, procesar pagos y consultar reportes financieros.'
            ]
        ];

        foreach ($roles as $rol) {
            Rol::create($rol);
        }
    }
}
