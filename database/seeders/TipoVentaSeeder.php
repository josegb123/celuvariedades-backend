<?php

namespace Database\Seeders;

use App\Models\TipoVenta;
use Illuminate\Database\Seeder;

class TipoVentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoVenta::insert([
            ['nombre' => 'Contado', 'maneja_cartera' => false, 'reserva_stock' => false],
            ['nombre' => 'Crédito', 'maneja_cartera' => true, 'reserva_stock' => false],
            ['nombre' => 'Plan Separe', 'maneja_cartera' => true, 'reserva_stock' => true],
        ]);
    }
}
