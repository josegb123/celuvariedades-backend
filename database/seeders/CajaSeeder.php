<?php

namespace Database\Seeders;

use App\Models\CajaDiaria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CajaDiaria::create(
            [
                'user_id' => 1,
                'fecha_apertura' => now(),
                'fondo_inicial' => 1000000,
                'fecha_cierre' => null,
                'estado' => 'Abierta',
            ]
        );
    }
}
