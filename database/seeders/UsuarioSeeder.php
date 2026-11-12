<?php

namespace Database\Seeders;

use App\Models\Estado;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea el administrador inicial. Su correo y su contraseña pueden definirse
     * en el archivo .env con ADMIN_CORREO y ADMIN_CONTRASENHA. Al iniciar
     * sesión por primera vez debe cambiar la contraseña.
     */
    public function run(): void
    {
        Usuario::updateOrCreate(
            [
                'correo' => env('ADMIN_CORREO', 'admin@healthware.com'),
            ],
            [
                'id_rol' => Rol::where('rol', 'Administrador')->value('id'),
                'id_estado' => Estado::where('estado', 'ACTIVO')->value('id'),
                'nombres' => 'Administrador',
                'apellidos' => 'del Sistema',
                'contrasenha' => env('ADMIN_CONTRASENHA', 'HealthWare#2026'),
                'telefono' => null,
                'debe_cambiar_contrasenha' => true,
            ]
        );
    }
}
