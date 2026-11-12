<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            [
                'estado' => 'ACTIVO',
                'descripcion' => 'El usuario puede hacer uso normal del sistema.'
            ],
            [
                'estado' => 'BLOQUEADO',
                'descripcion' => 'El usuario no puede ingresar al sistema temporalmente, pero es posible reestablecer su acceso.'
            ],
            [
                'estado' => 'ELIMINADO',
                'descripcion' => 'El usuario está eliminado del sistema irreversiblemente (conservado para auditoría).'
            ]
        ];

        foreach ($estados as $estado) {
            Estado::create($estado);
        }
    }
}
