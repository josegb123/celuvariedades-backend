<?php

namespace Database\Seeders;

use App\Models\Venta;
use Illuminate\Database\Seeder;

class VentaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea 10 ventas, cada una con un número aleatorio de productos (entre 1 y 5).
        Venta::factory(10)->withItems(5)->create();

        // Crea 1 venta, con entre 1 y 10 productos distintos.
        Venta::factory()->withItems(10)->create();
    }
}
