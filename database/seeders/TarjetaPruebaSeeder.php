<?php

namespace Database\Seeders;

use App\Models\TarjetaPrueba;
use Illuminate\Database\Seeder;

class TarjetaPruebaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tarjetas ficticias para simular los cobros. Los números son de prueba
     * públicos (pasan la validación de Luhn) y no pertenecen a nadie.
     */
    public function run(): void
    {
        $tarjetas = [
            [
                'numero' => '4111111111111111',
                'titular' => 'Titular Prueba Aprobada',
                'tipo' => 'debito',
                'marca' => 'Visa',
                'mes_vencimiento' => 12,
                'anio_vencimiento' => 2030,
                'cvv' => '123',
                'saldo' => 5000.00,
                'estado' => 'activa',
            ],
            [
                'numero' => '5555555555554444',
                'titular' => 'Titular Prueba Credito',
                'tipo' => 'credito',
                'marca' => 'Mastercard',
                'mes_vencimiento' => 11,
                'anio_vencimiento' => 2030,
                'cvv' => '456',
                'saldo' => 3000.00,
                'estado' => 'activa',
            ],
            [
                'numero' => '4012888888881881',
                'titular' => 'Titular Fondos Insuficientes',
                'tipo' => 'debito',
                'marca' => 'Visa',
                'mes_vencimiento' => 10,
                'anio_vencimiento' => 2030,
                'cvv' => '789',
                'saldo' => 5.00,
                'estado' => 'activa',
            ],
            [
                'numero' => '5105105105105100',
                'titular' => 'Titular Tarjeta Bloqueada',
                'tipo' => 'credito',
                'marca' => 'Mastercard',
                'mes_vencimiento' => 9,
                'anio_vencimiento' => 2030,
                'cvv' => '321',
                'saldo' => 2000.00,
                'estado' => 'bloqueada',
            ],
            [
                'numero' => '4222222222222',
                'titular' => 'Titular Tarjeta Vencida',
                'tipo' => 'credito',
                'marca' => 'Visa',
                'mes_vencimiento' => 1,
                'anio_vencimiento' => 2020,
                'cvv' => '654',
                'saldo' => 2000.00,
                'estado' => 'activa',
            ],
        ];

        foreach ($tarjetas as $tarjeta) {
            TarjetaPrueba::create($tarjeta);
        }
    }
}
